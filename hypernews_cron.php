<?php

/**
 * This class handles states in the Matchmail machine...
 * It is triggered by cron job /?matchmail-cron
 */


$hypernews_cron = new Hypernews_Cron();

class Hypernews_Cron{

    function __construct() {

        //Action hooks
        add_action('init', array(&$this, 'init') );
    }

    function init()
    {
        //add_action('parse_request', array(&$this, 'requests') );
        //add_filter('query_vars', array(&$this, 'query_vars') );

        //Define and create scheduled task!
        add_action('newsflow_jobs_run',array(&$this, 'jobs'));

        add_filter('cron_schedules',array(&$this, 'recurrence'));

        if (!wp_get_schedule('newsflow_jobs_run'))
            wp_schedule_event(time(),'newsflow','newsflow_jobs_run');

    }

    function recurrence($schedules)
    {
        //Getting interval from options or setting default 60s
        $interval = get_option('newsflow_interval',60);

        //Returns a new recurrens to WP.
        $schedules['newsflow'] = array(
          'interval'=> $interval, //60s
          'display'=>  sprintf(__('Once Every %s second.','newsflow'),$interval )
        );
        return $schedules;
    }

    function jobs(){

        $this->run();

    }


    //Så att WP reagerar på extern förfrågan
    function query_vars($vars) {
        $vars[] = 'newsflow-cron';
        return $vars;
    }

    function requests($wp)
    {
        if (array_key_exists('newsflow-cron', $wp->query_vars))
        {
            header("Cache-Control: no-cache, must-revalidate");
            $this->run();
        }
    }


    function run($part=null){

        $links = Newsflow_Item::get_exec_links();
        if (is_array($links) && sizeof($links)>0){

            $count=0;
            foreach($links as $link){
                $fetch = new Hypernews_Fetcher();
                $result = $fetch->fetch($link->id);
                $count = $count + 1;

                //Break at 5
                if ($count>5) break;
            }

        }

    }

}
?>