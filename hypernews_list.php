<?php

if(!class_exists('WP_List_Table'))
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * 
 */
class Hypernews_List extends WP_List_Table 
{
    private $current_link;

     function __construct() 
    {
         $this->hidden = false;
         
         parent::__construct( array(
        'singular'=> 'News', //Singular label
        'plural' => 'News', //plural label, also this well be one of the table css class
        'ajax'	=> true //We won't support Ajax for this table
        ) );
     }
     
     public function reload_page($reload = false){
        set_transient( 'hypernews_cache_unread', NULL);
        echo __('Fetching items from RSS-feeds...','newsflow').'<p><img src="'.WP_PLUGIN_URL.'/newsflow/img/ajax-loader.gif"></p><script> setTimeout(\'document.location="?page=newsflow";\',10);</script>';
        set_transient( 'hypernews_cache_unread', NULL);
        flush();

        if ($reload){
            $fetch = new Hypernews_Fetcher();
            $fetch->fetch(10);
        }

        wp_die(__('...please wait while loading news and reloading page!','newsflow'));
     }
     
     function extra_tablenav( $which ) 
     {
        global $current_user;
        get_currentuserinfo();
        if ( $which == "top" )
            {
                $channel = get_user_meta($current_user->ID, "hypernews_channel", true);
                if (!empty($channel) && strlen($channel)>0){
                    echo '<div style="display:inline-block; padding-top:7px">'.__('Filter news on channel:','newsflow').' <strong>'.$channel.'</strong></div>';
                }
            
                if ( isset( $_REQUEST['fetch'] ) )
                {
                    $this->reload_page();
                }

                ?>

                &nbsp;<div style="margin-right: 10px; margin-top: 6px; float: left;">
                    <a class="button-primary thickbox" href="/wp-admin/admin-ajax.php?action=newsflow_prepare&width=600&height=600&user_id=<?php echo $current_user->ID; ?>" title="Newsflow Fetch" target="_blank"><?php _e('Fetch news','newsflow'); ?></a>
                </div>

                <!--div style="clear:both;"></div-->
                <?php

            }
            if ( $which == "bottom" ){
                    //The code that goes after the table is there
                    echo " ";
            }
    }
    
    function get_bulk_actions() {
        $actions = array(
            //'reload' => __('Fetch latest news', 'newsflow'),
            'hide'    => __('Hide selected news','newsflow'),
            'hidden' => __('Show hidden', 'newsflow'),
            'only_show' => __('Show content in all channels','newsflow')
        );
        
        foreach (Newsflow_Item::channels() as $key => $value) {
            $actions[$value] = __('Only show content in channel: ','newsflow').$value;
        }

        $actions['all_read'] = __('Mark all items as read','newsflow');
        $actions['clean_sweep'] = __('Make a clean sweep and fetch news again','newsflow');

        return $actions;
    }
    
