<?php


function hypernews_browse(){
    global $wpdb;
    global $current_user;
    get_currentuserinfo();

    $current_browser = new Newsflow_Item(0);
    
    $browser_id = 0;
    if (isset($_GET['id'])){
        
        if (isset($_REQUEST['delete'])){
            Newsflow_Item::delete(((int)esc_attr($_GET['id'])));
            echo '<script>document.location="?page=hypernews_browse&edit=true";</script>';
        }
        else{
            $current_browser = new Newsflow_Item(((int)esc_attr($_GET['id'])));
            //$browse_id = $current_browser['id'];
        }
        
    }
    
    if (isset($_POST['id'])){
        //SPARA
        $current_browser->name = esc_attr($_POST['source']);
        $current_browser->parent = esc_attr($_POST['parent']);
        $current_browser->url = $_POST['url'];
        $current_browser->set_type_browse();
        $current_browser->save();
        echo '<script>document.location="?page=hypernews_browse&edit=true";</script>';

    }

    if (isset($_POST['selected'])){

        hypernews_run_selected($_POST['selected']);

    }

    
?>
    <div class="wrap">
        <div id="icon-link-manager" class="icon32"><br/></div><h2><?php _e('Newsflow', 'newsflow'); echo ' - '; _e('Browse List', 'newsflow'); ?></h2>
        <form method="post">
        <?php
        if (isset($_GET['id'])) {
            ?>
            <h3><?php _e('Edit browser source','newsflow'); ?></h3>
                <p>
                    <?php _e('Browser name','newsflow'); ?>:<br/>
                    <input type="text" name="source" value="<?php echo $current_browser->name; ?>" id="hypernews_name" size="50" />
                </p>
                <p>
                    <?php _e('Parent','newsflow'); ?>:<br/>
                    <select name="parent">
                        <option value=""></option>
                        <?php

                            $browsers = Newsflow_Item::browsers();
                            foreach ($browsers as $key => $value) {
                                echo '<option value="'.$value->id.'" ';
                                if ($current_browser->parent==$value->id) echo 'selected ';
                                echo '>'.$value->name.'</option>';
                            }

                        ?>
                    </select>
                </p>
                <p>
                    <?php _e('Url','newsflow'); ?>:<br/>
                    <input type="text" name="url" value="<?php echo $current_browser->url; ?>" size="50" />
                </p>
                <p>
                    <input type="hidden" name="save" value="true" />
                    <input type="hidden" name="id" value="<?php echo $_GET["id"]; ?>" />
                    <input type="submit" name="save" class="button-primary" value="<?php _e('Save','newsflow') ?>" />
                    <input type="button" class="button-secondary" value="<?php _e('Cancel','newsflow') ?>" onclick="document.location='?page=hypernews_browse&edit=true';" />
                </p>
        <?php

        }
        else if (isset($_GET['edit']))
        {
            $list = new Hypernews_Browse();
            $list->prepare_items();
            $list->display();
        }
        else {?>
            <br/>
            <input type="submit" class="button-primary" value="<?php _e('Run selected','newsflow') ?>"  />
            <input type="hidden" name="page" value="hypernews_browse" />
            <input type="button" class="button-secondary" value="<?php _e('Settings','newsflow') ?>" onclick="document.location='?page=hypernews_browse&edit=true';" />
            <input type="input" style="display:none;" value="<?php echo $_POST["selected"]; ?>" name="selected" id="hypernews_browse_selected" />
            <br/><br/>
            <div id="hypernews_browse_tree"> </div>
            <?php
        }
        
        ?>
            
        </form>        
        
    </div>
<?php
}

function hypernews_get_browse_tree() {

    $treeArr = array();

    $browsers = Newsflow_Item::browsers();
    foreach ($browsers as $key => $value) {

        if (!$value->parent) //Only parents
        {
            $treeArr[] = hypernews_get_browse_tree_recursive($value);
        }
    }

    $result = json_encode($treeArr);
    return $result;
}

