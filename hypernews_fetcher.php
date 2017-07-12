<?php

add_action('newsflow_fetch', 'newsflow_fetch_callback',10,2);
function newsflow_fetch_callback($link_id,$show_all=false){
    $fetch = new Hypernews_Fetcher();
    $fetch->fetch($link_id,$show_all);
}

add_action('newsflow_prepare', 'newsflow_prepare_callback');
function newsflow_prepare_callback(){
    $fetch = new Hypernews_Fetcher();
    $fetch->prepare();
    die();
}

add_filter('newsflow_fetch_span','newsflow_fetch_span_callback');
function newsflow_fetch_span_callback(){
    $fetch = new Hypernews_Fetcher();
    return $fetch->link_span();
}

class Hypernews_Fetcher
{

    function prepare(){

        echo '<h1><img src="'.WP_PLUGIN_URL . '/newsflow/img/feed_add_32.png" alt="hypernews_icon" /> Newsflow update!</h1>';
        echo '<input onclick="jQuery(this).hide(); newsflow_continue=0; jQuery(\'.newsflow_ajax_loader\').hide(); return false;" type="button" class="primary-button" value="'.__('Stop update','newsflow').'"/>';
        echo '<br/>';
        echo '<br/>';
        $links = apply_filters('newsflow_fetch_span',null);

        foreach($links as $link){
            ?>
            <div class="row_update_<?php echo $link->id; ?>" xmlns="http://www.w3.org/1999/html">
                <img style="padding-right:4px;" class="newsflow_ajax_loader newsflow_ajax_loader_<?php echo $link->id; ?>" src="<?php echo WP_PLUGIN_URL; ?>/newsflow/img/ajax-loader-small.gif" alt="ajax-loader" /><?php echo $link->name; ?> (<?php echo $link->channel ?>)
            </div>
            <?php
            if (!empty($js)) $js .= ",";
            $js .= '"'.$link->id.'"';
        }

        echo '<script type="text/javascript">'. "\n";

        echo 'var newsflow_continue=1;  '. "\n";
        echo '  '. "\n";
        echo 'jQuery(document).ready(function() {'. "\n";
        echo '  var links=new Array('.$js.');'. "\n";
        echo '  setTimeout(\'do_newsfeed_fetch(new Array('.$js.'));\',2000);'. "\n";
        echo '  '. "\n";
        echo '  '. "\n";
        echo '});'. "\n";

        echo '//Recursive ajax requests'. "\n";
        echo 'function do_newsfeed_fetch(links) {' . "\n";
        echo '    //console.log(links.length);' . "\n";
        echo '    if (links.length>0) {' . "\n";
        echo '      //grab the first one!'. "\n";
        echo '      var link_id = links[0];'. "\n";
        echo '      '. "\n";
        echo '      var data = {' . "\n";
        echo '          action: \'newsflow_fetch\',' . "\n";
        echo '          link_id: link_id' . "\n";
        echo '      };' . "\n";
        echo '      var url = \''.get_bloginfo('url').'/wp-admin/admin-ajax.php\';' . "\n";
        echo '      jQuery.ajax({' . "\n";
        echo '         type: "POST",' . "\n";
        echo '         cache: false,' . "\n";
        echo '         async: false,' . "\n";
        echo '         url: url,' . "\n";
        echo '         beforeSend: function() {' . "\n";
        echo '             timeout = window.setTimeout(function() {' . "\n";
        echo '                timeout = null;' . "\n";
        echo '                jQuery(\'.newsflow_ajax_loader_\'+link_id).show();' . "\n";
        echo '             }, 100);' . "\n";
        echo '         },' . "\n";
        echo '         data: data' . "\n";
        echo '      }).done(function( msg ) {' . "\n";
        echo '         jQuery(\'.row_update_\'+link_id).html(msg+\'<hr/>\');' . "\n";
        echo '         jQuery(\'.row_update_\'+link_id).delay(3000).slideUp(1000);' . "\n";
        echo '      });' . "\n";

        //echo '      jQuery.ajaxSetup({async:false,cache:false});' . "\n";
        //echo '      jQuery(\'.row_update_\'+link_id).load(url);' . "\n";
        echo '      ' . "\n";
        //echo '      jQuery.post(url, data, function(response) {' . "\n";
        //echo '         jQuery(\'.row_update_\'+link_id).html(response);' . "\n";
        //echo '         jQuery(\'.row_update_\'+link_id).delay(5000).slideUp(1000);' . "\n";
        //echo '      });' . "\n";
        echo '      ' . "\n";
        echo '      links.shift();'. "\n";
        echo '      if (typeof(links) === \'undefined\') newsflow_continue=false; '. "\n";
        echo '//console.log(links.length);' . "\n";
        echo '      if (newsflow_continue && links.length>1) '. "\n";
        echo '         setTimeout(\'do_newsfeed_fetch(new Array(\'+links.join()+\'));\',1000);'. "\n";
        echo '      else ' . "\n";
        echo '        setTimeout(\'parent.location.reload(1);\',1000);'. "\n";
        echo '    }'. "\n";
        echo '    else ' . "\n";
        echo '      setTimeout(\'parent.location.reload(1);\',3000);'. "\n";
        echo '    '. "\n";
        echo '}'. "\n";

        echo '</script>'. "\n";

        echo '<p>' . __('This page will close and refresh startpage as it is done, please wait!','newsflow') . '</p>';

        return;
    }