    function process_bulk_action() {
        global $wpdb, $current_user;
        get_currentuserinfo();
        
        if ($this->current_action() === 'reload'){
            $this->reload_page();
            return;
        }
        
        if ($this->current_action() === 'hide'){
            $table_name = Newsflow_Database::database_table_store();
            foreach ($_POST['news'] as $key => $value) {
                $wpdb->query( $wpdb->prepare( 
                "
                UPDATE $table_name
                SET status = %s
                WHERE id = %d;
                ", 
                'HIDE', 
                $value 
                ) );
            }
            return;
        }
        
        if ($this->current_action() === 'hidden'){
            $this->hidden = true;
            return;
        }

        if ($this->current_action() === 'only_show'){
            update_user_meta( $current_user->ID, "hypernews_channel", NULL);
            $this->reload_page();
            return;
        }

        if ($this->current_action() === 'clean_sweep'){

            $table_name = Newsflow_Database::database_table_store();
            $wpdb->query( $wpdb->prepare(
            "
            DELETE FROM $table_name
            "
            ) );

            $table_name = Newsflow_Database::database_table_raw();
            $wpdb->query( $wpdb->prepare(
                "
            DELETE FROM $table_name
            "
            ) );

            $this->reload_page();

        }

        if ($this->current_action() === 'all_read'){

            $table_name = Newsflow_Database::database_table_store();
            $wpdb->query( $wpdb->prepare(
            "
            UPDATE $table_name SET status='READ' WHERE status='NEW'
            "
            ) );

            set_transient( 'hypernews_cache_unread', NULL );
            hypernews_getunread_news();
            //$this->reload_page();

        }

        foreach (Newsflow_Item::channels() as $key => $value) {

            $current = $this->current_action();
            if ($current === $value){
                update_user_meta( $current_user->ID, "hypernews_channel", $value);
                $this->reload_page();
                return;
            }
        }
        
    }    

/**
 * Define the columns that are going to be used in the table
 * @return array $columns, the array of columns to use with the table
 */
function get_columns() {

    return $columns= array(
        'cb' => '<input type="checkbox" />',
        'status'=>'<img src="'.WP_PLUGIN_URL.'/newsflow/img/tag.png" />',
        'title'=>__('Headline', 'newsflow'),
        'pubdate'=>__('Published', 'newsflow'),
        'channel'=>__('Channel', 'newsflow'),
        'source'=>__('Source', 'newsflow'),
        'notes'=>__('Note', 'newsflow'),
        'link_id'=>'link_id'

    );
}
    
