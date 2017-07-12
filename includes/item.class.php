<?php


class Newsflow_Item{

    public $id=0;
    public $name;
    public $channel;
    public $url="http://";
    private $category_id=0;
    public $search="";
    public $maxchars=0;
    public $maxage=0;
    public $removechars=0;
    public $posttypes=array();
    public $error;
    public $report;
    public $stats;
    public $parent;
    public $status;

    public function __construct($link_id, $bookmark=null){

        if( !function_exists( 'wp_insert_link' ) )
            include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );

        if (!$link_id){

            if (!$bookmark){
                //default values!
                $this->apply_default();
            }
            else{
                $this->apply_default($bookmark);
            }
        }
        else
        {
            $bookmark = get_bookmark($link_id);
            $this->apply_bookmark($bookmark);
        }

    }

    public function set_type_browse(){
        $this->category_id = $this->get_category_browse();
    }

    public function save(){

        $meta = get_object_vars($this);

        $this->url = str_replace('feed://', 'http://', $this->url);

        $linkdata = array(
            "link_url"		=> $this->url, // varchar, the URL the link points to
            "link_rss"		=> $this->url, // varchar, the URL the link points to
            "link_name"		=> $this->name, // varchar, the title of the link
            "link_description"	=> '', // varchar, a short description of the link
            "link_visible"		=> 'N', // varchar, Y means visible, anything else means not
            "link_category"		=> $this->category_id, // int, the term ID of the link category. if empty, uses default link category
            "link_owner" => $this->parent,
            "link_notes" => json_encode($meta)
        );

        if ($this->id) $linkdata["link_id"] = $this->id;

        delete_option('newsflow_channels');

        if ($this->id){
            return wp_update_link($linkdata);
        }
        else{
            $this->id = wp_insert_link($linkdata);
            return $this->id;
        }
    }

    public static function delete($id){
        global $wpdb;

        wp_delete_link($id);
        delete_option('newsflow_channels');

        //Delete from database!
        $sql = "DELETE FROM " . Newsflow_Database::database_table_raw() . " WHERE link_id='" . $id . "'";
        $wpdb->query($sql);
        $sql = "DELETE FROM " . Newsflow_Database::database_table_store() . " WHERE link_id='" . $id . "'";
        $wpdb->query($sql);

    }

    public static function get_links(){

        $result = array();

        $category_id = Newsflow_Item::get_category_link();

        $args = array(
            'limit'          => -1,
            'hide_invisible' => 0,
            'category_name'       => 'Newsflow Links');

        $bookmarks = get_bookmarks($args);
        foreach ($bookmarks as $bookmark){
            $link = new Newsflow_Item($bookmark->link_id, $bookmark);
            $result[] = $link;
        }


        if (count($result)==0){
            $old_settings = get_option('hypernews_settings',array());

            if (!is_array($old_settings)){
                $old_settings = json_encode($old_settings);
            }

            if (sizeof($old_settings)>0 && sizeof($old_settings['Links'])){
                $result = Newsflow_Item::add_old_links($old_settings['Links']);
            }
        }

        return $result;

    }

    //Get links and sort them into next execution order...
    public static function get_exec_links(){

        function cmp($a, $b)
        {
            return strcmp($a->stats->date, $b->stats->date);
        }

        $links = Newsflow_Item::get_links();
        usort($links, "cmp");
        return $links;

    }

    public static function browsers(){

        $result = array();

        $category_id = Newsflow_Item::get_category_browse();

        $args = array(
            'orderby'        => 'name',
            'order'          => 'ASC',
            'limit'          => -1,
            'hide_invisible' => 0,
            'category_name'       => 'Newsflow Browse');

        foreach (get_bookmarks($args) as $bookmark){
            $result[] = new Newsflow_Item($bookmark->link_id, $bookmark);
        }

        if (count($result)==0){
            $old_settings = get_option('hypernews_settings',array());

            if (!is_array($old_settings)){
                $old_settings = json_encode($old_settings);
            }

            if (sizeof($old_settings)>0 && sizeof($old_settings['Browsers'])){
                $result = Newsflow_Item::add_old_browsers($old_settings['Browsers']);
            }
        }

        return $result;

    }

    public static function channels(){

        $result = get_option('newsflow_channels', array());

        if (sizeof($result)<1){
            $links = Newsflow_Item::get_links();
            foreach ($links as $key => $value) {
                if (!in_array($value->channel, $result)) {
                    $result[] = $value->channel;
                }
            }
            update_option('newsflow_channels', $result);
        }

        return $result;
    }

    private function apply_bookmark($bookmark){
        $this->id = $bookmark->link_id;
        $this->name = $bookmark->link_name;

        $meta = json_decode($bookmark->link_notes);

        $this->channel = $meta->channel;
        $this->search = $meta->search;

        $this->maxage = $meta->maxage;
        $this->maxchars = $meta->maxchars;
        $this->removechars = $meta->removechars;

        $this->category_id = $bookmark->link_category;

        $this->url = $bookmark->link_url;

        $this->posttypes = $meta->posttypes;
        $this->error = $meta->error;
        $this->report = $meta->report;

        $this->stats = $meta->stats;

        $this->status = $meta->status;

        $this->parent = $meta->parent;

    }

    private function apply_default(){
        $this->id = 0;
        $this->name = __('A new Newsflow source added','newsflow');
        $this->channel = __('Standard channel','newsflow');
        $this->category_id = $this->get_category_link();
    }

    public static function get_category_link(){

        $category_id = get_option('newsflow_category_link_id',0);

        $cat = get_category($category_id);
        if (isset($cat->errors)) $category_id=0;

        if (!$category_id){

            //$new_cat = array(
            //    'cat_name' => 'Newsflow Links',
            //    'category_description' => __('This category is used to group Newsflow links. Please leave this one! It is vital for the Newsflow plugin.','newsflow'),
            //    'taxonomy' => 'link_category' );
            //$category_id = wp_insert_category($new_cat);

            $category_id = wp_insert_term('Newsflow Links', 'link_category', array(
                'description'=> 'This category is used to group Newsflow links. Please leave this one! It is vital for the Newsflow plugin.'
            ));

            update_option('newsflow_category_link_id',$category_id);
        }

        return $category_id;

    }

    public function get_category_browse(){

        $category_id = get_option('newsflow_category_browse_id',0);

        $cat = get_category($category_id);
        if (isset($cat->errors)) $category_id=0;

        if (!$category_id){
            $new_cat = array(
                'cat_name' => 'Newsflow Browse',
                'category_description' => __('This category is used to group Newsflows browse elements. Please leave this one! It is vital for the Newsflow plugin.','newsflow'),
                'taxonomy' => 'link_category' );
            $category_id = wp_insert_category($new_cat);
            update_option('newsflow_category_browse_id',$category_id);
        }

        //$cat = get_category($category_id);
        return $category_id;

    }


    public function fetch( $test = false ){

        global $wpdb;

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;

        if ($this->status=='inactive'){
            if (!is_array($this->stats)) $this->stats = array();
            $this->stats['date'] = date('Y-m-d H:i:s');
            $this->save();
            return;
        }

        $simplepie = ABSPATH . WPINC . '/class-simplepie.php';
        include_once $simplepie;

        $url = $this->url;
        $url = str_replace('feed://', 'http://', $url);

        $timeout = get_option( 'hypernews_timeout', '10' );

        $feed = new SimplePie();

        //$feed->set_raw_data( wp_remote_retrieve_body( wp_remote_get( $url ) ) );

        $feed->set_feed_url($url);
        $feed->set_timeout($timeout);
        $feed->enable_cache(false);

        $feed->set_output_encoding();
        $feed->init();
        $feed->handle_content_type();

        $zone = get_option('newsflow_datetime_zone',0);
        if ($zone){
            date_default_timezone_set($zone);
        }

        if ($feed->error())
        {
            //Check autodiscover and clean the feed!
            $raw = $feed->get_all_discovered_feeds();
            if (sizeof($raw)>0){
                $feed = new SimplePie();
                $feed->set_raw_data($this->stripInvalidXml($raw[0]->body));
                $feed->set_timeout($timeout);
                $feed->enable_cache(false);
                $feed->force_feed(true);
                $feed->set_output_encoding();
                $feed->init();
                $feed->handle_content_type('text/xml');
            }

            if ($feed->error())
            {
                $this->error = "NOK " . $feed->error();
                if (!is_array($this->stats)) $this->stats = array();
                $this->stats['date'] = date('Y-m-d H:i:s');

                $this->save();

                if ($this->report && !$test){
                    $message = __('Error when fetching', 'newsflow') . ": ".$this->name."\n".$feed->error()."\nEdit your RSS-link: ".get_bloginfo('url')."/wp-admin/admin.php?page=newsflow_links&id=".$this->id;
                    $message .= "\nFetching from URL: ". $feed->feed_url;
                    //$headers = 'From: '.$this->matchmail_settings['sender_name'].' <'.$this->matchmail_settings['sender_email'].'>' . "\r\n";
                    //add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
                    wp_mail( $this->report, $this->name, $message );
                }
                return false;
            }
        }

        $index = 0;
        $added = 0;

        foreach ($feed->get_items() as $key => $item){

            $index++;

            $url = esc_attr($item->get_link());

            $guid = $this->id . '|' . $url;

            if (empty($url)){
                $guid = $this->id . '|' . $item->get_id(true);
            }

                //Check if exists then continue
            $sql = "SELECT count(*) FROM " . Newsflow_Database::database_table_raw() . " WHERE guid = '$guid';";
            if ($wpdb->get_var( $sql ) ) {
                continue;
            }

            $title = esc_html($item->get_title());

            if (empty($title)){
                $title = __('(This feed item contains no title)', 'newsflow');
            }

            $description = trim(esc_html(strip_tags($item->get_description())));

            if (empty($description)){
                $description = __('(This feed item contains no content)', 'newsflow');
            }

            $pubdate = $item->get_date();

            $simdate = '';
            if (empty($pubdate) || $pubdate < '2000-01-01'){

                $simdate = $item->get_item_tags('','pubDate');
                $simdate = $simdate[0]['data'];

                $pubdate = date_parse($simdate);
                $pubdate = $pubdate['year'] . '-' . $pubdate['month'] . '-' . $pubdate['day'] . ' ' . $pubdate['hour'] . ':' . $pubdate['minute'] . ':' . $pubdate['second'];

                if (!$this->isDate($pubdate)){

                    $days = array(
                        'må'=>'mon',
                        'ti'=>'tue',
                        'on'=>'wed',
                        'to'=>'thu',
                        'fr'=>'fre',
                        'lö'=>'sat',
                        'sö'=>'sun'
                    );

                    $months = array(
                        'januari'=>'jan',
                        'februari'=>'feb',
                        'mars'=>'mar',
                        'april'=>'apr',
                        'maj'=>'may',
                        'juni'=>'jun',
                        'juli'=>'jul',
                        'augusti'=>'aug',
                        'september'=>'sep',
                        'oktober'=>'oct',
                        'november'=>'nov',
                        'december'=>'dec',
                    );

                    $pubdate = $simdate;

                    foreach($days as $key => $day){
                        $pubdate = str_replace($key, $day, $pubdate);
                    }

                    foreach($months as $key => $month){
                        $pubdate = str_replace($key, $month, $pubdate);
                    }

                    $pubdate = date_parse($pubdate);
                    $pubdate = $pubdate['year'] . '-' . $pubdate['month'] . '-' . $pubdate['day'] . ' ' . $pubdate['hour'] . ':' . $pubdate['minute'] . ':' . $pubdate['second'];

                    if (!$this->isDate($pubdate)){
                        $pubdate = date('Y-m-d H:i:s');
                    }
                }

            }

            $pubdate = date('Y-m-d H:i:s', strtotime($pubdate));

            $content = strtolower($title);
            $content .= strtolower(' '.$description);

            $content = str_replace('&ouml;','ö',$content);
            $content = str_replace('&auml;','ä',$content);
            $content = str_replace('&aring;','å',$content);

            $sql = "INSERT INTO " . Newsflow_Database::database_table_raw() . "
                 (
                 guid,
                 link_id,
                 title,
                 link,
                 description,
                 content,
                 pubdate,
                 stored,
                 matched,
                 simdate,
                 system
                 )
                 VALUES
                 (
                 '$guid',
                 '$this->id',
                 '$title',
                 '$url',
                 '$description',
                 '$content',
                 '$pubdate',
                 0,
                 0,
                 '$simdate',
                 'Only raw and not handled by SQL-matching'
                 );";

            if ($wpdb->query($sql)) $added++;
        }

        /*
         * 1. uppdatera databasen med ny raw-table!
         * 2. ÅLDER - kolla alla obehandlade med link_id om för gammal och uppdatera system samt matched=0 med det och sätt stored=1 på dem också.
         * 3. SÖKORD - Kolla om match med sökord
         * 4. Överför röset... och markera dem behandlade
         * 5. Ändra i wp_list_table så att de extra fälten syns!
        */

        //Remove items that are to old!
        if ($this->maxage>0){
            //$date = date('Y-m-d H:i:s', strtotime(getdate() . " -".$link['maxage']." hours"));
            //$sql = "UPDATE " . $wpdb->prefix . "hypernews_raw SET stored=1, matched=0, system='This item is to old' WHERE link_id='".$link['id']."' AND stored=0 AND pubdate < '".$date."'";
            //$wpdb->query($sql);
        }

        //Remove items younger than now!
        //$sql = "UPDATE " . Newsflow_Database::database_table_raw() . " SET stored=1, matched=0, system='This item is not yet born (!)' WHERE link_id='".$this->id."' AND stored=0 AND pubdate > NOW(); ";
        //Put earliest date to now!
        $sql = "UPDATE " . Newsflow_Database::database_table_raw() . " SET pubdate=CURRENT_TIMESTAMP(), system='This item is not yet born (!)' WHERE link_id='".$this->id."' AND stored=0 AND pubdate > NOW(); ";

        $wpdb->query($sql);

        //Check with search words in links
        $search = $this->search;
        $search_words = array();

        if (strpos($search,',')){
            $search_words = explode(',', $search);
        }
        else
        {
            if (strlen($search)>0){
                $search_words[] = trim($search);
            }
        }

        if (sizeof($search_words)>0){

            $sql = "UPDATE " . Newsflow_Database::database_table_raw() . " SET stored=1, matched=0, system='No match for item' WHERE link_id='".$this->id."' AND stored=0 ";
            foreach($search_words as $word){
                $sql .= "AND content NOT like '%" . $word . "%' ";
            }

            $wpdb->query($sql);
        }

        //SPARA RÖSET!
        $sql = "INSERT INTO " . Newsflow_Database::database_table_store() . " (title,url,link_id,channel,source,description,pubdate,guid,status)
                    SELECT title,link,link_id,'".$this->channel."','".$this->name."',description,pubdate,guid,'NEW' FROM " . Newsflow_Database::database_table_raw() . " WHERE stored=0 AND link_id='".$this->id."';";

        $result = $wpdb->query($sql);

        $sql = "UPDATE " . Newsflow_Database::database_table_raw() . " SET stored=1, matched=1, system='Transfered!' WHERE link_id='".$this->id."' AND stored=0; ";
        $wpdb->query($sql);

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);

        if (!is_array($this->stats)) $this->stats = array();

        $this->stats['time'] = round($totaltime,2);
        $this->stats['date'] = date('Y-m-d H:i:s');
        $this->stats['result'] = $result;
        $this->stats['added'] = $added;
        $this->stats['index'] = $index;

        $this->error="OK";

        $this->save();

        if (!$test)
            delete_transient('hypernews_cache_unread');

        return true;

    }


    function match(){
        global $wpdb;

        $sql = "SELECT * FROM " . Newsflow_Database::database_table_store() . " WHERE link_id='" . $this->id . "' ORDER BY pubdate DESC;";
        $rows = $wpdb->get_results($sql);
        foreach($rows as $row){
            echo '<a href="'.$row->url.'" target="_blank" alt="'.$row->title.'">'.$row->title.'</a><br/>';
        }

    }

    function missmatch(){
        global $wpdb;

        $sql = "SELECT * FROM " . Newsflow_Database::database_table_raw() . " WHERE link_id='" . $this->id . "' AND matched=0 ORDER BY pubdate DESC;";
        $rows = $wpdb->get_results($sql);
        foreach($rows as $row){
            echo '<a href="'.$row->url.'" target="_blank" alt="'.$row->title.'">'.$row->title.'</a><br/>';
        }

        if (count($rows)==0) _e('(none found)','newsflow');
    }

    static function add_old_links($old_links){
        $result = array();
        $added = array();

        foreach($old_links as $old_link){

            if (!in_array($old_link['id'], $added)){
                $link = new Newsflow_Item(0);
                $link->name = $old_link['source'];
                $link->url = $old_link['url'];
                $link->channel = $old_link['channel'];
                $link->search = $old_link['search'];
                $link->maxchars = $old_link['maxchars'];
                $link->removechars = $old_link['removechars'];
                $link->maxage = $old_link['maxage'];
                $link->posttypes = $old_link['posttypes'];
                $link->report = $old_link['report'];

                $id = $link->save();

                $added[] = $old_link['id'];

                $result[] = $link;
            }

        }
        return $result;
    }

    static function add_old_browsers($old_browsers){


        //DISABLED!
        return array();

        $result = array();
        $added = array();

        foreach($old_browsers as $key => $old_browser){

            if (!in_array($old_browser['id'], $added)){
                $link = new Newsflow_Item(0);
                $link->set_type_browse();
                $link->name = $old_browser['source'];
                $link->url = $old_browser['url'];

                $link->parent = $old_browser['parent'];

                $id = $link->save();
                $old_browsers[$key]['new_id'] = $id;

                $added[] = $old_browser['id'];

                $result[] = $link;
            }

        }

        //Now the parents!
        foreach($result as $link){
            foreach($old_browsers as $old){
                if ($old['id']==$link->parent) {
                    $link->parent = $old['new_id'];
                    $link->save();
                }
            }
        }

        return $result;
    }

    function isDate($string)
    {
        $t = strtotime($string);
        $m = date('m',$t);
        $d = date('d',$t);
        $y = date('Y',$t);
        $result = checkdate ($m, $d, $y);

        if ($result){
            if ($y<'2000') return false;
        }

        return $result;

    }

    /**
     * Removes invalid XML
     *
     * @access public
     * @param string $value
     * @return string
     */
    function stripInvalidXml($value)
    {
        $ret = "";
        $current;
        if (empty($value))
        {
            return $ret;
        }

        $length = strlen($value);
        for ($i=0; $i < $length; $i++)
        {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF)))
            {
                $ret .= chr($current);
            }
            else
            {
                $ret .= " ";
            }
        }
        return $ret;
    }

}



?>