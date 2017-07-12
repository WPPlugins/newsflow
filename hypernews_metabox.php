<?php
/**
 * Creates and handles metabox in editor post view.
 * Andreas Ek, 2012-01-11, created
 * 
 */
class Hypernews_Metabox 
{

    public function __construct()
    {
        add_action( 'admin_init', array( &$this, 'init' ) );
    }
    
    public function init()
    {
        // review the function reference for parameter details
        // http://codex.wordpress.org/Function_Reference/add_meta_box

        $posttypes = array();

        // add a meta box for each of the wordpress page types: posts and pages
        $links = Newsflow_Item::get_links();
        foreach($links as $link){
            if (is_array($link->posttypes)){
                foreach($link->posttypes as $posttype){
                    if (!in_array($posttype, $posttypes)) $posttypes[] = $posttype;
                }
            }
        }

        if (!is_array($posttypes) || sizeof($posttypes)<1) {
            $posttypes = array();
        }
        foreach ($posttypes as $type) 
        {
            add_meta_box('hypernews_metabox', 'Newsflow', array( &$this, 'setup'), $type, 'normal', 'high');
        }

        // add a callback function to save any data a user enters in
        // NOT NOW! add_action('save_post', array( &$this, 'save'));
    }


    public function setup()
    {
        global $post;
        $meta = get_post_meta($post->ID,'hypernews_metabox',TRUE);
        ?>
        <div class="hypernews_meta_control">
            <?php 
                echo _e('Imported from RSS-feed:','newsflow').'&nbsp;';
                echo '<a href="'.$meta['feed'].'" target="_new">'.$meta['source'].'</a><br/>';
                echo _e('Original newslink:','newsflow').'&nbsp'.'<a href="'.$meta['url'].'" target="_new">'.$meta['title'].'</a>';
            ?>
        </div>
        <?php
        
        if (!$meta['url']){
           ?>
           <script> jQuery(document).ready( function($) { $('#hypernews_metabox').hide();  } ); </script>
           <?php
        }

        // create a custom nonce for submit verification later
        echo '<input type="hidden" name="hypernews_metabox_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
    }

    public function save($post_id) 
    {
        global $post;
        // authentication checks
        // make sure data came from our meta box
        if (!wp_verify_nonce($_POST['hypernews_metabox_noncename'],__FILE__)) return $post_id;

        if (!current_user_can('edit_page', $post_id)) return $post_id;

	$current_data = get_post_meta($post_id, 'hypernews_metabox', TRUE);	
 
	$new_data = $_POST['hypernews_metabox'];
 
	$this->clean($new_data);
 
	if ($current_data) 
	{
		if (is_null($new_data)) delete_post_meta($post_id,'hypernews_metabox');
		else update_post_meta($post_id,'hypernews_metabox',$new_data);
	}
	elseif (!is_null($new_data))
	{
		add_post_meta($post_id,'hypernews_metabox',$new_data,TRUE);
	}
 
	return $post_id;
    }

    function clean(&$arr)
    {
            if (is_array($arr))
            {
                    foreach ($arr as $i => $v)
                    {
                            if (is_array($arr[$i])) 
                            {
                                    clean($arr[$i]);

                                    if (!count($arr[$i])) 
                                    {
                                            unset($arr[$i]);
                                    }
                            }
                            else 
                            {
                                    if (trim($arr[$i]) == '') 
                                    {
                                            unset($arr[$i]);
                                    }
                            }
                    }

                    if (!count($arr)) 
                    {
                            $arr = NULL;
                    }
            }
    }
    
    
    
    
} //end of class


?>