    /**
 * Decide which columns to activate the sorting functionality on
 * @return array $sortable, the array of columns that can be sorted by the user
 */
public function get_sortable_columns() {
	return $sortable = array(
		'channel' => array('channel',false),
		'source' => array('source',false),
		'status' => array('status',false),
		'title' => array('title',false),
                'pubdate' => array('pubdate',false)
	);
}

function get_hidden_columns(){
    return array('link_id');
}


/**
 * Prepare the table with different parameters, pagination, columns and table elements
 */
function prepare_items() {
        global $wpdb, $_wp_column_headers;
        global $current_user;
        get_currentuserinfo();
       
        $screen = get_current_screen();

        $table_name = Newsflow_Database::database_table_store();
        
        $this->process_bulk_action();
        
	/* -- Preparing your query -- */
        $query = "SELECT * FROM ".$table_name;
        
        $channel = get_user_meta($current_user->ID, "hypernews_channel",true);

        $where = "";
        if (!$this->hidden) 
        {
            if (empty($where)) $where = ' WHERE ';
            $where.='status!="HIDE" ';
        }
        if (!empty($channel) && strlen($channel)>0)
        {
            if (empty($where)) //MOABUGGEN
                $where = ' WHERE ';
            else
                $where.=' AND ';
            $where.='channel="'.$channel.'" ';
        }
        $query.= $where;
        
        
        
    /* -- Ordering parameters -- */
        
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'pubdate';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : 'desc';
        if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 100;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($perpage)){
            $offset=($paged-1)*$perpage;
        $query.=' LIMIT '.(int)$offset.','.(int)$perpage;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
                "total_items" => $totalitems,
                "total_pages" => $totalpages,
                "per_page" => $perpage,
        ) );
        //The pagination links are automatically built according to those parameters

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
        
}

    function column_cb($item)
    {
        $result = sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            $item->id);                //The value of the checkbox should be the record's id
        return $result;
    }

    function column_default($item, $column_name){
        return '';
    }

    function column_title($item){

        $actions = array(
            'edit'      => sprintf('<a href="#" class="hypernews_edit_row" row_id="%3$s">'.__('Show', 'newsflow').'</a>',$_REQUEST['page'],'edit',$item->id),
            'unread'      => sprintf('<a href="#" class="hypernews_unread_row" row_id="%3$s">'.__('Unread', 'newsflow').'</a>',$_REQUEST['page'],'unread',$item->id),
            'star' => sprintf('<a href="#" class="hypernews_star_row" row_id="%3$s">'.__('Favorite', 'newsflow').'</a>',$_REQUEST['page'],'star',$item->id),
            'hide'    => sprintf('<a href="#" class="hypernews_hide_row" row_id="%3$s">'.__('Hide', 'newsflow').'</a>',$_REQUEST['page'],'hide',$item->id)
        );
        $title_class = "hypernews_title_row";
        if ($item->status=='NEW'){
            $title_class = "hypernews_title_unread";
        }

        //Return the title contents
        $result = sprintf('<a class="%7$s" href="%6$s" target="_new">%1$s</a><br/><div class="hypernews_pre_row hypernews_row_pre_%4$s"><i>%2$s</i></div><div class="hypernews_hidden_row hypernews_row_%4$s">%5$s</div>%3$s',
            $item->title,
            substr(strip_tags($item->description),0,150),
            $this->row_actions($actions),
            $item->id,
            strip_tags($item->description),
            $item->url,
            $title_class
        );

        return $result;
    }

    function column_pubdate($item){

        $this->current_link = new Newsflow_Item($item->link_id);

        //Channels:
        $pb_result = "";
        $posttypes = $this->current_link->posttypes;

        if (!is_array($posttypes)) $posttypes = array();
        foreach ($posttypes as $type)
        {
            $posttype_object = get_post_type_object($type);
            if ($pb_result!='')
            {
                $pb_result.='&nbsp;&nbsp;&nbsp;&nbsp;';
            }

            $pb_result.='<a href="#" row_id="'.$item->id.'" posttype="'.$type.'" class="hypernews_publish_row" title="'.__('Add as draft to', 'newsflow').' '.$type.'"><span class="hypernews_publish_add">'.$posttype_object->label.'</a>';
        }
        return sprintf('%1$s<br/>'.$pb_result.'<div style="clear:both;"></div>',
            stripslashes($item->pubdate),
            $item->id
        );
    }

    function column_channel($item){
        return $item->channel;
    }

    function column_source($item){

        $name = $item->source;

        $result = '<a href="'.get_admin_url().'admin.php?page=hypernews_links&id='.$item->link_id.'">'.$name.'</a>';

        return $result;
    }

    function column_status($item){
        if ($item->status == 'NEW')
        {
            return '<img id="hypernews_row_icon_'.$item->id.'" src="'.WP_PLUGIN_URL.'/newsflow/img/lightbulb.png" />';
        }
        else if ($item->status == 'READ')
        {
            return '<img id="hypernews_row_icon_'.$item->id.'" src="'.WP_PLUGIN_URL.'/newsflow/img/lightbulb_off.png" />';
        }
        else if ($item->status == 'STAR')
        {
            return '<img id="hypernews_row_icon_'.$item->id.'" src="'.WP_PLUGIN_URL.'/newsflow/img/star.png" />';
        }
        else if ($item->status == 'HIDE')
        {
            return '<img id="hypernews_row_icon_'.$item->id.'" src="'.WP_PLUGIN_URL.'/newsflow/img/cross.png" />';
        }
        else if ($item->status == 'POST')
        {
            return '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$item->post.'&action=edit" target="_blank"><img id="hypernews_row_icon_'.$item->id.'" src="'.WP_PLUGIN_URL.'/newsflow/img/page_white_go.png" /></a>';
        }
        else
        {
            return stripslashes($item->status);
        }

    }

    function column_notes($item){
        $result = sprintf('<div id="hypernews_row_notetext_%2$s">%1$s</div><br/><div class="hypernews_hidden_row hypernews_row_%2$s"><textarea id="hypernews_row_notearea_%2$s">%1$s</textarea><br/><input type="button" value="'.__('Update', 'newsflow').'" row_id="%2$s" class="hypernews_row_note button-primary" /></div>',
            stripslashes($item->notes),
            $item->id
        );

        return $result;
    }


}


?>