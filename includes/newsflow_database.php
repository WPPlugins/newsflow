<?php
/*
 * INSERT CUSTOM TABLE FOR PLUGIN
 */

class Newsflow_Database
{
    public static function database_table_store(){
        global $wpdb;
        return $wpdb->prefix . "newsflow_store";
    }

    public static function database_table_raw(){
        global $wpdb;
        return $wpdb->prefix . "newsflow_raw";
    }

    public static function database_table_link(){
        global $wpdb;
        return $wpdb->prefix . "newsflow_link";
    }

    public function uninstall()
    {
        global $wpdb;

        $table_name = Newsflow_Database::database_table_raw();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        $table_name = Newsflow_Database::database_table_store();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        delete_option('newsflow_db');
        delete_option('hypernews-settings');
        delete_option('hypernews_timeout');
        delete_option('newsflow_datetime_zone');
        delete_option('newsflow_category_link_id');
        delete_option('newsflow_category_browse_id');

        delete_option('newsflow_add_source');
        delete_option('newsflow_open_blank');

        delete_option('newsflow_interval');

        delete_option('newsflow_newscapability');
        delete_option('newsflow_linkcapability');
        delete_option('newsflow_settingscapability');

    }

    public function install()
    {
        global $wpdb;

        $category_id = Newsflow_Item::get_category_link();
        $category_id = Newsflow_Item::get_category_browse();

        //set table structure version
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/newsflow/newsflow.php');
        $nf_db_version = $plugin_data['Version'];
        
        if (get_option('newsflow_db')!=$nf_db_version)
        {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $table_name = Newsflow_Database::database_table_store();
            //$wpdb->query("DROP TABLE IF EXISTS $table_name");
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $sql = "CREATE TABLE ".$table_name." (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        title text NOT NULL,
                        url text NOT NULL,
                        link_id VARCHAR(255),
                        channel VARCHAR(255),
                        source VARCHAR(255),
                        description text,
                        pubdate DATETIME NOT NULL,
                        guid VARCHAR(512),
                        status VARCHAR(15),
                        post mediumint(9),
                        posturl VARCHAR(255),
                        postedby mediumint(9),
                        notes text,
                        updated timestamp,
                        PRIMARY KEY (id) );";
                dbDelta($sql);
            }

            $table_name = Newsflow_Database::database_table_raw();
            //$wpdb->query("DROP TABLE IF EXISTS $table_name");
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $sql = "CREATE TABLE ".$table_name." (
                        `guid` varchar(256) NOT NULL,
                        `link_id` varchar(255) DEFAULT NULL,
                        `title` varchar(255) DEFAULT NULL,
                        `link` varchar(255) DEFAULT NULL,
                        `description` text,
                        `content` varchar(5000) DEFAULT NULL,
                        `pubdate` datetime NOT NULL,
                        `stored` bit(1) NOT NULL DEFAULT b'0',
                        `matched` bit(1) NOT NULL DEFAULT b'0',
                        `simdate` varchar(32) NULL,
                        `system` varchar(255) DEFAULT NULL,
                        UNIQUE KEY `guid` (`guid`)
                        );";
                dbDelta($sql);
            }

            $table_name = Newsflow_Database::database_table_link();
            //$wpdb->query("DROP TABLE IF EXISTS $table_name");
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $sql = "CREATE TABLE ".$table_name." (
                        `id` INT NOT NULL AUTO_INCREMENT ,
                        `name` VARCHAR(128) NULL ,
                        `type` VARCHAR(45) NOT NULL DEFAULT 'LINK' ,
                        `url` VARCHAR(255) NULL ,
                        `meta` TEXT NULL ,
                        `updated` TIMESTAMP NULL ,
                        `executed` TIMESTAMP NULL ,
                        PRIMARY KEY (`id`) );";
                dbDelta($sql);
            }

            //Save the table structure version number
            add_option('newsflow_db', $nf_db_version);
        }
    }
}


?>