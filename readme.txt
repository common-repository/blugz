=== Blugz - Google Buzz stream importer ===
Contributors: andrea.olivato
Donate link: http://null/
Tags: buzz,import,social
Requires at least: 2.7
Tested up to: 2.9.2
Stable tag: 0.5

Blugz for Wordpress allows any WP Blog owner to import automatically his buzz stream into his blog, including comments and media attachments

== Description ==

Blugz for Wordpress is a standalone, highly optimized Google&trade; Buzz&trade; stream importer for Wordress.

Using Blugz you can easily import your Buzz posts into a single category of your blog, automatically adding comments, images and links exactly as they show up on buzz.

*PLEASE NOTE WE ARE IN A BETA VERSION*

**Key Features**

*	Import your whole buzz stream into your wordpress blog
*   Auto-update new posts everytime someone visit your website (you can set up a minimum delay)
*	Shows comments in real time
*	Shows images, links and rich media exactly as shown in buzz
*	Geolocation Integration >**new**<
*	Choose user to assign imported posts to
*	Shows likes in real time >**new**<

**Why would you need to import your buzz stream into a Blog?**

*	You can be **indicized** better on google, using *sitemaps*, advanced *seo* tools etc
*	You can **personalize** it adding themes or changing the whole layout
*	You can **monetize** your stream adding *advertising* to your posts
*	You can **enrich** your blog adding a *live flux* of posts to your current articles


**Future Development**

Please fill out your suggestion in the [dedicated thread](http://wordpress.org/support/topic/398254 "dedicated thread")

*	Multiple account support
*	Direct link to images in the image attachments
*	Video support
*	Get images at first import
*	Direct comments write support

== Installation ==

**Basic Setup**

1. Upload the `blugz` directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Insert your user and change the destination category from `Settings` -> `Blugz` in your WP Administration

**First Import**

To start importing your buzz you just need to visit your blog homepage. The first import will take a while, please be patient.
As soon as the page finishes loading you can refresh it and you should see your brand new aggregated stream.

**Ad Hoc Theme**

You can download a modded version of P2 theme and install it to achieve the best Blugz experience

[Download P2 Mod Blugz](http://blugz.com/p2-mod-blugz.zip "Download P2 Mod Blugz") themes website.

**Geolocation**

To use geolocation you need to install the [Automattic official plugin](http://wordpress.org/extend/plugins/geolocation/ "Automattic official plugin")

**Theme Optimization**

For the best result we suggest you to use the `P2` theme with disabled titles. You can download `P2` from the [official wordpress](http://wordpress.org/extend/themes/p2 "official wordpress") themes website.

If you are using a different theme you should act as follows:

 * Disable comments
 * Disable titles

== Screenshots ==

1. Main page showing a P2 optimized theme with your buzz stream
2. Detail page featuring links, images and comments
3. Detail page featuring geolocation. Second part is "onmouseover"

== Changelog ==

= 0.5 =
* Updated API protocol to new version
* Added "Likes" support
* Better GeoLocation support, added since the first import
* Debugged Comments
* Improved Graphics
* Saved many async calls by getting more info from the new API
* Started working with JSON instead of Serialized Arrays on Ajax

= 0.4 = 
* Added user selection
* BugFixed GeoLocation again
* BugFixed image placement
* BugFixed missing time

= 0.3.1 =
* BugFixed GeoLocation

= 0.3 =
* Added GeoLocation support

= 0.2 =
* Bugfixed Images
* Added some more graphics
* Links to leave comments
* Added titles to links

= 0.1 =
* Added support for images
* Added icon for Links

== Frequently Asked Questions ==

= Why geolocation isn't working? =

Please install [GeoLocation plugin](http://wordpress.org/extend/plugins/geolocation/ "GeoLocation plugin")

== Upgrade Notice ==
None Yet
