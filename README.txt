=== Plugin Name ===
Contributors: dynadata
Donate link: #
Tags: mijnclub, mijnclub.nu, teampagina, westrijdpagina, clubwebsite, xml, xmlreader, xmlparser, xmlconverter, nhwebsites, nh-websites
Requires at least: 3.4.1
Tested up to: 3.5
Stable tag: 1.7.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Converts Mijn Club XML data into Wordpress pages and menus, given the MijnClub clubcode

== Description ==

This plugin reads XML data that is provided by (http://www.mijnclub.nu/) .
It creates pages on the wordpress website for all teams of a club and the menu that shows the matches for those teams. 

It also has a few widgets that show next upcoming matches, a quick menu to select a team, and the current standings of a particular team.

The plugin has a few settings, of which the clubcode is the most important one.
Entering the correct clubcode allows for retrieval of all the data from mijnclub.nu.

This plugin will be used for Club websites that want their mijnclub information integrated on their wordpress website.


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the 'mijnclub'-folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter clubcode on the options page
4. Press the 'Ververs Teampagina's'-Button to create all pages and menus
5. Put the created pages in your Menu
6. Put the widgets that you like on any page


== Frequently Asked Questions ==

= Why do I not have access plugin? =

You are not authorized to use the plugin, contact <a href=\"mailto:info@nh-websites.nl?Subject=MijnClub%20Plugin%20toegang\">info@nh-websites.nl</a> to get access 

= Why does the plugin change the menu of my website? =

Prior to Mijnclub Plugin v1.2.8 the new "Mijnclub Menu" was automatically chosen after refreshing the pages.
To reset your website's menu go to Menu's and select your old menu.
Please update to the newest version to prevent this from happening.

= Why does the plugin break other plugins? =

Prior to Mijnclub Plugin v1.4.0 the plugin used an older version of jQuery, this can possibly break other plugins. 
To fix this please update to the latest version.

= Why are the tabs not working on the teampages? = 

Because of a bug in versions older than 1.4.0 the tabs were not correctly shown.
Please update to the latest version to fix this.

= I have accidentally removed pages or removed pages from the menu, how do I restore it? =

If you only removed a few pages from the Menu, you can restore it by manually adding them again.
If you removed actual pages, the easiest way to restore them is to deactivate the plugin (this will remove all the mijnclub pages),
and activate it again. Then press 'Ververs teampagina's' and it will create the pages again.

= How do I manually create the Mijnclub pages? What shortcodes are available? =

[mijnclub team="{teamcode}"] is the main shortcode to create the teampage. 
The teamcode is the Mijnclub teamcode. For example '1 (zon)' or 'E1' or '1 (zaal)'.

You can use the following shortcodes to fill the other pages:
[wedstrijden], [afgelastingen], [uitslagen], [verslagen], [trainingen].
The [wedstrijden] and [afgelastingen] can use parameters similar to the [mijnclub] shortcode.
You can use [wedstrijden periode="Deze Week"] to only show Wedstrijden for This week.
You can use the following 'periode': Deze Week, Volgende Week, Vorige Week, Volgende Speeldag, Komende Periode.

You can use the parameter 'dag' for the [trainingen] shortcode to only show Trainingen of that day.
For example [trainingen dag="maandag"] only shows the trainingen on Monday.

= Why can I not enter player-statistics for {teamname} ? =

Make sure the team is enabled in the 'Statistieken'-page options.
The other reason this might happen is because there are no 'Spelers' entered for that particular team at Mijnclub.nu.
Go to (http://www.mijnclub.nu/teams) , pick the team, and add 'Spelers'.

== Screenshots ==

1. Team page with filters and tabs that show all the information
2. Example of the 'Stand' tab
3. Example of the 'Programma' page which shows all the matches
4. All the widgets that are available
5. Mijnclub Options page
6. Statistieken-page

== Changelog ==

= 1.7.3 =
* Fixed bugs that were introduced in version 1.7.1 regarding the Tabs on the mijnclub team-pages

= 1.7.1 =
* Fixed a bug which would occur at the Trainingen page, when no team was selected (at the Mijnclub back-end) a PHP warning would be shown
* Added more HTML-classes to elements created by the plugin to allow for better customized styling

= 1.7.0 =
* Added new Statistieken functionality
* Fixed some HTML whitespacing
* Fixed more PHP warnings
* Cleaned up code
* Added new message when a Mijnclub-page would be removed during the Refresh team-pages action

= 1.6.5 =
* Replaced the get_page_by_title function that would produce bugs if similarly titled pages were found in the installation
* Fixed some PHP warning/notice bugs
* Added case-insensitive clubcode check
* Fixed PHP warnings in widgets
* Replaced the check in the Teamselector widget to prevent errors
* Updated the Readme FAQ

= 1.6.0 =
* Improved caching of Mijnclub data, and Authentication to ensure less errors are thrown
* Fixed a bug where the default 'Periode' would not match the selected 'Periode'
* Added a filter on the 'Wedstrijden' pages to allow filtering 'Uit/Thuis' matches
* Updated the Teamselector Widget to work with all permalink structures
* Added a check to not produce a Fatal Error when an XML string is empty
* Added the mijnclub icon to the 'Mijnclub Opties' page

= 1.5.0 =
* Added red 'afgelast' notification in the 'Eerstvolgende Wedstrijden' widget
* Fixed small textual bug
* Added sanitizing of all form fields for improved security
* Prefixed all functions to prevent future conflicts
* Updates in the FAQ
* Added documentation in the code
* Improvements of html styling
* Cleaned up code

= 1.4.0 =
* Fixed tabs using the wordpress packaged jQuery
* Added the ability to add the mijnclubpages to a Menu of your liking
* Added a link to the 'uitslagenmatrix' on the uitslagen tab
* Fixed small bug with caching when /wp-content/uploads/mijnclub did not exist
* Fixed styling issues with generic styling of h1-h6 in mijnclub.css

= 1.3.5 =
* Added caching to all requests that are done to mijnclub to improve website performance
* Added button to admin menu that allows the admin to clear the cache (to force update of pages)
* Some small styling bugs

= 1.3.1 =
* Refixed the bug that was fixed in 1.2.9

= 1.2.9 =
* Fixed bug in wordpress 3.5 that would hide all tabs

= 1.2.8 =
* Added 'uitslagen' widget
* Fixed small bug with 'uitslagen' tab, where it would not change the filter on teampages
* Added more filter-options in the 'uitslagen' tab
* Fixed 'MijnClubStandEerste' widget
* Fixed bug that would remove pages under the 'Wedstrijden' submenu when 'Ververs teampaginas' was clicked
* Added creating the 'Trainingen' page
* Removed automatic activation of the 'MijnClub Menu', would produce unwanted results on websites with existing menu

= 1.2.1 =
* Fixed small bug where the Teamselector Widget did not link to the correct page
* Added the ability to choose 'uitslagen' from 10 weeks back


= 1.2.0 = 
* Fixed critical bug that would remove all pages if a plugin was deactivated without creating the mijnclub pages
* Related bug: Makes sure the plugin can never remove any pages not created by the plugin

= 1.1.0 =
* Added an extra tab in the team pages to show trainingen

= 1.0.0 =
* Fixed the correct refreshing of all teampages
* moved the functions for printing the admin page 
* cleaned code of unused statements
* Fixed message when page/category is removed

= 0.9.0 =
* Bugfix for teaminfo being shown on teampages
* 'Uitslagen' can now be filtered by week
* On teampages, the selected tab is now remembered when you choose a different filter


= 0.8.5 =
* Authorisation is fixed
* Deactivation of plugin cleans up properly
* Loads wordpress jQuery version instead of packaged (older) version
* Adding option in options to select 'Eerste Team'

= 0.7.0 =
* First release

== Upgrade Notice ==

= 1.2.0 =
This is the first completely working version, do not use older versions.
