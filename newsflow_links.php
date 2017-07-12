<?php


function newsflow_links(){
    global $wpdb;
    global $current_user;
    get_currentuserinfo();

    $src = WP_PLUGIN_URL . '/newsflow/js/newsflow.js';
    wp_deregister_script('newsflowAjax');
    wp_register_script('newsflowAjax', $src);
    wp_enqueue_script('newsflowAjax');
    wp_localize_script('newsflowAjax','newsflowAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

    $current_link = new Newsflow_Item(0);

    if (isset($_POST['id'])){
        //SPARA

        $link_id = esc_attr($_POST['id']);

        if ($link_id) {
            $current_link = new Newsflow_Item($link_id);
        }

        $current_link->name = esc_attr($_POST['source']);
        $current_link->channel = esc_attr($_POST['channel']);
        $current_link->url = esc_url($_POST['url']);
        $current_link->search = esc_attr($_POST['search']);
        $current_link->maxchars = esc_attr($_POST['maxchars']);
        $current_link->removechars = esc_attr($_POST['removechars']);
        $current_link->maxage = esc_attr($_POST['maxage']);
        //$current_link['sort_order'] = esc_attr($_POST['sort_order']);
        $current_link->posttypes = $_POST['posttypes'];
        $current_link->report = esc_attr($_POST['report']);

        $current_link->status = esc_attr($_POST['status']);

        $current_link->error = "";

        $id = $current_link->save();

        echo '<script>document.location="?page=newsflow_links#'.$id.'";</script>';
        return;
    }


    $link_id = 0;
    if (isset($_GET['id'])){
        
        if (isset($_GET['delete'])){
            Newsflow_Item::delete((int)esc_attr($_GET['id']));
            echo '<script>document.location="?page=newsflow_links";</script>';
        }
        else{
            $current_link = new Newsflow_Item((int)esc_attr($_GET['id']));
            $link_id = $current_link->id;
        }
        
    }

    add_thickbox();

    ?>
    <div class="wrap">
        <div id="icon-link-manager" class="icon32"><br/></div><h2><?php _e('Newsflow', 'newsflow'); echo ' - '; _e('RSS Feeds', 'newsflow'); ?></h2>

        <form method="post" id="newsflow_links_form" action="">
        <?php
        if (isset($_GET['id'])) {
            ?>
            <h3><?php _e('Edit RSS source','newsflow'); ?></h3>
                <table cellpadding="6">
                    <tr>
                        <td align="right">
                            <?php _e('Source name','newsflow'); ?>:
                        </td>
                        <td>
                            <input type="text" name="source" value="<?php echo $current_link->name; ?>" id="newsflow_name" size="50" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <span style="width:200px;"><?php _e('Status:','newsflow'); ?></span>
                        </td>
                        <td>
                            <select name="status">
                                <option value="active"><?php _e('Active','newsflow'); ?></option>
                                <option value="inactive"<?php if ($current_link->status=='inactive') echo ' selected="selected"'; ?>><?php _e('Inactive','newsflow'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Channel name','newsflow'); ?>:
                        </td>
                        <td>
                            <input type="text" name="channel" value="<?php echo $current_link->channel; ?>" size="50" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Url','newsflow'); ?>:
                        </td>
                        <td>
                            <textarea name="url" id="url" cols="50" rows="10" scrollbars="1"><?php echo $current_link->url; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Search','newsflow'); ?>:
                        </td>
                        <td>
                            <textarea name="search" cols="50" rows="10" scrollbars="1"><?php echo $current_link->search; ?></textarea><br/>
                            <i><?php _e('Only collecting news with one of these comma separated words found','newsflow'); ?></i>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Strikethrough text after n-characters:', 'newsflow'); ?>
                        </td>
                        <td>
                            <input type="text" name="maxchars" value="<?php echo $current_link->maxchars; ?>" size="5" />&nbsp;<?php _e('( 0 = feature disabled )', 'newsflow'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Remove text after n-characters:', 'newsflow'); ?>
                        </td>
                        <td>
                            <input type="text" name="removechars" value="<?php echo $current_link->removechars; ?>" size="5" />&nbsp;<?php _e('( 0 = feature disabled )', 'newsflow'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Delete RSS-items older than:', 'newsflow'); ?>
                        </td>
                        <td>
                            <input type="text" name="maxage" value="<?php echo $current_link->maxage; ?>" size="5" />&nbsp;<?php _e('( hours, 0 = feature disabled )', 'newsflow'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php _e('Email errors to:', 'newsflow');

                            $report = $current_link->report;
                            if (!$report){
                                wp_get_current_user();
                                $report = $current_user->user_email;
                            }

                            ?>
                        </td>
                        <td>
                            <input type="text" name="report" value="<?php echo $report ?>" size="50" />&nbsp;<?php _e('( separate multiple email adresses with , )', 'newsflow'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php
                                _e('Publish to:', 'newsflow');
                            ?>
                        </td>
                        <td>
                            <?php
                                echo '<div>';
                                $post_types=get_post_types(array('public'=>true),'objects');
                                foreach ($post_types as $post_type )
                                {
                                    echo '<span style="float:left; border: solid 1px #aaa; background-color:#eee;padding:4px;margin-right:10px;margin-bottom: 10px; min-width: 150px; overflow: hidden; ">';
                                    echo '<input type="checkbox" name="posttypes[]" value="'.$post_type->name.'" ';

                                    $posttypes = $current_link->posttypes;
                                    if (!is_array($posttypes)) $posttypes = array();

                                    if (in_array($post_type->name, $posttypes))
                                    {
                                        echo ' checked';
                                    }

                                    echo '> '. $post_type->label. '</span>';

                                }
                                echo '<div style="clear:both;"></div>';
                                echo '</div>';
                            ?>
                        </td>
                    </tr>
                    <!--tr>
                        <td align="right">
                            <span style="width:200px;"><?php _e('Sort order:','newsflow'); ?></span>
                        </td>
                        <td>
                            <input type="text" name="sort_order" value="<?php echo $current_link->sort_order; ?>" size="5" />
                        </td>
                    </tr-->
                    <tr>
                        <td>
                            <input type="hidden" name="save" value="true" />
                            <input type="hidden" name="id" value="<?php echo $current_link->id; ?>" />
                        </td>
                        <td>
                            <input type="submit" name="save" class="button-primary" value="<?php _e('Save','newsflow') ?>" />
                            <a name="test" class="button-secondary" onclick="tb_show('Test feed','<?php echo admin_url('admin-ajax.php'); ?>?action=newsflow_test&width=600&height=600&data='+jQuery(this).closest('form').serialize());"><?php _e('Test feed','newsflow') ?></a>
                            <a name="clear" class="button-secondary" onclick="tb_show('Clear cache','<?php echo admin_url('admin-ajax.php'); ?>?action=newsflow_clear&width=600&height=600&data='+jQuery(this).closest('form').serialize());"><?php _e('Clear cache','newsflow') ?></a>
                            <input type="button" class="button-secondary" value="<?php _e('Cancel','newsflow') ?>" onclick="document.location='?page=newsflow_links';" />
                        </td>
                    </tr>
                </table>

        <?php

            if ($_POST["clear"]){
                echo '<h3>Clear...</h3>';
                $sql = "DELETE FROM ".Newsflow_Database::database_table_store() . " WHERE link_id=".$current_link->id;
                $wpdb->query( $sql );
                $sql = "UPDATE ".Newsflow_Database::database_table_raw() . " SET (stored=0) WHERE link_id=".$current_link->id;
                $wpdb->query( $sql );
            }

            if ($_POST["test"]){
                do_action('newsflow_fetch',$current_link->id,true);
            }
        }
        else 
        {


            $list = new newsflow_links();
            $list->prepare_items();
            $list->display();

        }
        
        ?>
            
        </form>        
        
    </div>
<?php
}

function newsflow_clear(){
    global $wpdb;

    $id = esc_attr($_GET['id']);

    if ($id){

        $link = new Newsflow_Item(esc_attr($_GET['id']));

        $sql = "DELETE FROM ".Newsflow_Database::database_table_store() . " WHERE link_id=".$id;
        $wpdb->query( $sql );
        $sql = "UPDATE ".Newsflow_Database::database_table_raw() . " SET (stored=0) WHERE link_id=".$id;
        $wpdb->query( $sql );

        echo '<h2>Result</h2>';
        echo $link->name . __(' memory cache is cleared.');

    }

    die(1);

}

function newsflow_test(){

    $url = esc_url($_GET['url']);
    $search = esc_attr($_GET['search']);

    $test = new Newsflow_Item(0);
    $test->url = $url;
    $test->search = $search;
    $test->name = "###TEST###";

    $test->save();

    $result = $test->fetch(true);

    echo '<h2>Result</h2>';

    if (!$result){
        echo '<h3>' . __('Error:','newsflow') . '</h3>';
        echo $test->error;
    }
    else{
        echo __('Found','newsflow') . ': '.$test->stats['index'].', ' . __('Time','newflow') . ': '.$test->stats['time'].' s<br/>';
        echo '<h3>' . __('Matched news:','newsflow') . '</h3>';
        $test->match();
        echo '<h3>' . __('Mismatch:','newsflow') . '</h3>';
        $test->missmatch();
    }

    $test->delete($test->id);

    die(1);
}


if(!class_exists('WP_List_Table'))
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class newsflow_links extends WP_List_Table {
    
    function __construct() 
    {
        global $status, $page;
         parent::__construct( array(
            'singular'=> 'Link', //Singular label
            'plural' => 'Links', //plural label, also this well be one of the table css class
            'ajax'	=> false //We won't support Ajax for this table
            ) );
    }    
    
    function prepare_items() {
        global $_wp_column_headers;
        global $current_user;
        get_currentuserinfo();

        $channel = get_user_meta($current_user->ID, "hypernews_channel",true);

        $screen = get_current_screen();
                
        $this->process_bulk_action();

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        /* -- Fetch the items -- */
        $data = Newsflow_Item::get_links();

        if ($channel){
            foreach($data as $key => $link){
                if ($link->channel!=$channel){
                    unset($data[$key]);
                }
            }
        }


        //sort link for latest execution time
        //function sort_data($a, $b)
        //{
        //    $meta_a = json_decode($a->link_notes);
        //    $meta_b = json_decode($b->link_notes);
        //    return $meta_a['source'] > $meta_b['source'];
        //}
        //usort($data, 'sort_data');

        $this->items = $data;

    }
    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id' => 'Id',
            'status'     => __('Status','newsflow'),
            'source'     => __('Source','newsflow'),
            'channel'    => __('Channel','newsflow'),
            'url'  => __('RSS-Url','newsflow'),
            'posttypes' => __('Post types','newsflow'),
            'fetch_date'    => __('Latest fetch','newsflow'),
            'fetch_time'    => __('Exec time','newsflow'),
            'fetch_index'    => __('# items','newsflow'),
            'fetch_added'    => __('New articles','newsflow'),
            'error' => __('Error','newsflow')
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        return array();
    }
    
    function get_hidden_columns() {
        $result = array();
        $result[] = "id";
        $result[] = "url";
        return $result;
    }
    
    function get_bulk_actions() {

        $error_channels = $this->get_error_channels();

        //$actions['activate'] = __('Make active','newsflow');
        //$actions['inactivate'] = __('Make inactive','newsflow');
        $actions['delete'] = __('Delete','newsflow');
        //$actions['separator'] = __('','newsflow');

        //$actions['new_link'] = __('Add new RSS Feed', 'newsflow');
        $actions['only_show'] = __('Show all sources','newsflow');
        foreach (Newsflow_Item::channels() as $key => $value) {
            if (in_array($value, $error_channels)){
                $actions[$value] .= '* ';
            }
            $actions[$value] .= __('Only show sources from channel: ','newsflow').$value;
            if (in_array($value, $error_channels)){
                $actions[$value] .= ' * ' . __(' (Contains errors!)','newsflow');
            }
        }
        return $actions;
    }

    function get_error_channels(){
        $result = array();
        $links = Newsflow_Item::get_links();
        foreach($links as $link){
            if (strpos($link->error,'NOK')===0 && !in_array($link->channel, $result)) $result[] = $link->channel;
        }
        return $result;
    }
    
    function process_bulk_action() {
        global $current_user;
        get_currentuserinfo();

        if ($this->current_action() === 'delete'){
            foreach ($_POST['link'] as $key => $value) {
                Newsflow_Item::delete($value);
            }
            wp_die('<SCRIPT> setTimeout(\'document.location="?page=newsflow_links";\',10); </SCRIPT>');
            return;
        }

        if ($this->current_action() === 'only_show'){
            update_user_meta( $current_user->ID, "hypernews_channel", NULL);
            wp_die('<SCRIPT> setTimeout(\'document.location="?page=newsflow_links";\',10); </SCRIPT>');
            return;
        }

        foreach (Newsflow_Item::channels() as $key => $value) {

            $current = $this->current_action();
            if ($current === $value){
                update_user_meta( $current_user->ID, "hypernews_channel", $value);
                wp_die('<SCRIPT> setTimeout(\'document.location="?page=newsflow_links";\',10); </SCRIPT>');
                return;
            }
        }

    }

    function extra_tablenav( $which )
    {
        global $current_user;
        get_currentuserinfo();
        if ( $which == "top" )
        {
            ?>
        <input onclick="document.location='<?php echo get_admin_url(); ?>admin.php?page=newsflow_links&id=0';" style="margin-top: 4px;" type="button" class="button-primary" title="Newsflow Fetch" target="_blank" value="<?php _e('Add new RSS feed','newsflow'); ?>" />
            &nbsp;
        <!--input type="button" class="button-secondary" title="Newsflow Fetch" target="_blank" value="<?php _e('Show all sources','newsflow'); ?>" /-->
        <?php

            $channel = get_user_meta($current_user->ID, "hypernews_channel", true);
            if (!empty($channel) && strlen($channel)>0){
                echo '<div style="color:red;display:inline-block; padding-top:7px">'.__('Filter news on channel:','newsflow').' <strong>'.$channel.'</strong></div>';
            }

            if ( isset( $_REQUEST['fetch'] ) )
            {
                $this->reload_page();
            }
        }
        if ( $which == "bottom" ){
            //The code that goes after the table is there
            echo " ";
        }
    }

    function column_cb($item)
    {
        $result = sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            $item->id);                //The value of the checkbox should be the record's id
        return $result;
    }

    function column_source($item){

        $edit_url = sprintf('?page=%1$s&id=%2$s',$_REQUEST['page'],$item->id);

            //Build row actions
        $actions = array(
            'edit'      => sprintf('<a name="%2$s" href="?page=%1$s&id=%2$s">'.__('Edit','newsflow').'</a>',$_REQUEST['page'],$item->id),
            'delete'      => sprintf('<a onclick="return confirm(\''.__('Confirm delete this source!','newsflow').'\');" href="?page=%s&id=%s&delete=true">'.__('Delete','newsflow').'</a>',$_REQUEST['page'],$item->id)
        );


        //Return the title contents
        return sprintf('<a href="%4$s" name="%2$s">%1$s</a> <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item->name,
            /*$2%s*/ $item->id,
            /*$3%s*/ $this->row_actions($actions),
            $edit_url
        );
    }
    
    function column_channel($item){
        return $item->channel;
    }

    function column_fetch_time($item){
        return $item->stats->time . ' s';
    }

    function column_fetch_index($item){
        return $item->stats->index;
    }

    function column_fetch_added($item){
        return $item->stats->added;
    }

    function column_fetch_date($item){
        return $item->stats->date;
    }

    function column_id($item){
        return $item->id;
    }

    function column_error($item){
        if (!empty($item->error)){
            $error = $item->error;

            if (substr($error, 0, 2) == "OK"){
                return '<span style="color:green">'.$error.'</span>';
            }
            else {
                return '<span style="color:red">'.$error.'</span>';
            }
        }
        else
        {
            return '';
        }
    }

    function column_status($item){

        if ($item->status=='inactive'){
            return '<img src="'.WP_PLUGIN_URL.'/newsflow/img/control_pause.png" alt="status" />';
        }

        if (!empty($item->error)){
            if (substr($item->error, 0, 2) != "OK"){
                return '<img src="'.WP_PLUGIN_URL.'/newsflow/img/exclamation.png" alt="status" />';
            }
        }

        return '<img src="'.WP_PLUGIN_URL.'/newsflow/img/tick.png" alt="status" />';
    }

    function column_url($item){
        return $item->url;
    }
    
    function column_posttypes($item){
        $result = "";
        $posttypes = $item->posttypes;
        if (is_array($posttypes)){
            foreach ($posttypes as $key => $value) {
                if (strlen($result)>0) { $result.=', '; }
                $result.=$value;
            }
        }
        return $result;
    }
    
    function column_default($item, $column_name){
        switch($column_name){
            default:
                return $item->column_name; //Show the whole array for troubleshooting purposes
        }
    }

}


?>
