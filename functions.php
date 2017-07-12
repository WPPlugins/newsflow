<?php


/**
 * Returns the users unread items in Hypernews!
 * Using cache for 1 min.
 * @global type $current_user
 * @global type $wpdb
 * @return type 
 */
function hypernews_getunread_news(){
    global $current_user;
    global $wpdb;

    $cache = get_transient( 'hypernews_cache_unread' );
    if (!$cache){
        get_currentuserinfo();    

        $cache = 0;
        $channel = get_user_meta($current_user->ID, "hypernews_channel", true);

        $table_name = Newsflow_Database::database_table_store();
        $query = "SELECT count(*) FROM ".$table_name." WHERE status='NEW'";
        if (strlen($channel)>0){
            $query.=" AND channel='".$channel."'";
        }
        $cache = $wpdb->get_var( $wpdb->prepare( $query ) );

        set_transient( 'hypernews_cache_unread', $cache, 60 );
        
    }
    
    return $cache;
}

//function hypernews_get_browse_tree(){
//
//    $treeArr = array();
//
//    $result = array();
//    $result['title'] = 'TEST';
//    $result['key'] = 1;
//    $result['tooltip'] = 'Beskrivning';
//
//    $treeArr[] = $result;
//
//    $result11 = array();
//    $result11['title'] = 'TEST 1.1';
//    $result11['key'] = 11;
//    $result11['tooltip'] = 'Beskrivning';
//
//    $result['children'][] = $result11;
//    $result['isFolder'] = true;
//
//    $treeArr[] = $result;
//
//    $result = array();
//    $result['title'] = 'TEST2';
//    $result['key'] = 2;
//    $result['tooltip'] = 'Beskrivning';
//
//    $treeArr[] = $result;
//
//    $result = json_encode($treeArr);
//    return $result;
//}

?>