function hypernews_get_browse_tree_recursive($current) {

    $result = array();

    $browsers = Newsflow_Item::browsers();
    foreach ($browsers as $key => $value) {
        if ($current->id == $value->parent){
            $result['children'][] = hypernews_get_browse_tree_recursive($value);
            $result['isFolder'] = true;
        }
    }

    $result['title'] = $current->name;
    $result['key'] = $current->id;
    $result['tooltip'] = $current->url;

    return $result;

}

function hypernews_run_selected($selected) {

    $browsers = Newsflow_Item::browsers();
    foreach ($browsers as $key => $value) {

        if ($value->id==$selected) //Only selected
        {
            hypernews_run_selected_recursive($value, 25);
        }
    }
}

function hypernews_run_selected_recursive($current, $i) {

    if ($current->url) {
        ?>

        <script>
            jQuery(document).ready(function() {
                window.open('<?php echo $current->url; ?>','','top=<?php echo $i; ?>,left=<?php echo $i; ?>,width=800,height=600,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes,resizable=yes');
            });
        </script>

    <?php
    }

    $browsers = Newsflow_Item::browsers();
    foreach ($browsers as $key => $value) {
        if ($current->id == $value->parent){
            $i=$i+25;
            hypernews_run_selected_recursive($value, $i);
        }
    }

}




if(!class_exists('WP_List_Table'))
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Hypernews_Browse extends WP_List_Table {
    
    function __construct() 
    {
        global $status, $page;
         parent::__construct( array(
            'singular'=> 'Browser', //Singular label
            'plural' => 'Browsers', //plural label, also this well be one of the table css class
            'ajax'	=> false //We won't support Ajax for this table
            ) );
    }    
    
    function prepare_items() {
        global $_wp_column_headers;
       
        $screen = get_current_screen();

        $this->process_bulk_action();

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        /* -- Fetch the items -- */
        $this->items = Newsflow_Item::browsers();
        
    }
    
    function get_bulk_actions() {
        
        $actions['new_browser'] = __('Add new browser-url', 'newsflow');
        
        //foreach ($this->channels() as $key => $value) {
        //    $actions[$value] = __('Start browsing: ','newsflow').$value;
        //}
        
        return $actions;
    }
    
    function process_bulk_action() {

        if ($this->current_action() === 'new_browser'){
            echo '<SCRIPT> document.location="?page=hypernews_browse&id=0"; </SCRIPT>';
            return;
        }

    }
    
    function channels()
    {
        $result= array();
        $settings = new Newsflow_Settings();
        $browsers = $settings->browsers();
        foreach ($browsers as $key => $value) {
            if (!in_array($value['channel'], $result)) $result[] = $value['channel'];
        }
        return $result;
    }    
    
    function get_columns(){
        $columns = array(
            'id' => 'Id',
            'source'     => __('Source','newsflow'),
            'parent'    => __('Parent','newsflow'),
            'url'  => __('Url','newsflow'),
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        return array();
    }
    
    function get_hidden_columns() {
        $result = array();
        $result[] = "id";
        return $result;
    }
    
    function extra_tablenav( $which ) 
    {
        if ( $which == "top" )
        {
        }
        if ( $which == "bottom" ){
        }
    }

    function column_source($item){
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%1$s&id=%2$s">'.__('Edit','newsflow').'</a>',$_REQUEST['page'],$item->id),
            'delete'      => sprintf('<a onclick="return confirm(\''.__('Confirm delete this source!','newsflow').'\');" href="?page=%s&id=%s&delete=true">'.__('Delete','newsflow').'</a>',$_REQUEST['page'],$item->id)
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item->name,
            /*$2%s*/ $item->id,
            /*$3%s*/ $this->row_actions($actions)
        );
    }
    
    function column_parent($item){

        $browsers = Newsflow_Item::browsers();
        foreach ($browsers as $key => $value) {
            if ($item->parent==$value->id) return $value->name;
        }
    }
    
    function column_id($item){
        return $item->id;
    }
    
    function column_url($item){
        return $item->url;
    }
    
    function column_default($item, $column_name){
        switch($column_name){
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
}


?>
