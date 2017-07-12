<?php
/*
Plugin Name: Newsflow
Plugin URI: http://wordpress.org/extend/plugins/newsflow
Description: Editorial support, very fast user interface to manually select and publish RSS streams to your WordPress site/blog.
Version: 1.9.11
Author: EkAndreas, Flowcom AB
Author URI: http://www.flowcom.se
License: GPLv2
*/

/**
 * Localize plugin
*/
load_plugin_textdomain( 'newsflow', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
include_once 'includes/item.class.php';

//include_once(ABSPATH . WPINC . '/feed.php');

include_once 'includes/newsflow_database.php';

include_once('hypernews_fetcher.php');
include_once('hypernews_list.php');
include_once('hypernews_ajax.php');
include_once('newsflow_links.php');
include_once('hypernews_browse.php');
include_once('functions.php');
include_once 'hypernews_metabox.php';
include_once 'hypernews_cron.php';
include_once 'hypernews_options.php';

register_activation_hook( __FILE__, array(new Newsflow_Database(), 'install') );
register_deactivation_hook(__FILE__, array(new Newsflow_Database(), 'uninstall'));

$metabox = new Hypernews_Metabox();

$hypernews_options = new Hypernews_Options();

add_action('wp_ajax_newsflow_test', 'newsflow_test');
add_action('wp_ajax_newsflow_clear', 'newsflow_clear');

add_action('wp_ajax_newsflow_fetch', 'newsflow_fetch_ajax');
function newsflow_fetch_ajax(){
    $link_id = $_REQUEST['link_id'];
    $fetch = new Hypernews_Fetcher();
    $fetch->fetch($link_id,false);
    wp_die();
}
add_action('wp_ajax_newsflow_prepare', 'newsflow_prepare_callback');

add_action('admin_menu', 'hn_add_menu');
function hn_add_menu()
{
    global $current_user;

    $news_capability = get_option( 'newsflow_newscapability', 'edit_posts' );

    if (!user_can($current_user->ID, $news_capability)) return;

    $link_capability = get_option( 'newsflow_linkcapability', 'edit_posts' );
    $settings_capability = get_option( 'newsflow_settingscapability', 'edit_posts' );

    $unread = hypernews_getunread_news();

    $unread_text = "";
    if ($unread>0){
        $unread_text = '&nbsp;<span style="font-size:10px;background-color:#FF3300;color:#fff;margin:2px;padding:2px;-moz-border-radius:15px;-webkit-border-radius:15px;">&nbsp;'.$unread.'&nbsp;</span>';
    }

    add_menu_page( __('Newsflow','newsflow'), __('Newsflow','newsflow').$unread_text, $news_capability, 'newsflow', 'newsflow_main', WP_PLUGIN_URL.'/newsflow/img/feed_add_16.png' );
    add_submenu_page( 'newsflow', 'Newsflow Links', __('RSS Feeds', 'newsflow'), $link_capability, 'newsflow_links', 'newsflow_links' );
    add_submenu_page( 'newsflow', 'Newsflow Browse', __('Browse List', 'newsflow'), $link_capability, 'hypernews_browse', 'hypernews_browse' );
    add_submenu_page( 'newsflow', 'Settings', __('Settings', 'newsflow'), $settings_capability, 'hypernews_settings', 'newsflow_options_page' );

}

function newsflow_options_page(){
    $hypernews_options = new Hypernews_Options();
    $hypernews_options->display();
}


/*
 * REGISTER HEADER
 * Javascript, CSS
 */
add_action('admin_head', 'admin_register_head');
function admin_register_head() {
    $siteurl = get_option('siteurl');
    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/css/newsflow.css';
    $js_url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/js/newsflow.js';
    echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
//    echo "<script type='text/javascript' src='$js_url'></script>";
    
}

add_action('admin_enqueue_scripts', 'newsflow_script');
function newsflow_script($hook) {
    
    if (strpos($hook, 'newsflow')){
        wp_enqueue_script("jquery");
        wp_enqueue_script("jquery-ui-core");

        $siteurl = get_option('siteurl');
        $tree_url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/css/skin-vista/ui.dynatree.css';
        wp_enqueue_style('hn_dynatree_css', $tree_url);


        //wp_enqueue_script("jquery-ui-effects");
        //We really need the effects in jquery-ui!
        wp_enqueue_script("jquery-ui","https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js");

        $src = WP_PLUGIN_URL . '/newsflow/js/newsflow.js';
        wp_deregister_script('newsflowAjax');
        wp_register_script('newsflowAjax', $src);
        wp_enqueue_script('newsflowAjax');
        wp_localize_script('newsflowAjax','newsflowAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

        $myScriptUrl2 = WP_PLUGIN_URL . '/newsflow/js/jquery.ui.widget.js';
        wp_deregister_script( 'uiwidget' );
        wp_register_script( 'uiwidget', $myScriptUrl2,'query-ui-core');
        wp_enqueue_script( 'uiwidget' );

        // Main jQuery
        $src = WP_PLUGIN_URL . '/newsflow/js/browsertree.js';
        wp_deregister_script('hn_tree');
        wp_register_script('hn_tree', $src);
        wp_enqueue_script('hn_tree');
        wp_localize_script( 'hn_tree', 'hypernews_dynatree_data',  hypernews_get_browse_tree() );

        // Dynatree
        wp_deregister_script('hn_dynatree');
        $dyn_src = WP_PLUGIN_URL . '/newsflow/js/dynatree/jquery.dynatree.min.js';
        wp_register_script('hn_dynatree', $dyn_src);
        wp_enqueue_script('hn_dynatree');

        // jQuery Cookie
        wp_deregister_script('hn_jqcookie');
        $cookie_src = WP_PLUGIN_URL . '/newsflow/js/dynatree/jquery.cookie.js';
        wp_register_script('hn_jqcookie', $cookie_src);
        wp_enqueue_script('hn_jqcookie');


    }
}

function newsflow_main()
{
    global $current_user;
    get_currentuserinfo();

    wp_enqueue_script('thickbox',null,array('jquery'));
    wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');

    echo '<div class="wrap">';
    echo '<div id="" class="icon32"><img src="'.WP_PLUGIN_URL . '/newsflow/img/feed_add_32.png" alt="hypernews_icon" /><br/></div>';

    //$plugin_data = get_plugin_data(__FILE__);

    echo '<h2>'.__('Newsflow', 'newsflow').'</h2>'; //.' - <a style="text-decoration:underline;" href="/wp-admin/admin-ajax.php?action=newsflow_prepare&width=600&height=600&user_id='.$current_user->ID.'" class="thickbox" title="Newsflow Fetch" target="_blank">'.__('fetch feeds now!','newsflow').'</a></h2>';

    echo '<form method="post">';
    
    //Prepare Table of elements
    $rss_list = new Hypernews_List();
    $rss_list->prepare_items();
    $rss_list->display();
    
    echo '</form>';
 
    
    echo '</div>';
}

?>