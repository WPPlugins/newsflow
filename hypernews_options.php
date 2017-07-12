<?php



class Hypernews_Options{


    function __construct()
    {
        add_action('admin_menu', array(&$this, 'admin_menu'));
    }

    function admin_menu()
    {
        //add_submenu_page( 'newsflow', 'settings', __('Settings', 'newsflow'), 'edit_posts', 'hypernews_settings', array(&$this, 'display') );
    }

    function display()
    {
        global $wp_roles;

        if (isset($_POST['save'])){

            update_option('hypernews_timeout', esc_attr($_POST['timeout']));

            update_option('newsflow_datetime_zone', esc_attr($_POST['timezone']));

            update_option('newsflow_add_source', esc_attr($_POST['newsflow_add_source']));
            update_option('newsflow_open_blank', esc_attr($_POST['newsflow_open_blank']));

            update_option('newsflow_interval', esc_attr($_POST['interval']));

            update_option('newsflow_newscapability', esc_attr($_POST['newscapability']));
            update_option('newsflow_linkcapability', esc_attr($_POST['linkcapability']));
            update_option('newsflow_settingscapability', esc_attr($_POST['settingscapability']));

        }

        $news_capability = get_option( 'newsflow_newscapability', 'edit_posts' );
        $link_capability = get_option( 'newsflow_linkcapability', 'edit_posts' );
        $settings_capability = get_option( 'newsflow_settingscapability', 'edit_posts' );

        $timeout = get_option( 'hypernews_timeout', '10' );
        $timezone = get_option('newsflow_datetime_zone',0);

        $add_source = get_option('newsflow_add_source',0);
        $open_blank = get_option('newsflow_open_blank',0);

        $interval = get_option('newsflow_interval',60);

        echo '<div class="wrap">';
        echo '<div id="" class="icon32"><img src="'.WP_PLUGIN_URL . '/newsflow/img/feed_add_32.png" alt="hypernews_icon" /><br/></div>';

        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/newsflow/newsflow.php');

        echo '<h2>'.__('Newsflow', 'newsflow').' - ' .__('Settings','newsflow') . ' - version ' .$plugin_data['Version'].'</h2>';

        echo '<form method="post" enctype="multipart/form-data">';

        echo '<p>'.__('Interval','newsflow').':<br/><input size="2" type="text" value="'.$interval.'" name="interval" /> s. '.__('for WP-Cron action.','newsflow').'</p>';

        echo '<p>'.__('Timeout','newsflow').':<br/><input size="2" type="text" value="'.$timeout.'" name="timeout" /> s. '.__('for each fetch.','newsflow').'</p>';

        //echo 'Link settings file:<br/><input type="file" name="file"/></p>';

        $list = DateTimeZone::listAbbreviations();
        $idents = DateTimeZone::listIdentifiers();

        $data = $offset = $added = array();
        foreach ($list as $abbr => $info) {
            foreach ($info as $zone) {
                if ( ! empty($zone['timezone_id'])
                    AND
                    ! in_array($zone['timezone_id'], $added)
                        AND
                        in_array($zone['timezone_id'], $idents)) {
                    $z = new DateTimeZone($zone['timezone_id']);
                    $c = new DateTime(null, $z);
                    $zone['time'] = $c->format('H:i a');
                    $data[] = $zone;
                    $offset[] = $z->getOffset($c);
                    $added[] = $zone['timezone_id'];
                }
            }
        }

        array_multisort($offset, SORT_ASC, $data);
        $options = array();
        foreach ($data as $key => $row) {
            $options[$row['timezone_id']] = $row['time'] . ' - '
                . $this->formatOffset($row['offset'])
                . ' ' . $row['timezone_id'];
        }

        echo '<p>Timezone:<br/>';
        echo '<select name="timezone">';
        foreach($options as $key => $option){
            echo '<option value="'.$key.'" ';
            if ($timezone == $key) echo "selected";
            echo '>'.$option.'</option>';
        }
        echo '</select></p>';

        echo '<p><input type="checkbox" name="newsflow_add_source" value="1" '.checked($add_source,1,false).'/> '.__('Add source link to posts','newsflow').'</p>';
        echo '<p><input type="checkbox" name="newsflow_open_blank" value="1" '.checked($open_blank,1,false).'/> '.__('Open post links in new window (add _blank to target)','newsflow').'</p>';

        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles();

        $caps = array();
        foreach($wp_roles->roles as $role){
            foreach($role['capabilities'] as $key => $cap){
                if (!in_array($key, $caps)) {
                    $caps[] = $key;
                }
            }
        }

        echo '<p>'.__('Manage Newsflow capability','newsflow').':<br/>';
        echo '<select name="newscapability">';
        foreach($caps as $cap){
            echo '<option value="'.$cap.'"';
            if ( $news_capability == $cap ) echo ' selected="selected"';
            echo '">'.$cap.'</option>';
        }
        echo '</select>';

        echo '<p>'.__('Manage RSS Feeds capability','newsflow').':<br/>';
        echo '<select name="linkcapability">';
        foreach($caps as $cap){
            echo '<option value="'.$cap.'"';
            if ( $link_capability == $cap ) echo ' selected="selected"';
            echo '">'.$cap.'</option>';
        }
        echo '</select>';

        echo '<p>'.__('Manage Settings capability','newsflow').':<br/>';
        echo '<select name="settingscapability">';
        foreach($caps as $cap){
            echo '<option value="'.$cap.'"';
            if ( $settings_capability == $cap ) echo ' selected="selected"';
            echo '">'.$cap.'</option>';
        }
        echo '</select>';

        echo '</p>';

        echo '<p>&nbsp;</p>';

        echo '<input type="hidden" name="save" value="save"/>';
        echo '<input type="submit" name="save" class="button-primary" value="'. __('Save','newsflow') .'" />';
        //echo '<input type="button" onclick="location.href=\''.get_bloginfo('url').'?hypernews-links\'" name="options" class="button-secondary" value="'. __('Get Link Settings','newsflow') .'" />';

        echo '</form>';


        echo '</div>';
    }

    function formatOffset($offset) {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);

        if ($hour == 0 AND $minutes == 0) {
            $sign = ' ';
        }
        return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT)
            .':'. str_pad($minutes,2, '0');

    }


}

?>