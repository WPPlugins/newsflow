=== Newsflow ===
Contributors: EkAndreas 
Tags: news,rss,flow,monitor,publish,editorial,cms
Requires at least: 3.2
Tested up to: 3.4.2
Stable tag: 1.9.11
License: GPLv2

Editorial support, very fast user interface to manually select and publish RSS feeds to your WordPress site.

== Description ==

Editorial support, very fast user interface to manually select and publish RSS feeds to your WordPress site.

1. Add one or more RSS feeds to Newsflow.
2. Fetch (auto via wp-cron) or manually on page via ajax.
3. Read and mark news items from the feeds.
4. Publish them as draft to you post, page or any custom post in your CMS.
5. Keep track of new items in the flow.

Add links with the Link-management, a sub menu under Newsflow.

Your Newsflow ui will now fetch and list your feeds. Keep track of which items is read and mark them as favorites or hide them. Or you can make a note on the item row.

If you want to edit them in Wordpress click on the post type links at each item row. The content will be saved as draft posts for later edit.

[youtube http://www.youtube.com/watch?v=Lv-8Zsongbc]

Newsflow is tested with/without network installation and seems to work fine.

Primary language is Swedish so please support me with correct language updates if you find this plugin useful!

Please contact us at Twitter account [@EkAndreas https://twitter.com/ekandreas] with questions and request of features!

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

Add RSS-source to Links.

Choose your settings in the Newsflow settings page.

Max Chars is how many chars to show in each item description.

"Publish to" is possible places to put the content. You can choose custom post types if you have any.

Now reload the entry page and start working with your RSS-streams as news to your site/blog.

Please contact us at Twitter account @EkAndreas with questions and request of features!

To use the data in the post metabox for developers: Post meta name is 'newsflow_metabox' with an array of data.

Your RSS fetch will take use of WordPress Cron Jobs.


== Screenshots ==

1. Download the plugin as usual from the official WordPress repository. Activate the Newsflow plugin!
2. Reload the page at Newsflow start.
3. If you would like to save one RSS-item into a post type then click the link in the Published column at the row.
4. Your new news item will be saved in draft mode only.
5. Click the icon to get to the edit page or post.
6. This is how it works.

== Upgrade Notice ==
This version is brand new from former Hypernews so it's a complete new installation.

== Frequently Asked Questions ==
None yet!

== Changelog ==
= 1.9.11 =
* Back to feed in SimplePie

= 1.9.10 =
* wp_remote instead of fetch feed
* Name in menues changed

= 1.9.9 =
* Test and clear optimized
* Status icons in links added to links
* Active and inactive status added to links
* Cron changed, 5 feeds every cron job added
* Swedish lang added

= 1.9.8 =
* Forcing feeds and retry if invalid tokens

= 1.9.7 =
* Capabilities added, change them in the settings! Default as earlier versions of Newsflow.

= 1.9.6 =
* Added filter functions to feed reader avoiding invalid tokens in feed.

= 1.9.5 =
* Prepare leaving Links in WP due to obselete
* Fixed error when fetching long list of feeds
* Didn't save fetch date when error, fixed

= 1.9.4 =
* Link to source in list fixed

= 1.9.3 =
* WP-Cron-error is now fixed
* Buttons instead of bulk action (new rss)
* Some bug fix in fetch
* Closing fetch-window when done

= 1.9.2 =
* Hide items in newslist fixed

= 1.9.1 =
* Channel name didn't show in links list
* Getting feeds empty adds empty text

= 1.9 =
* Some minor setup bugs
* Added table remove on deactivate plugin
* Added interval option
* Added link to post option
* Removed unique cron, replaced with ordinary wp-cron

= 1.8.2 =
* Publish to post was impossible due to missing javascript-file. Please update!

= 1.8.1 =
* Clear function in RSS-source updated

= 1.8 =
* Changed name to Newsflow
* WordPress own SimplePie is now used
* Links are stored in WordPress links
* Optimized for performance

= 0.10 =
Hypernews...
