=== BuddyPress Classifieds ===
Contributors: G.Breant
Donate link: http://dev.benoitgreant.be/2010/02/01/buddypress-classifieds#donate
Tags: BuddyPress, classifieds,annonces
Requires at least: WPMU 2.9.1, BuddyPress 1.2
Tested up to: WPMU 2.9.2, BuddyPress 1.2.1
Stable tag: 1.02

!WARNING : this plugin has been discontinued and replaced by [Your Classified Ads for BuddyPress](http://dev.pellicule.org/?page_id=24), for WP 3 and higher.
This component adds classifieds to your BuddyPress installation.

== Description ==

!WARNING : this plugin has been discontinued and replaced by <a href="http://dev.pellicule.org/?page_id=24">Your Classified Ads for BuddyPress</a>, for WP 3 and higher.

A Classifieds ad components that's fully integrated into BuddyPress.

Features :

* Write your ad directly from the frontend, WYSIWYG
* Allow/disallow moderation
* Browse by category, tags, ...
* Breadcrumbs
* Add to favorites ("follow)
* Upload pictures with your classified
* Link classifieds to groups
* Define a map (Google map) to locate your classified (powered by BuddyPress Maps)


Demo site : http://dev.benoitgreant.be/wordpress-mu/classifieds

== Installation ==

= WordPress 2.9.1 and above = 

* Copy the directory bp-classifieds to wp-content/plugins.
* Activate the plugin site-wide

* Create a new WPMU blog where the datas will be stored.  (This blog cannot be called 'classifieds').
* Access your main blog.  Under Admin>Blogs, edit the new created blog and set the "Public" setting to "NO". (this is for removing the blog from the BuddyPress search results).
* Remove the first default post of that blog ("hello world").
* Under BuddyPress>Classifieds, setup the Classifieds Component settings.

* If you want to have classifieds categories, create them in the data blog.
* If you want to have classifieds actions (will show tabs into your classifieds direction - eg. propositions,offers...), create them into the data blog then put their IDS in the component option.

* If you want a custom theme; copy the directory /classifieds from buddypress-classifieds/theme to your current theme and edit it.

== Frequently Asked Questions ==


== Screenshots ==

1. Classifieds directory
2. My Classifieds
3. Edit single classified
4. Classifieds directory (category-browsing)
5. Admin Options I
6. Admin Options III

== Changelog ==
= 1.02=
-Users are now automatically authors in the classifieds data blog
-Updated the way capabilities are handled
-Added pictures gallery ! Finally !
-There are still some bugs; coders please help : http://dev.benoitgreant.be/bbpress/topic/102-released
= 1.013=
-Added TinyMCE (WYSIWYG editor) for classified description
= 1.012=
-Added autosuggestion for tags
-Tabs count is now fixed
-Ajax for tabs disabled (http://dev.benoitgreant.be/bbpress/topic/ajax-for-tabs-disabled-coders-i-need-help)
= 1.010-beta =
-Bugs fixed
-Added group classifieds !
-Added classifieds maps (uses plugin BuddyPress Maps) !
= 1.005-beta =
Activity functions update
= 1.003-beta =
-various fixes about ajax
-theme is now included directly in the plugin
= 1.002-beta =
Added 2 simple widgets (Classifieds and Classifieds Tag Cloud)
= 1-beta =
* First version