    public function fetch($link_id=null,$show_all=false)
    {

        //ini_set('max_execution_time', 3600); //300 seconds = 5 minutes
        //set_time_limit(3600);

        $total_added = 0;
        $total_items = 0;
        $count = 0;

        $links = $this->link_span($link_id);

        // Loop through each bookmark and print formatted output
        foreach ( $links as $link )
        {

            echo 'Newsflow-> Running: ' . $link->name . ' ('.$link->channel.') - ';

            $result = $link->fetch();

            if ($result){
                echo 'Found: '.$link->stats['index'].', Added new: '.$link->stats['added'].', Time: '.$link->stats['time'].' s<br/>';

                if ($show_all){
                    echo __('Matched news:','newsflow').'<br/>';
                    $link->match();

                    echo __('Mismatch:','newsflow').'<br/>';
                    $link->missmatch();
                    echo '<br/>';

                }

            }
            else{

                if ($link->status == 'inactive'){
                    echo '<span style="color:red;">' . __('Inactive','newsflow') . '!</span> <br/>';
                }
                else{
                    echo 'Error: '.$link->error.'<br/>';
                }
            }

            $count++;

        } //for each

        echo 'Newsflow-> Total new news: ' . $total_added . '<br/>';


    }

    function filter_handler( $seconds )
    {
      // change the default feed cache recreation period to 2 hours
      return 0;
    }

    function do_not_cache_feeds(&$feed) {
    	$feed->enable_cache(false);
    }

    function link_span($link_id=null, $exec=null){

        global $current_user; get_currentuserinfo(); // get current user info

        $links = array();

        if ($link_id){
            $links[] = new Newsflow_Item($link_id);
            return $links;
        }

        $user_id = isset($_REQUEST['user_id']) ? ((int)esc_attr($_REQUEST['user_id'])) : $current_user->ID;

        $channel = get_user_meta($user_id, "hypernews_channel", true);

        if ($exec){
            $links = Newsflow_Item::get_exec_links();
        }
        else{
            $links = Newsflow_Item::get_links();
        }

        //Remove if channel choosen
        if ($channel && strlen($channel)>0){
            foreach ($links as $key => $value) {
                if ($value->channel!=$channel){
                    unset($links[$key]);
                }
            }
        }

        //sort link for latest execution time
        function sort_links($a, $b)
        {
            return strtotime($a->stats->date) > strtotime($b->stats->date);
        }
        usort($links, 'sort_links');

        //if nobody, then just 5.
        if (!$user_id){
            $new_links = array();
            foreach($links as $key => $link){
                if ($key>5) break;
                $new_links[] = $link;
            }
            $links = $new_links;
        }

        return $links;

    }

}

?>