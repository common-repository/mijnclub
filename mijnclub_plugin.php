<?php
/*
Plugin Name: MijnClub Plugin
Plugin URI: http://wordpress.org/extend/plugins/mijnclub/
Description: Converteert MijnClub XML data naar wordpress paginas en menus, wanneer de juiste MijnClub clubcode word ingegeven. Bevat ook een aantal widgets die kunnen worden gebruikt op de website. <strong>LET OP: Wanneer de plugin wordt gedeactiveerd worden alle paginas weer verwijderd.</strong>
Version: 1.7.3
Author: NH-Websites
Author URI: http://www.nh-websites.nl
License: GPLv2

	Copyright 2013  Dynadata  (info@dynadata.nl)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define('mijnclub_version','1.7.4');

include 'mijnclub_widgets.php'; //loads widgets

$adminOptionsName = 'MijnClubXMLparserAdminOptions';
$changes = array();

//init
register_activation_hook('mijnclub/mijnclub_plugin.php','mijnclub_init');
register_deactivation_hook('mijnclub/mijnclub_plugin.php','mijnclub_destroy');
add_action('admin_init', 'mijnclub_options_redirect'); //redirects upon first activation

//g-c creates directory to write cache files in
if (!is_dir(mijnclub_getValue(wp_upload_dir(),'basedir').'/mijnclub/')) {
	mkdir(mijnclub_getValue(wp_upload_dir(),'basedir').'/mijnclub/',0755);
}

//c-sh adds the shortcodes
if (function_exists('add_shortcode')) { //adding all shortcodes to wordpress
	add_shortcode('mijnclub','mijnclub_printteampagina');
	add_shortcode('wedstrijden','mijnclub_printwedstrijdenshortcode');
	add_shortcode('afgelastingen','mijnclub_printafgelastingen');
	add_shortcode('uitslagen', 'mijnclub_printuitslagen');
	add_shortcode('verslagen', 'mijnclub_printverslagen');
	add_shortcode('trainingen','mijnclub_printtrainingen');
}

//c-sh adds the actions
if (function_exists('add_action')&&function_exists('add_filter')) { 
	add_action('admin_menu','mijnclub_menu'); //creates the admin menu
	//add_action('wp_head','mijnclub_addheadercode'); //adds code to header of html
	add_action('wp_print_scripts','mijnclub_load_table_scripts'); //loads the tab scripts
	add_action('wp_print_styles','mijnclub_load_stylesheets'); 	//loads the styles for tabs
	
	add_action('admin_enqueue_scripts','mijnclub_adminstyles'); //loads admin javascript
}

//g-f
function mijnclub_menu() { //adding the options page in admin menu
	if (function_exists('add_menu_page') && function_exists('add_submenu_page')) {
		add_menu_page('MijnClub Opties', 'MijnClub Opties', 'activate_plugins', 'mijnclub-opties', 'mijnclub_printAdminPage',WP_CONTENT_URL.'/plugins/mijnclub/images/icon.png');
		add_submenu_page('mijnclub-opties', 'Teams Statistieken', 'Statistieken', 'activate_plugins','mijnclub-statistieken','mijnclub_printAdminStatistiekenPage');
	}
}

//c-s*
function mijnclub_addheadercode () { 
	//currently no headercode is inserted
}

//c-st
function mijnclub_load_stylesheets() {
	wp_register_style('tabs_smoothness', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/css/smoothness/jquery-ui-1.8.22.custom.css');
	wp_enqueue_style('tabs_smoothness');
	
	wp_register_style('mijnclubstyle', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/css/mijnclub.css');
	wp_enqueue_style('mijnclubstyle');
}

function mijnclub_adminstyles() {
	wp_register_style('tabs_smoothness', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/css/smoothness/jquery-ui-1.8.22.custom.css');
	wp_enqueue_style('tabs_smoothness');
	
	wp_register_style('jquery-tabs-backend', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/css/admin-jquery-ui.css');
	wp_enqueue_style('jquery-tabs-backend');
	
	wp_register_style('mijnclubadminstyle', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/css/mijnclub-admin.css');
	wp_enqueue_style('mijnclubadminstyle');
	
	wp_register_script('vertical-tabs', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/js/vertical-tabs.js');
	wp_enqueue_script('vertical-tabs');
	
	wp_enqueue_script('jquery-ui-accordion');
}

//c-sc
function mijnclub_load_table_scripts() {
	wp_enqueue_script('jquery-ui-tabs');
	
	wp_register_script('tabsinline', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/js/tabs-inline.js');
	wp_enqueue_script('tabsinline');
	
	wp_register_script('posttabs', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/js/posttabs.js');
	wp_enqueue_script('posttabs');
}

//g-c makes cookie with chosen team and redirects to that team's page
if (isset($_POST['goto'])) {
	$goto = (string) esc_url($_POST['goto']);
	
	$teamarray = mijnclub_maakteamarray();
	$chosenteam = (string) $teamarray[$goto];
	$expire = time()+60*60*24*30; //expires after 30 days
	setcookie('gekozenteam', $chosenteam, $expire);

	header('location: '.$goto); //redirects to correct page
}

//g-f
function mijnclub_printAdminPage() {
	include 'admin_settings_page.php';
}

function mijnclub_printAdminStatistiekenPage() {
	include 'admin_teamstatistics_page.php';
}

//g-f returns the values of the saved options
function mijnclub_getOptions() {
	global $adminOptionsName;
	$result = array('clubcode' => '', 'aantalwedstrijden' => 10, 'showpowered' => 'false', 'eersteteam' => '', 'eersteteamnaam' => '');
	if (function_exists('get_option')) {
		$retrievedOptions = get_option($adminOptionsName);
	}
	if (!empty($retrievedOptions)) {
		foreach ( $retrievedOptions as $key=>$option) {
			$result[$key] = $option;
		}
	}
	return $result;
}
	
//a-f sets options to control whether page is redirected, or authentication is done
function mijnclub_init() {
	add_option('mijnclub_do_activation_redirect', true);
}

//a-f removes pages/menus and options that are made in the plugin
function mijnclub_destroy() {
	mijnclub_removewedstrijdpages();
	mijnclub_removeteampages();
	global $adminOptionsName;
	delete_option($adminOptionsName);
}

function mijnclub_options_redirect() {
	if (get_option('mijnclub_do_activation_redirect', false)) {
        delete_option('mijnclub_do_activation_redirect');
		$domain = get_bloginfo('wpurl');
		wp_redirect($domain.'/wp-admin/admin.php?page=mijnclub-opties');
    }
}

//a-f
function mijnclub_add_pages_to_menu($id) {
	$args = array(
		'numberposts' => -1,
		'orderby'         => 'menu_order',
		'meta_key'        => 'mijnclub',
		'meta_value'      => 'true',
		'post_type'       => 'page',
		'post_status'     => 'publish',
		'suppress_filters' => true 
	);
	$mijnclub_pages = get_posts( $args );
	$status = array();

	$menu_items = wp_get_nav_menu_items($id, array('orderby'=>'menu_order'));
	
	//teampages
	if (mijnclub_get_menu_item_id('Teams', $menu_items) == -1) {
		$teammenu = mijnclub_addsubmenu($id, 'Teams', 0);
		mijnclub_addmenupage($id, 'Alle Teams', $teammenu);
	
		$devOptions = mijnclub_getOptions();
		$xmlurl = 'http://www.mijnclub.nu/clubs/teams/xml/'.$devOptions['clubcode'];
		$xml = mijnclub_loadxml($xmlurl);
		
		$cat = ''; //stores the current category in loop, is empty at first
		foreach ($xml->team as $team) { //loops through all the teams to create the pages
			$naam = $team->naam;
			$soort = (string) $team->soort;
			if ($cat != $soort) {
				$cat = $soort;
				$catmenu = mijnclub_addsubmenu($id, $cat, $teammenu);
			}
			mijnclub_addmenupage($id, $naam, $catmenu);
		}
		$status[] = true;
	} else {
		//should reflect new changes
		$status[] = false;
	}
	
	//wedstrijdpages
	if (mijnclub_get_menu_item_id('Wedstrijden',$menu_items) == -1) {
		$wedstrijdmenu = mijnclub_addsubmenu($id, 'Wedstrijden', 0);
		$menu_items = wp_get_nav_menu_items($id, array('orderby'=>'menu_order'));
		//$wedstrijdmenu = mijnclub_get_menu_item_id('Wedstrijden', $menu_items);
		
		$progmenu = mijnclub_addsubmenu($id, 'Programma', $wedstrijdmenu);
		mijnclub_addmenupage($id, 'Deze Week', $progmenu);
		mijnclub_addmenupage($id, 'Volgende Week', $progmenu);
		mijnclub_addmenupage($id, 'Vorige Week', $progmenu);
		mijnclub_addmenupage($id, 'Volgende Speeldag', $progmenu);
		mijnclub_addmenupage($id, 'Komende Periode', $progmenu);
		
		$afgelastmenu = mijnclub_addsubmenu($id, 'Afgelastingen', $wedstrijdmenu);
		mijnclub_addmenupage($id, 'Afgelastingen Deze Week', $afgelastmenu);
		mijnclub_addmenupage($id, 'Afgelastingen Volgende Week', $afgelastmenu);
		mijnclub_addmenupage($id, 'Afgelastingen Vorige Week', $afgelastmenu);
		mijnclub_addmenupage($id, 'Afgelastingen Volgende Speeldag', $afgelastmenu);
		mijnclub_addmenupage($id, 'Afgelastingen Komende Periode', $afgelastmenu);
		
		mijnclub_addmenupage($id, 'Uitslagen' , $wedstrijdmenu);
		mijnclub_addmenupage($id, 'Verslagen' , $wedstrijdmenu);
		mijnclub_addmenupage($id, 'Trainingen' , $wedstrijdmenu);
		
		$status[] = true;
	} else {
		$status[] = false;
	}
	
	return $status;
}

//g-f checks if domain is authenticated to use plugin
function mijnclub_authenticate($source = 'default') {
	//reads info from website
	$domain = get_bloginfo('wpurl');
	$devOptions = mijnclub_getOptions();
	$clubcode = $devOptions['clubcode'];
	
	//retrieves the valid combinations of authentications
	$auths = mijnclub_getauths();
	$authsarray = array();
	foreach(preg_split("/((\r?\n)|(\r\n?))/",$auths) as $line) {
		if ($line != '') {
			$linearray = explode('@',$line); //splits a line into array with domain and clubcode
			$authsarray[] = array( 'domain' => $linearray[0], 'clubcode' => $linearray[1]);
		}
	}
	//matches the given details with the valid authentications
	return mijnclub_detailsmatch($domain, $clubcode, $authsarray, $source);
}

//g-f retrieves the valid authentications
function mijnclub_getauths() {
	//caching the auths.txt file to prevent alot of traffic to external auths.txt file
	$cachefile = mijnclub_getValue(wp_upload_dir(),'basedir').'/auths-cache.txt';
	$cachetime = 3600; //1hour
	
	$writecache=false; //defaults to false
	//uses the cached file if latest cache is less than 1 hour ago, otherwise loads external auths.txt and writes the cached auths-cache.txt
	
	if (file_exists($cachefile)) {
		$cache = file_get_contents($cachefile);
	} else {
		$cache = false;
	}
	
	if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $cache !== false && $cache != '' ) {
		$data = $cache;
	} else {
		$url = 'http://www.nh-websites.nl/wp-content/uploads/auths.txt';
		$ch = curl_init();
		$timeout = 30;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
	
		$writecache=true; //write new cache
	}
	
	if ($writecache) { //saves the new auths.txt in the cache
		$fh = fopen($cachefile, 'w');
		fwrite($fh, $data);
		fclose($fh);
	}
	
	return $data;
}

//g-f checks if details of domain match with auths
function mijnclub_detailsmatch($domain, $clubcode, $a, $source) {
	foreach($a as $auth) {
		if ($auth['domain'] == $domain && $auth['clubcode'] == strtoupper($clubcode)) {
			return true;
		}
	}
	//saves accesslog
	$date = date("Y-m-d H:i:s");
	$url = 'http://www.nh-websites.nl/wp-content/uploads/accesslog.php';
	$post_data['date'] = $date;
	$post_data['domain'] = $domain;
	$post_data['clubcode'] = $clubcode;
	$post_data['version'] = mijnclub_version;
	$post_data['source'] = $source;
	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_exec($ch);
	
	return false; //returns false when there is no match
}

function mijnclub_clean_name($string) {
	$string = html_entity_decode($string, ENT_COMPAT,'ISO-8859-1');
	return strtr( $string,
	"ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
	"aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn" );
}

function mijnclub_clean($string) {
	return strtolower(str_replace(
		array('(',')',' '),
		array('','','-'),
		$string
	));
}

//c-f loads xml from mijnclub or from cache
function mijnclub_loadxml($url,$echo=false) {
	$urlreplace = str_replace(' ','%20',$url); //replaces space with %20, needed to load XML correctly

	$cachename = mijnclub_getcachename($urlreplace);
	$cachefile = mijnclub_getValue(wp_upload_dir(),'basedir').'/mijnclub/'.$cachename.'.xml';
	$cachetime = 7200; //1hour
	$writecache=false; //defaults to false
	
	if (file_exists($cachefile)) {
		$cache = file_get_contents($cachefile);
	} else {
		$cache = false;
	}
	
	if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $cache !== false && $cache != '' ) {
		$xml = $cache;
	} else {
		$ch = curl_init();
		$timeout = 30;
		curl_setopt($ch,CURLOPT_URL,$urlreplace);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$xml = curl_exec($ch);
		curl_close($ch);
		$writecache=true; //write new cache
	}

	if ($writecache) { //saves the new xml in the cache
		$fh = fopen($cachefile, 'w');
		fwrite($fh, $xml);
		fclose($fh);
	}
	
	//checks for validity of loaded xml file
	$correctteampagina	= strpos($xml,'id="wedstrijdschema"');
	$correctwedstrijdxml = strpos($xml,'lastModified=');
	$correctteamxml = strpos($xml,'clubcode=');
	$correctstand = strpos($xml, 'class="ranking"');
	$correcttraining = strpos($xml, 'last_update="');
	
	//only loads the xml if a correct file is found
	if ($correctteampagina>0 || $correctwedstrijdxml>0 || $correctteamxml>0 || $correcttraining > 0) {
		$xml = simplexml_load_string($xml);
		return $xml;
	}
	if ($correctstand>0) {
		$xml = simplexml_load_string($xml);
		return $xml;
	} else {
		return "Geen stand gevonden";
	}
}

//c-f gets the data from mijnclub or cache
function mijnclub_getdata($url,$echo=false) {
	$urlreplace = str_replace(' ','%20',$url); //replaces space with %20, needed to load data correctly
	
	$cachename = mijnclub_getcachename($urlreplace);	
	$cachefile = mijnclub_getValue(wp_upload_dir(),'basedir').'/mijnclub/'.$cachename.'.xml';
	$cachetime = 7200; //1hour
	$writecache=false; //defaults to false

	if (file_exists($cachefile)) {
		$cache = file_get_contents($cachefile);
	} else {
		$cache = false;
	}
	
	if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $cache !== false && $cache != '' ) {
		$data = $cache;
	} else {
		$ch = curl_init();
		$timeout = 30;
		curl_setopt($ch,CURLOPT_URL,$urlreplace);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		$writecache=true; //write new cache
	}

	if ($writecache) { //saves the new xml in the cache
		$fh = fopen($cachefile, 'w');
		fwrite($fh, $data);
		fclose($fh);
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode != 404) {
			curl_close($ch);
			return $data;
		}
	}
	
	return $data;
}

//c-f converts the xml url to the name how the cache is saved
function mijnclub_getcachename($url) {
	$name = str_replace(
	array('http://www.mijnclub.nu/clubs/','xml/',
		'embed/','?layout=stand&format=xml',
		'%20','?afgelast=',
		'?team=','/team/',
		'/periode,','&periode=',
		'?periode=','?lokatie=',
		'&lokatie=','/dag/',
		'/')
	,array('','',
		'','-stand',
		'-','-a',
		'-t','-t',
		'-p','-p',
		'-p','-l',
		'-l','-d',
		'-'),$url);
	
	return $name;
}

//a-f returns an array with the details given
function mijnclub_getpostarray($order, $content, $slug, $parent, $title) {
	$array = array(
		'menu_order' => $order, 
		'comment_status' => 'closed', 
		'ping_status' => 'closed', 
		'post_content' => $content , 
		'post_name' => $slug ,  // The name (slug) for your  post
		'post_parent' => $parent->ID , //Sets the parent of the new post.
		'post_status' => 'publish' ,
		'post_title' => $title , //The title of your post.
		'post_type' => 'page'
	);
	return $array;
}

//a-f clears the cache so new XML from mijnclub can be retrieved
function mijnclub_clearcache() {
	$files = glob(mijnclub_getValue(wp_upload_dir(),'basedir').'/mijnclub/*.xml');
	foreach ($files as $file) {
		if(is_file($file)) { unlink($file);}
	}
}

//a-f refreshes all the pages
function mijnclub_refreshpages() {
	//create menu
	$menuname = 'Mijnclub Menu';
	$menu_exists = wp_get_nav_menu_object( $menuname );	
	if (!$menu_exists) {
		$menu_id = wp_create_nav_menu($menuname);
	}
	
	mijnclub_refreshteampages();
	
	mijnclub_createwedstrijdpages();
	
	//mijnclub_activatemenu();
}

//a-f removes the wedstrijd pages 
function mijnclub_removewedstrijdpages() {

	mijnclub_delete_page(mijnclub_get_page('Wedstrijden','nav_menu_item')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Wedstrijden')->ID, true);
	
	//removes pages
	mijnclub_delete_page(mijnclub_get_page('Programma','nav_menu_item')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Programma')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Deze Week')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Volgende Week')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Vorige Week')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Volgende Speeldag')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Komende Periode')->ID, true);
	
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen','nav_menu_item')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen Deze Week')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen Volgende Week')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen Vorige Week')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen Volgende Speeldag')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Afgelastingen Komende Periode')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Uitslagen')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Verslagen')->ID, true);
	mijnclub_delete_page(mijnclub_get_page('Trainingen')->ID, true);
	
	//removes menu items
}

//retrieves a mijnclub page based on their title
function mijnclub_get_page($name = '', $type = 'page', $single = true) {
	global $wpdb;
	
	$query = "SELECT $wpdb->posts.post_title,$wpdb->posts.ID,$wpdb->postmeta.meta_value 
	FROM $wpdb->posts,$wpdb->postmeta 
	WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
	AND $wpdb->postmeta.meta_key = 'mijnclub' 
	AND $wpdb->postmeta.meta_value = 'true' 
	AND $wpdb->posts.post_status = 'publish' 
	AND $wpdb->posts.post_type = '$type'";
	
	if ($name != '') {
		$query .= "AND $wpdb->posts.post_title = '$name'";
	}
	$results = $wpdb->get_results($query);
	if ($single) {
		if (count($results) > 0) {
			return $results[0];
		} else {
			$return = new stdClass();
			$return->ID = '';
			return $return;
		}
	} else {
		if (count($results) > 0) {
			return $results;
		} else {
			$return = new stdClass();
			$return->ID = '';
			return $return;
		}
	}
}

//a-f deletes a single page, only if it's created by the plugin
function mijnclub_delete_page($id,$perm_del) {
	//removed check if page was mijnclub page because this is handled by mijnclub_get_page function
	wp_delete_post($id, $perm_del);
}

//a-f removes all the teampages/menuitems
function mijnclub_removeteampages() {
	$menu_id = (int) wp_get_nav_menu_object('MijnClub Menu')->term_id; 

	$oldpages = get_pages();
	$oldmenus = wp_get_nav_menu_items($menu_id);
	if (gettype($oldmenus) == 'array') {
		$oldall = array_merge($oldpages,$oldmenus);
	} else {
		$oldall = $oldpages;
	}
	
	mijnclub_remove_oldpages($oldall);
}

//a-f refreshes all team pages
function mijnclub_refreshteampages() {
	$devOptions = mijnclub_getOptions();
	
	$xmlurl = 'http://www.mijnclub.nu/clubs/teams/xml/'.$devOptions['clubcode'];
	
	$xml = mijnclub_loadxml($xmlurl);
	
	$cat = ''; //stores the current category in loop, is empty at first
	
	$menu_id = (int) wp_get_nav_menu_object('MijnClub Menu')->term_id; 
	$menu_items = wp_get_nav_menu_items($menu_id, array('orderby'=>'menu_order'));
	
	$teamspage = mijnclub_get_page('Teams'); 
	$teamspageid = ($teamspage !== null) ? $teamspage->ID : '';
	
	if ($teamspageid == '') {
		$post = array(
			'comment_status' => 'closed', 
			'ping_status' => 'closed', 
			'post_content' => 'Dit is het hoofdmenu van alle teams' , 
			'post_name' => 'teams' ,  // The name (slug) for your post 
			'post_status' => 'publish' , 
			'post_title' => "Teams" , //The title of your post.
			'post_type' => 'page' 
		);  
		$id = wp_insert_post($post);
		add_post_meta($id, 'mijnclub', 'true'); 
	} 
	
	if (mijnclub_get_menu_item_id('Teams', $menu_items) == -1) {
		$teammenu = mijnclub_addsubmenu($menu_id, 'Teams', 0);
	} else {
		$teammenu = mijnclub_get_menu_item_id('Teams', $menu_items);
	}
	
	$args = array(
		'sort_order' => 'ASC',
		'sort_column' => 'menu_order',
		'post_type' => 'page',
		'meta_key' => 'mijnclub',
		'meta_value' => 'true',
		'post_status' => 'publish'
	);
	
	$oldpages = get_pages($args);
	global $changes;
	
	$catnum = 1;
	$teamnum = 1;
	
	$index = mijnclub_page_alreadymade('Teams',$oldpages);
	if ($index >= 0) {
		unset($oldpages[$index]);
		$oldpages = array_values($oldpages);
	}
	
	$index = mijnclub_page_alreadymade('Alle Teams',$oldpages);
	if ($index >= 0) {
		unset($oldpages[$index]);
		$oldpages = array_values($oldpages);
	} else {
		mijnclub_postallteamspage();
		mijnclub_addmenupage($menu_id, 'Alle Teams', $teammenu);
	}
		
	foreach ($xml->team as $team) { //loops through all the teams to create the pages
		$naam = $team->naam;
		$soort = (string) $team->soort;
		if ($cat != $soort) {
			$cat = $soort;
			$index = mijnclub_page_alreadymade($cat, $oldpages);
			if ($index >= 0) { //removes the team from the oldpages list
				unset($oldpages[$index]);
				$oldpages = array_values($oldpages);
				$catmenu = mijnclub_get_menu_item_id($cat, $menu_items);
			} else { //create the page
				mijnclub_postcatpage($cat,$catnum);
				$catmenu = mijnclub_addsubmenu($menu_id, $cat, $teammenu);
				$changes[] = 'Categorie toegevoegd: '.$cat;
				$catnum++;
			}
		}
		$index = mijnclub_page_alreadymade($naam,$oldpages);
		if ($index >= 0) {
			unset($oldpages[$index]);
			$oldpages = array_values($oldpages);
		} else {
			mijnclub_postteampage($cat,$naam,$teamnum);
			mijnclub_addmenupage($menu_id, $naam, $catmenu);
			$changes[] = 'Teampagina toegevoegd: '.$naam;
			$teamnum++;
		}
	}
	
	//removes pages that are no longer in the teamlist
	mijnclub_remove_oldpages($oldpages);
	
	//prints out all changes made
	if (count($changes) > 0){
		echo '<div class="updated"><p><strong>De volgende aanpassingen zijn gedaan</strong>';
		foreach ($changes as $change) {
			echo '<p>'.$change.'</p>';
		}
		echo '</div>';
	} else {
		echo '<div class="updated"><p><strong>Er zijn geen aanpassingen gedaan in de teampaginas</strong></div>';
	}
}

//a-f returns the index of the page in the oldpages array, -1 if page not made
function mijnclub_page_alreadymade($title, $oldpages) {
	$max = count($oldpages);
	for ($i=0; $i < $max; $i++) {
		if ($oldpages[$i]->post_title == $title) { //if title matches: return the index of the page in array
			return $i;
		}
	}
	return -1; //otherwise returns -1
}

//a-f removing all the old pages
function mijnclub_remove_oldpages($pages) {
	//array of pages that should not be removed
	$wedstrijdpages = array('Wedstrijden','Programma','Deze Week','Volgende Week','Vorige Week','Volgende Speeldag','Komende Periode','Afgelastingen','Afgelastingen Deze Week','Afgelastingen Volgende Week','Afgelastingen Vorige Week','Afgelastingen Volgende Speeldag','Afgelastingen Komende Periode','Uitslagen','Verslagen','Trainingen');
	global $changes;
	foreach ($pages as $page) {
		$title = $page->post_title;
		
		//checks if the page isnt a wedstrijdpage
		if (!in_array($title,$wedstrijdpages)) {
			$id = $page->ID;
			
			$value = get_post_meta($id, 'mijnclub', true);
			
			if ($value == 'true') {
				//deletes the page 
				wp_delete_post($page->ID, true);
				$changes[] = 'Pagina verwijderd: '.$title;
			}
		}
	}
}

//a-f checks if a menu_item exists within the menu and returns the ID or -1
function mijnclub_get_menu_item_id($name, $menu_items) {
	foreach ($menu_items as $menu_item) {
		if ($menu_item->title == $name) {
			return $menu_item->ID;
		}
	}
	return -1;
}

//a-f inserts a category page into wordpress
function mijnclub_postcatpage($cat,$order) { 
	if (function_exists('wp_insert_post')) {
		if (mijnclub_get_page($cat)->ID == ''){
			$parentid = mijnclub_get_page('Teams')->ID;
			$post = array(
			  'menu_order' => $order, 
			  'comment_status' => 'closed', 
			  'ping_status' => 'closed', 
			  'post_content' => 'Dit is het submenu van categorie: '.$cat , 
			  'post_name' => $cat ,  // The name (slug) for your post 
			  'post_parent' => $parentid , //Sets the parent of the new post.
			  'post_status' => 'publish' , 
			  'post_title' => $cat , //The title of your post.
			  'post_type' => 'page' 
			);  
			$id = wp_insert_post($post);
			add_post_meta($id, 'mijnclub', 'true');
		}
	}	
}

//a-f inserts a teampage into wordpress
function mijnclub_postteampage($cat,$name,$order) { 
	if (function_exists('wp_insert_post')) {
		if (mijnclub_get_page($name)->ID == '') {
			$parentid = mijnclub_get_page($cat)->ID;
			$post = array(
			  'menu_order' => $order , 
			  'comment_status' => 'closed',  
			  'ping_status' => 'closed',
			  'post_content' => '[mijnclub team="'.$name.'"]', 
			  'post_name' => 'team-'.$name , // The name (slug) for your  post
			  'post_parent' => $parentid ,//Sets the parent of the new post.
			  'post_status' => 'publish', 
			  'post_title' => $name , //The title of your post.
			  'post_type' => 'page',
			);  
			$id = wp_insert_post($post);
			add_post_meta($id, 'mijnclub', 'true');
		}
	}
}

//a-f inserts the 'Alle Teams' page
function mijnclub_postallteamspage() {
	$content = '[mijnclub team="alle" periode="Komende Periode" teampagina="nee"]';
	if (function_exists('wp_insert_post')) {
		if (mijnclub_get_page('Alle Teams')->ID == ''){
			$parentid = mijnclub_get_page('Teams')->ID;
			$post = array(
			  'menu_order' => 0, 
			  'comment_status' => 'closed', 
			  'ping_status' => 'closed', 
			  'post_content' => $content , 
			  'post_name' => 'alle-wedstrijden' ,  // The name (slug) for your  post
			  'post_parent' => $parentid , //Sets the parent of the new post.
			  'post_status' => 'publish' , 
			  'post_title' => 'Alle Teams' , //The title of your post.
			  'post_type' => 'page'
			);  
			$id = wp_insert_post($post);
			add_post_meta($id, 'mijnclub', 'true');
		}
	}
}

//a-f creates all the wedstrijdpages
function mijnclub_createwedstrijdpages() {
	$menu_id = (int) wp_get_nav_menu_object('MijnClub Menu')->term_id;
	$menu_items = wp_get_nav_menu_items($menu_id, array('orderby'=>'menu_order'));
	
	
	if (mijnclub_get_menu_item_id('Wedstrijden',$menu_items) == -1) {
		$post = array(
			  'menu_order' => 0, 
			  'comment_status' => 'closed', 
			  'ping_status' => 'closed', 
			  'post_content' => 'Dit is het hoofdmenu van alle wedstrijden' , 
			  'post_name' => 'wedstrijden' ,  // The name (slug) for your  post
			  'post_status' => 'publish' , 
			  'post_title' => 'Wedstrijden' , //The title of your post.
			  'post_type' => 'page'
			);  
		$id = wp_insert_post($post); //inserts wedstrijden page
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$parent = mijnclub_get_page('Wedstrijden');
		//hoofdcategorie voor programma
		$id = wp_insert_post(mijnclub_getpostarray(0,'Dit is de hoofdcategorie voor het programma van alle wedstrijden', 'programma', $parent, 'Programma'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		//wedstrijden per periode
		$id = wp_insert_post(mijnclub_getpostarray(0,'[wedstrijden periode="Deze Week"]', 'deze-week', mijnclub_get_page('Programma'), 'Deze Week'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(1,'[wedstrijden periode="Volgende Week"]', 'volgende-week', mijnclub_get_page('Programma'), 'Volgende Week'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(2,'[wedstrijden periode="Vorige Week"]', "vorige-week", mijnclub_get_page('Programma'), 'Vorige Week'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(3,'[wedstrijden periode="Volgende Speeldag"]', 'volgende-speeldag', mijnclub_get_page('Programma'), 'Volgende Speeldag'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(4,'[wedstrijden periode="Komende Periode"]', 'komende-periode', mijnclub_get_page('Programma'), 'Komende Periode'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		//hoofdcategorie voor afgelastingen
		$id = wp_insert_post(mijnclub_getpostarray(1,'Dit is de hoofdcategorie voor alle afgelastingen', 'afgelastingen', $parent, 'Afgelastingen'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		//afgelastingen per periode
		$id = wp_insert_post(mijnclub_getpostarray(0,'[afgelastingen periode="Deze Week"]', 'afgelastingen-deze-week', mijnclub_get_page('Afgelastingen'), 'Afgelastingen Deze Week'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(1,'[afgelastingen periode="Volgende Week"]', 'afgelastingen-volgende-week', mijnclub_get_page('Afgelastingen'), 'Afgelastingen Volgende Week'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(2,'[afgelastingen periode="Vorige Week"]', 'afgelastingen-vorige-week', mijnclub_get_page('Afgelastingen'), 'Afgelastingen Vorige Week'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(3,'[afgelastingen periode="Volgende Speeldag"]', 'afgelastingen-volgende-speeldag', mijnclub_get_page('Afgelastingen'), 'Afgelastingen Volgende Speeldag'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(4,'[afgelastingen periode="Komende Periode"]', 'afgelastingen-komende-periode', mijnclub_get_page('Afgelastingen'), 'Afgelastingen Komende Periode'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		
		//uitslagen-, wedstrijdverslagen- en trainingenpaginas
		$id = wp_insert_post(mijnclub_getpostarray(2, '[uitslagen matrix="false"]', 'uitslagen', $parent, 'Uitslagen'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(3, '[verslagen]', 'verslagen', $parent, 'Verslagen'));
		add_post_meta($id, 'mijnclub', 'true'); 
		
		$id = wp_insert_post(mijnclub_getpostarray(4, '[trainingen]', 'trainingen', $parent, 'Trainingen'));
		add_post_meta($id, 'mijnclub', 'true'); 
				
		$menu_id = (int) wp_get_nav_menu_object('MijnClub Menu')->term_id;
		mijnclub_addsubmenu($menu_id, 'Wedstrijden', 0);
		$menu_items = wp_get_nav_menu_items($menu_id, array('orderby'=>'menu_order'));
		$wedstrijdmenu = mijnclub_get_menu_item_id('Wedstrijden', $menu_items);
		
		$progmenu = mijnclub_addsubmenu($menu_id, 'Programma', $wedstrijdmenu);
		mijnclub_addmenupage($menu_id, 'Deze Week', $progmenu);
		mijnclub_addmenupage($menu_id, 'Volgende Week', $progmenu);
		mijnclub_addmenupage($menu_id, 'Vorige Week', $progmenu);
		mijnclub_addmenupage($menu_id, 'Volgende Speeldag', $progmenu);
		mijnclub_addmenupage($menu_id, 'Komende Periode', $progmenu);
		
		$afgelastmenu = mijnclub_addsubmenu($menu_id, 'Afgelastingen', $wedstrijdmenu);
		mijnclub_addmenupage($menu_id, 'Afgelastingen Deze Week', $afgelastmenu);
		mijnclub_addmenupage($menu_id, 'Afgelastingen Volgende Week', $afgelastmenu);
		mijnclub_addmenupage($menu_id, 'Afgelastingen Vorige Week', $afgelastmenu);
		mijnclub_addmenupage($menu_id, 'Afgelastingen Volgende Speeldag', $afgelastmenu);
		mijnclub_addmenupage($menu_id, 'Afgelastingen Komende Periode', $afgelastmenu);
		
		mijnclub_addmenupage($menu_id, 'Uitslagen' , $wedstrijdmenu);
		mijnclub_addmenupage($menu_id, 'Verslagen' , $wedstrijdmenu);
		mijnclub_addmenupage($menu_id, 'Trainingen' , $wedstrijdmenu);
		
		echo '<div class="updated"><p><strong>Wedstrijdpaginas zijn gemaakt</strong></p></div>';
		return true;
	}
}

//a-f selects the mijnclub menu and activates it as selected menu
function mijnclub_activatemenu() {
	$menu_id = (int) wp_get_nav_menu_object('MijnClub Menu')->term_id;

	$menulocation = 'primary';
	$locations = get_theme_mod('nav_menu_locations');
	$locations[$menulocation] = $menu_id;
	set_theme_mod('nav_menu_locations', $locations);
}

//a-f add submenu to the menu
function mijnclub_addsubmenu($menu_id, $name, $parent) { //adds an unclickable menu item
	$menu = wp_update_nav_menu_item($menu_id, 0, array(
	'menu-item-title' => $name,
	'menu-item-parent-id' => $parent ,
	'menu-item-url' => '#', //makes the menu unclickable
	'menu-item-status' => 'publish' ));
	add_post_meta($menu, 'mijnclub', 'true'); 
	return $menu;
}
//###############################################################
//!! might produce unwanted results with same menu-item-titles !!
//!! at thisLine + 6, with str_replace(xx
//###############################################################
//a-f adds leaf-menu item
function mijnclub_addmenupage($menu_id, $name, $parent) { //adds a page which is clickable
	$id = wp_update_nav_menu_item($menu_id, 0, array(
		'menu-item-object-id' => mijnclub_get_page($name)->ID, //finds page with same Title to link to
		'menu-item-title' => str_replace('Afgelastingen ', '', $name), 
		'menu-item-parent-id' => $parent ,
		'menu-item-object' => 'page',
		'menu-item-type' => 'post_type',
		'menu-item-status' => 'publish' )
	);
	add_post_meta($id, 'mijnclub', 'true'); 
}

//g-f easy way to retrieve a value from an array that is returned by a function i.e returns_array($something)[$key] does not work
function mijnclub_getValue($array, $key) { 
	//allows easy retrieval of array value without temp variables
	return $array[$key];
}

//g-f :untill end of file: g-f printing shortcodes/parts of plugin
function poweredbymijnclub($do) {
	if ($do=='true') {
		return '<p class="powered-by-mijnclub"><a href="http://mijnclub.nu/" target="_blank">Powered by mijnclub.nu</a></p>';
	}
}

function mijnclub_printteampagina($atts) {
	$output = '';
	
	$output .= mijnclub_printteaminfo($atts);
	
	//periodeopties
	if (isset($atts['teampagina']) && $atts['teampagina']=='nee') {
		$output .= mijnclub_printopties();
	} else {
		$output .= mijnclub_printperiodeopties('alleen');
	}
	
	//loading the content of the tabs
	$speelschema = mijnclub_printwedstrijden($atts,'nee',true);
	$uitslagen = mijnclub_printuitslagen($atts,'ja');
	$afgelastingen = mijnclub_printwedstrijden($atts,'afgelast',true);
	$verslagen = mijnclub_printverslagen($atts);
	$trainingen = mijnclub_printtrainingen($atts);
	$stand = mijnclub_printstand($atts);
	
	if (isset($_POST['selectedTab'])) {
		$selectedtab = absint($_POST['selectedTab']);
	} else {
		$selectedtab = 0;
	}
	
	//prints the tabs with the content
	$output .= "<div id=\"tabs\">\n
	<ul>\n
	<li><a href=\"#tabs-1\">Programma</a></li>\n
	<li><a href=\"#tabs-2\">Afgelastingen</a></li>\n
	<li><a href=\"#tabs-3\">Trainingen</a></li>\n
	<li><a href=\"#tabs-4\">Uitslagen</a></li>\n
	<li><a href=\"#tabs-5\">Verslagen</a></li>\n
	<li><a href=\"#tabs-6\">Stand</a></li>\n";

	//hides all the tabs until everything is loaded
	$output .= "<script type='text/javascript'>jQuery(\"#tabs\").hide();</script>";
	
	$wp_version = get_bloginfo('version');
	//selects the tab that was selected
	if ($wp_version >= 3.5) {
		$output .= "<script type='text/javascript'>jQuery(function(){jQuery( \"#tabs\" ).tabs( \"option\", \"active\", $selectedtab );jQuery(\"#tabs\").show();});</script>";
	} else {
		$output .= "<script type='text/javascript'>jQuery(function(){jQuery( \"#tabs\" ).tabs( 'select', $selectedtab );jQuery(\"#tabs\").show();});</script>";
	}

	$output .= "</ul>\n
	<div id=\"tabs-1\">".$speelschema."</div>\n
	<div id=\"tabs-2\">".$afgelastingen."</div>\n
	<div id=\"tabs-3\">".$trainingen."</div>\n
	<div id=\"tabs-4\">".$uitslagen."</div>\n
	<div id=\"tabs-5\">".$verslagen."</div>\n
	<div id=\"tabs-6\">".$stand."</div>\n
	</div>\n";
	
	
	return $output;
}

function mijnclub_printteaminfo($atts) {
	$devOptions = mijnclub_getOptions();
	$output = "";
	if ($atts['team'] != 'alle' && $atts['team'] != '') {
		$url = "http://www.mijnclub.nu/clubs/teams/embed/{$devOptions['clubcode']}/team/{$atts['team']}";
		$htmlstring = mijnclub_getdata($url);
		//replacing &raquo;/&laquo; to prevent warnings being displayed when trying to read in strings with raquo/laquo in them.
		$htmlstring = str_replace('&raquo;', '&#187;', $htmlstring); 
		$htmlstring = str_replace('&laquo;', '&#171;', $htmlstring);
		
		if (!strpos($htmlstring, 'een verouderde bladwijzer')) {
			$htmlobj = simplexml_load_string($htmlstring); //loads in the string of HTML
			if ($htmlobj != false) {
				//loads in all the content 
				//hardcoded file structure, might not work in update mijnclub.nu
				$html = $htmlobj->children();
				$body = $html->body;
				$contentdiv = $body->div;
				$divhead = $contentdiv->div;
				
				//only tries to load the XML when the element exists
				$fotoxml = $contentdiv->xpath('//*[@id="teamfoto"]');
				$foto = (!empty($fotoxml)) ? $fotoxml[0] : '' ;
				$beschrijvingxml = $contentdiv->xpath('//*[@id="team_beschrijving"]');
				$beschrijving = (!empty($beschrijvingxml)) ? $beschrijvingxml[0] : '' ;
				$leidersxml = $contentdiv->xpath('//*[@id="leiders"]');
				$leiders = (!empty($leidersxml)) ? $leidersxml[0] : '' ;
				$spelersxml = $contentdiv->xpath('//*[@id="spelers"]');
				$spelers = (!empty($spelersxml)) ? $spelersxml[0] : '' ;
				
				//only prints the content if they are loaded in correctly
				$output .= "<div class=\"mijnclub-page-team-info\">\n";
				if (gettype($divhead) == 'object') {
					$output .= $divhead->asXML(); 
				}
				if (gettype($foto) == 'object') {
					$output .= $foto->asXML(); 
				}
				if (gettype($beschrijving) == 'object') {
					$output .= $beschrijving->asXML(); 
				}
				if (gettype($leiders) == 'object') {
					if ($leiders->asXML()!='<p id="leiders" class="geen-leiders">Er zijn nog geen leiders aangemaakt voor dit team.</p>') {
						$output .= "<h2>Leiders</h2>\n";
						$output .= $leiders->asXML(); 
					}
				}	
				if (gettype($spelers) == 'object') {
					$output .= "<h2>Spelers</h2>\n";
					$output .= $spelers->asXML();
					$output .= "\n"; 
				}
				$output .= '</div>';
			} else {
				$output = '<p>Mijnclub gegevens konden niet ingeladen worden. Druk op de "Clear XML Cache" knop bij de Mijnclub opties. Als dit het probleem niet oplost zou het kunnen zijn dat de Mijnclub Feed niet werkt op het moment. Probeer het later nog eens.</p>';
			}
		}
	}
	return $output;
}

function mijnclub_printdatumNL($timestamp) {
	$result = '';
	$daynum = date('w', $timestamp);
	switch ($daynum) {
		case 0:
			$result .= 'Zondag ';
			break;
		case 1:
			$result .= 'Maandag ';
			break;
		case 2:
			$result .= 'Dinsdag ';
			break;
		case 3:
			$result .= 'Woensdag ';
			break;
		case 4:
			$result .= 'Donderdag ';
			break;
		case 5:
			$result .= 'Vrijdag ';
			break;
		case 6:
			$result .= 'Zaterdag ';
			break;
	}
	$result .= date('j', $timestamp);
	$monthnum = date('m', $timestamp);
	switch ($monthnum) {
		case '01':
			$result .= ' Januari ';
			break;
		case '02':
			$result .= ' Februari ';
			break;
		case '03':
			$result .= ' Maart ';
			break;
		case '04':
			$result .= ' April ';
			break;
		case '05':
			$result .= ' Mei ';
			break;
		case '06':
			$result .= ' Juni ';
			break;
		case '07':
			$result .= ' Juli ';
			break;
		case '08':
			$result .= ' Augustus ';
			break;
		case '09':
			$result .= ' September ';
			break;
		case '10':
			$result .= ' Oktober ';
			break;
		case '11':
			$result .= ' November ';
			break;
		case '12':
			$result .= ' December ';
			break;
	}
	
	$result .= date('Y', $timestamp);
	return $result;
}

function mijnclub_printafgelastingen($atts) {
	return mijnclub_printwedstrijden($atts,'afgelast');
}

function mijnclub_printwedstrijdenshortcode($atts) {
	return mijnclub_printwedstrijden($atts);
}

function mijnclub_printwedstrijden($atts, $afgelast='nee',$team=false) { 
	$output = '';
	$devOptions = mijnclub_getOptions();
	if (isset($_POST['team'])) {
		$teamnaam = wp_strip_all_tags($_POST['team']); //read teamname from options dropdown		
	} elseif (isset($atts['team'])) {
		$teamnaam = $atts['team']; //read teamname from attributes in shortcode
	} else {
		$teamnaam = 'alle'; //default
	} 
	
	if (isset($_POST['periode'])) {
		$periode = wp_strip_all_tags($_POST['periode']); //read periode from options dropdown
	} elseif (isset($atts['periode'])) {
		$periode = $atts['periode']; //read periode from attributes in shortcode
	} else {
		$periode = 'Komende Periode'; //default
	}
	
	if (isset($_POST['lokatie'])) {
		$lokatie = wp_strip_all_tags($_POST['lokatie']);
	} else {
		$lokatie = 'uit&thuis';
	}
	
		
	$xmlurl = 'http://www.mijnclub.nu/clubs/speelschema/xml/'.$devOptions['clubcode'];
	
	//formatting the correct XML url
	switch ($periode)  {
	case 'Deze Week': 
		$xmlurl .= '/periode,WEEK/';
		break;
	case 'Volgende Week':
		$xmlurl .= '/periode,NEXTWEEK/';
		break;
	case 'Vorige Week':
		$xmlurl .= '/periode,PREVWEEK/';
		break;
	case 'Volgende Speeldag':
		$xmlurl .= '/periode,NEXT/';
		break;
	case 'Komende Periode':
		$xmlurl .= '/periode,/';
		break;	
	}
	if ($teamnaam != ''&&$teamnaam != 'alle') {
		$xmlurl .= "team/$teamnaam/";
	}
	
	$firstparam = true;
	if ($afgelast == 'afgelast') {
		$xmlurl .= '?afgelast=Y';
		$param = false;
	} elseif ($afgelast == '') {
		$afgelast = 'nee';
	}
	
	if ($lokatie == 'uit' || $lokatie == 'thuis' ) {
		if ($firstparam) {
			$xmlurl .= '?lokatie='.$lokatie;
		} else {
			$xmlurl .= '&lokatie='.$lokatie;
		}
	}
	
	$xml = mijnclub_loadxml($xmlurl);
	
	$wedstrijden = $xml->wedstrijden;
	$datumrow = '';
	
	if (!$team) {
		$output .= mijnclub_printperiodeopties('alleen');
	}
	
	//printing the table with the wedstrijden
	if (count($wedstrijden->wedstrijd) > 0) {
		$output .= "<div class=\"mijnclub-page-programma\">\n";
		$output .= "<table border=\"0\">\n";
		foreach($wedstrijden->wedstrijd as $wedstrijd) {
			$datum = $wedstrijd->datum;
			$datumconverted = date('j M \'y',strtotime($datum));
			$aanvang = $wedstrijd->aanvang;
			$aanwezig = $aanvang->attributes();
			$thuisteam = $wedstrijd->thuisteam;
			$uitteam = $wedstrijd->uitteam;
			$scheidsrechter = $wedstrijd->scheidsrechter;
			$soort = $wedstrijd->soort;
			$wedatts = $wedstrijd->attributes();
			$opmerkingen = $wedstrijd->opmerkingen;
			
			if ($datumrow != $datumconverted) {
				$output .= "<tr>\n
				<th colspan=\"7\" class=\"datumhead\">".mijnclub_printdatumNL(strtotime($datum))."</th>\n
				</tr>\n
				<tr class=\"headrow\">\n 
				<td><strong>Thuisploeg</strong></td>\n <td><strong>Uitploeg</strong></td>\n <td><strong>Soort</strong></td>\n <td><strong>Scheids</strong></td>\n <td><strong>Aanv.</strong></td>\n <td><strong>Aanw.</strong></td>\n <td><strong>Info</strong></td>\n
				</tr>\n
				";
				$datumrow = $datumconverted;
			}
			$output .= "<tr>\n"; //prints row of wedstrijd
			$output .= "<td>".$thuisteam."</td>\n<td>".$uitteam."</td>\n<td>".$soort."</td>\n<td>".$scheidsrechter."</td>\n";
			if ($wedatts['afgelast'] == 'ja') {
				$output .= "<td nowrap colspan=\"2\" >\n<div class=\"afgelast\">&#187; AFGELAST &#171;</div>\n</td>\n";
			} else {
				$output .= "<td>".$aanvang."</td>\n<td>".$aanwezig."</td>\n";
			}
			if ($opmerkingen != '') {
				$imgsrc = get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/images/con_info.png';
				$output .= "<td><img src=\"".$imgsrc."\" alt=\"Informatie\" title=\"".$opmerkingen."\"/></td>"; //info icon
			} else {
				$output .= "<td></td>";
			}
			$output .= "</tr>\n";
		}
		$output .= "</table>\n";
		$output .= "</div>\n";
	} else {
		if ($afgelast == 'nee') {
			$output .= "<p>Geen wedstrijden gevonden</p>\n";
		} else {
			$output .= "<p>Geen afgelastingen gevonden</p>\n";
		}
	}
	$output .= poweredbymijnclub($devOptions['showpowered']);
	return $output;
}

function mijnclub_printuitslagen($atts,$soort='ja') {
	$output = "";
	$devOptions = mijnclub_getOptions();
	if (empty($atts['soort'])) { $printsoort='ja';} else {$printsoort = $atts['soort'];$printsoort = $soort; }
	
	if (isset($_POST['team'])) {
		$teamnaam = wp_strip_all_tags($_POST['team']); //read teamname from options dropdown		
	} elseif (isset($atts['team'])) {
		$teamnaam = $atts['team']; //read teamname from attributes in shortcode
	} else {
		$teamnaam = 'alle';
	} 
	
	if ($teamnaam == 'alle') { $printmatrix = false;} else {$printmatrix = true;}
	if (isset($_POST['periode'])) {
		$gekozenperiode = wp_strip_all_tags($_POST['periode']);
	} else {
		$gekozenperiode = 'WEEK'; //default = Laatste Week
	}
	
	$xmlurl = "http://www.mijnclub.nu/clubs/uitslagen/xml/".$devOptions['clubcode'];
	
	$team = false; //defaults to false
	if ($teamnaam != ''&&$teamnaam != 'alle') {
		$xmlurl .= "?team=".$teamnaam;
		$team = true;
	}
	if ($team) {
		$xmlurl .= "&periode=".$gekozenperiode;
	} else {
		$xmlurl .= "?periode=".$gekozenperiode;
	}
	
	$xml = mijnclub_loadxml($xmlurl);
	
	$output .= "<div class=\"mijnclub-uitslagen\">\n";
	$output .= "<form name=\"chooseperiode\" action=\"\" method=\"POST\">";
	$output .= "<div class=\"opties\">\n";
	$output .= "<label class=\"optiesperiodelabel\" for=\"periode\">Periode (Week/Jaar):</label>\n";
	$output .= "<select class=\"optiesperiodeselect\" onchange=\"posttabs2()\" id=\"periode\" name=\"periode\">\n";
	$output .= "<option value=\"WEEK\" ".selected('WEEK',$gekozenperiode,false).">Laatste week</option>\n";
	$output .= "<option value=\"DAG\" ".selected('DAG',$gekozenperiode,false).">Vandaag</option>\n";
	$output .= "<option value=\"MAAND\" ".selected('MAAND',$gekozenperiode,false).">Laatste maand</option>\n";
	if ($team) {
		$output .= "<option value=\"SEIZOEN\" ".selected('SEIZOEN',$gekozenperiode,false).">Hele Seizoen</option>\n";
	}
	$output .= "<option value=\"".date('WY')."\" ".selected(date('WY'),$gekozenperiode,false).">".date('W')."/".date('Y')."</option>";
	
	for ($i = 1; $i < 10; $i++) {
		$periode = date('WY',strtotime("-".$i."week"));
		$week = date('W',strtotime("-".$i."week"));
		$year = date('Y',strtotime("-".$i."week"));

		$output .= "<option value=\"".$periode."\" ".selected($periode,$gekozenperiode,false).">".$week."/".$year."</option>";
	}
	$output .= "</select><br>\n";
	$output .= "</div>\n";
	$output .= "</form>\n";

	//printing the table with uitslagen
	if (count($xml->wedstrijd) > 0) {
		$output .= "<table border=\"0\" class=\"uitslagentable\">\n";
		$output .= "<tr>\n<td><strong>Datum</strong></td>\n<td><strong>Thuisploeg</strong></td>\n<td><strong>Uitploeg</strong></td>\n <td><strong>Uitslag</strong></td>\n";
		if ($printsoort == 'ja') {
			$output .= "<td><strong>Soort</strong></td>\n";
		}
		$output .= "</tr>\n";
		foreach($xml->wedstrijd as $wedstrijd) {
			$uitslag = $wedstrijd->uitslag;
			$datum = $wedstrijd->datum;
			$datumconverted = date('j M',strtotime($datum)); //convert date to suitable format
			$soort = $wedstrijd->soort;
			$thuisteam = $wedstrijd->thuisteam;
			$uitteam = $wedstrijd->uitteam;
			$wedatts = $wedstrijd->attributes();
			
			$output .= "<tr>\n"; //prints 'uitslagen'
			$output .= "<td>".$datumconverted."</td>\n<td>".$thuisteam."</td>\n<td>".$uitteam."</td>\n";
			
			
			if ($wedatts['afgelast'] == 'ja') {
				if ($printsoort == 'ja') {
					$output .= "<td nowrap colspan=\"2\" >\n<div class=\"afgelast\">&#187; AFGELAST &#171;</div>\n</td>\n";
				} else {
					$output .= "<td nowrap>\n<div class=\"afgelast\">&#187; AFGELAST &#171;</div>\n</td>\n";
				}
			} else {
				$output .= "<td>".$uitslag."</td>\n";
				if ($printsoort == 'ja') {
					$output .= "<td>".$soort."</td>\n";
				}
			}
			$output .= "</tr>\n";
		}
		$output .= "</table>\n";
		//link to uitslagen matrix
		if ($printmatrix) {
			$output .= "<p><a href=\"http://www.mijnclub.nu/clubs/teams/popup/".$devOptions['clubcode']."/team/".$teamnaam."?layout=alle-uitslagen\" target=\"_blank\">Uitslagenmatrix (opent in nieuw venster)</a></p>\n";
		}
	} else {
		$output .= "<p>Geen uitslagen gevonden</p>\n";
		//link to uitslagen matrix
		if ($printmatrix) {
			$output .= "<p><a href=\"http://www.mijnclub.nu/clubs/teams/popup/".$devOptions['clubcode']."/team/".$teamnaam."?layout=alle-uitslagen\" target=\"_blank\">Uitslagenmatrix (opent in nieuw venster)</a></p>\n";
		}
	}
	
	$output .= poweredbymijnclub($devOptions['showpowered']);
	$output .= '</div>';
	return $output;
}

function mijnclub_printverslagen($atts) {
	$output = "";
	$devOptions = mijnclub_getOptions();
	$clubcode = $devOptions['clubcode'];
	
	if (isset($_POST['team'])) {
		$teamnaam = wp_strip_all_tags($_POST['team']); //read teamname from options dropdown		
	} elseif (isset($atts['team'])) {
		$teamnaam = $atts['team']; //read teamname from attributes in shortcode
	} else {
		$teamnaam = 'alle';
	}
	
	$url = "http://www.mijnclub.nu/clubs/wedstrijdverslagen/embed/".$clubcode."/";
	if ($teamnaam != ''&&$teamnaam != 'alle') {
		$url .= "team/".$teamnaam."/";
	}
	
	$xmlstring = mijnclub_getdata($url);
	
	//finding positions of the body tags
	//hardcoded body tag, possibly wrong in update in mijnclub
	$bodyopen = "<body class=\"contentpane com_clubs wedstrijdverslagen\">"; 
	$bodyclose = "</body>";
	$bodyopenpos = strpos($xmlstring, $bodyopen);
	$bodyclosepos = strpos($xmlstring, $bodyclose);
	
	//finding the substring of the body
	$lenghttofind = ($bodyclosepos-$bodyopenpos)-strlen($bodyopen);
	$bodystring = substr($xmlstring, $bodyopenpos+strlen($bodyopen), $lenghttofind);
	
	$output .= "<div class=\"mijnclub-page-verslagen\">\n";
	$output .= $bodystring; //only returns the content in body tags
	
	$output .= poweredbymijnclub($devOptions['showpowered']);
	$output .= "</div>";
	return $output;
}

function mijnclub_printstand ($atts) {
	$devOptions = mijnclub_getOptions();
	
	if (isset($_POST['team'])) {
		$teamnaam = wp_strip_all_tags($_POST['team']); //read teamname from options dropdown		
	} elseif (isset($atts['team'])) {
		$teamnaam = $atts['team']; //read teamname from attributes in shortcode
	} else {
		$teamnaam = 'alle';
	} 
	
	if ($teamnaam != 'alle') {
		$xmlurl = "http://www.mijnclub.nu/clubs/teams/embed/".$devOptions['clubcode']."/team/".$teamnaam."?layout=stand&format=xml";
		$stand = mijnclub_loadxml($xmlurl);
		if (gettype($stand) == "string") {
			return "<p>".$stand."</p>".poweredbymijnclub($devOptions['showpowered']);
		}
		if (!empty($stand)) {	
			$stand = $stand->asXML();
			return $stand;
		} else {
			return "<p>Het team is niet gevonden</p>\n".poweredbymijnclub($devOptions['showpowered']);
		}
	} else {
		return "<p>Geen stand beschikbaar als je alle teams selecteerd</p>\n".poweredbymijnclub($devOptions['showpowered']);
	}
}

function mijnclub_printopties() { //prints both options
	return mijnclub_printteamopties().mijnclub_printperiodeopties();
}

function mijnclub_printteamopties($alleen = '') {
	$output = "";
	
	$devOptions = mijnclub_getOptions();
	
	$xmlurl = "http://www.mijnclub.nu/clubs/teams/xml/".$devOptions['clubcode'];
	
	$xml = mijnclub_loadxml($xmlurl);
	
	$output .= "<form name=\"chooseoptions\" action=\"\" method=\"POST\">\n";
	$output .= "<div class=\"opties\">\n";
	$output .= "<label class=\"optieslabel\" for=\"team\">Team:</label>\n";
	$output .= "<select class=\"optiesselect\" onchange=\"posttabs()\" id=\"team\" name=\"team\">\n";
	
	if (isset($_POST['team'])) { //reads the currently chosen team
		$gekozenteam = wp_strip_all_tags($_POST['team']);
	} else {
		$gekozenteam = '';
	}
	
	$output .= "<option value=\"alle\" ".selected('alle',$gekozenteam,false).">Alle teams</option>\n"; //selects 'alle' as default selected

	$cat = ""; //empty categorie at first team
	$first = true;
	foreach ($xml->team as $team) { //prints each team as an option
		$naam = $team->naam;
		$soort = (string) $team->soort;
		if ($cat != $soort) { //whenever a new category is found, an optgroup tag is inserted
			$cat = $soort;
			if (!$first) {
				$output .= "</optgroup>\n";
			}
			$output .= "<optgroup label=\"".$cat."\">\n";
			$first = false;
		}
		$output .= '<option value="'.$naam.'" '.selected($naam,$gekozenteam,false).'>'.$naam.'</option>\n';
	}
	
	$output .= "</optgroup>\n</select><br>\n";
	
	if ($alleen == 'alleen') {
		$output .= "</div>\n";
		$output .= "</form>\n";
	}
	return $output;
}

function mijnclub_printperiodeopties($alleen = '') {
	$output = "";
	if ($alleen == 'alleen' ) { 
		$output .= "<form name=\"chooseoptions\" action=\"\" method=\"POST\">\n";
		$output .= "<div class=\"opties\">\n";
	}
	$output .= "<label class=\"optieslabel\" for=\"periode\">Periode:</label>\n"; //start periodeoptie
	$output .= "<select class=\"optiesselect\" onchange=\"posttabs()\" id=\"periode\" name=\"periode\" >\n";
	
	if (isset($_POST['periode'])) {
		$periode = wp_strip_all_tags($_POST['periode']);
	} else {
		$periode = 'Komende Periode'; //default
	}
	
	$output .= "<option value=\"Deze Week\" ".selected('Deze Week',$periode,false).">Deze Week</option>\n";
	$output .= "<option value=\"Volgende Week\" ".selected('Volgende Week',$periode,false).">Volgende Week</option>\n";
	$output .= "<option value=\"Vorige Week\" ".selected('Vorige Week',$periode,false).">Vorige Week</option>\n";
	$output .= "<option value=\"Volgende Speeldag\" ".selected('Volgende Speeldag',$periode,false).">Volgende Speeldag</option>\n";
	$output .= "<option value=\"Komende Periode\" ".selected('Komende Periode',$periode,false).">Komende Periode</option>\n";


	$output .= "</select><br>\n";
	
	if (isset($_POST['lokatie'])) {
		$lokatie = wp_strip_all_tags($_POST['lokatie']);
	} else {
		$lokatie = "uit&thuis"; //default
	}
	
	$output .= "<label class=\"optieslabel\" for=\"lokatie\">Lokatie:</label>\n";//start lokatie optie
	$output .= "<select class=\"optiesselect\" onchange=\"posttabs()\" id=\"lokatie\" name=\"lokatie\" >\n";
	
	$output .= "<option value=\"uit&thuis\" ".selected('uit&thuis',$lokatie,false).">uit&thuis</option>\n";
	$output .= "<option value=\"uit\" ".selected('uit',$lokatie,false).">uit</option>\n";
	$output .= "<option value=\"thuis\" ".selected('thuis',$lokatie,false).">thuis</option>\n";

	$output .= "</select><br>\n";
	

	$output .= "</div>\n";
	$output .= "</form>\n";
	
	return $output;
}

function mijnclub_printtrainingen($atts, $options = true) {
	$output = "";
	$devOptions = mijnclub_getOptions();
	
	if (isset($_POST['dag'])) {
		$dag = wp_strip_all_tags($_POST['dag']); //read teamname from options dropdown		
	} elseif (isset($atts['dag'])) {
		$dag = $atts['dag']; //read teamname from attributes in shortcode
	} else {
		$dag = 'alle'; //default
	} 
	
	if (isset($_POST['team'])) {
		$gekozenteam = wp_strip_all_tags($_POST['team']); //read teamname from options dropdown		
	} elseif (isset($atts['team'])) {
		$gekozenteam = $atts['team']; //read teamname from attributes in shortcode
	} else {
		$gekozenteam = 'alle'; //default
	} 
	$output .= "<div class=\"mijnclub-trainingen\">\n";
	if ($options) {
		$output .= "<form name=\"choosedag\" action=\"\" method=\"POST\">\n";
		$output .= "<div class=\"opties\">\n";
		$output .= "<label class=\"optieslabel\" for=\"dag\">Dag:</label>\n"; //start periodeoptie
		$output .= "<select onchange=\"posttabs3()\" class=\"optiesselect\" id=\"dag\" name=\"dag\" >\n";
	
		$output .= "<option value=\"alle\" ".selected('alle',$dag,false).">Hele week</option>\n";
		$output .= "<option value=\"maandag\" ".selected('maandag',$dag,false).">Maandag</option>\n";
		$output .= "<option value=\"dinsdag\" ".selected('dinsdag',$dag,false).">Dinsdag</option>\n";
		$output .= "<option value=\"woensdag\" ".selected('woensdag',$dag,false).">Woensdag</option>\n";
		$output .= "<option value=\"donderdag\" ".selected('donderdag',$dag,false).">Donderdag</option>\n";
		$output .= "<option value=\"vrijdag\" ".selected('vrijdag',$dag,false).">Vrijdag</option>\n";
		$output .= "<option value=\"zaterdag\" ".selected('zaterdag',$dag,false).">Zaterdag</option>\n";
		$output .= "<option value=\"zondag\" ".selected('zondag',$dag,false).">Zondag</option>\n";

		$output .= "</select><br>\n";
		//end day select
		
		$output .= "</div>\n";
		$output .= "</form>\n";
	}
	
	//xmlurl to read trainingen
	$xmlurl = "http://www.mijnclub.nu/clubs/trainingen/xml/".$devOptions['clubcode']."/";
	
	if ($dag != 'alle') {
		$xmlurl .= "dag/".$dag."/";
	}
	if ($gekozenteam != '' && $gekozenteam != 'alle') {
		$xmlurl .= "team/".$gekozenteam."/";
	}
	
	$xml = mijnclub_loadxml($xmlurl);
	
	$trainingen = $xml->training;
	
	$dagen = array(
		'maandag' => 'false',
		'dinsdag' => 'false',
		'woensdag' => 'false',
		'donderdag' => 'false',
		'vrijdag' => 'false',
		'zaterdag' => 'false',
		'zondag' => 'false'
	);
	
	//reads in all data
	if (count($trainingen) > 0) {
		foreach($trainingen as $training) {
			$dag = (string) mijnclub_getValue($training->attributes(),2);
			//sets the day to true
			$dagen[$dag] = 'true';
			$fields = $training->field;
			$starttijd = mijnclub_getValue($fields[0]->attributes(),1);
			$eindtijd = mijnclub_getValue($fields[1]->attributes(),1);
			//only reads titel field when it is exists
			if (count($fields) > 2) {
				$titel = mijnclub_getValue($fields[2]->attributes(),1);
			} else {
				$titel = '*geen titel*';
			}
			$veldenelement = $training->velden->entry;
			$teamselement = $training->teamnamen->entry;
			
			//creates new array to make sure old values are removed
			$teamarray = array(); 
			$veldarray	= array();
			
			foreach($veldenelement as $veld) {
				$veldarray[] = mijnclub_getValue($veld->attributes(),1);
			}
			
			if ($teamselement !== null) {
				foreach($teamselement as $team) {
					$teamarray[] = mijnclub_getValue($team->attributes(),1);
				}
			} else {
				$teamarray = array();
			}
			
			$trainingarray = array(
				'dag' => $dag,
				'starttijd' => $starttijd,
				'eindtijd' => $eindtijd,
				'titel' => $titel,
				'teams' => $teamarray,
				'velden' => $veldarray			
			);
			$masterarray[] = $trainingarray;
		}
		
		foreach ($dagen as $dag => $do) {
			// only prints day if it exists in trainingen
			if ($do=='true') {
			$output .= "<table>\n";
			$output .= "<th colspan=\"4\" class=\"daghead\">".$dag."</th>\n";
			$output .= "<tr class=\"headrow\">\n";
			$output .= "<td><strong>Tijd</strong></td>\n";
			$output .= "<td><strong>Titel</strong></td>\n";
			$output .= "<td><strong>Veld</strong></td>\n";
			$output .= "<td><strong>Teams</strong></td>\n";
			$output .= "</tr>\n";
			foreach ($masterarray as $trainingout) {
				if ($trainingout['dag'] == $dag) {
					//prints a training that is on the day
					$output .= "<tr>\n";
					$output .= "<td width=\"20%\">".$trainingout['starttijd']." - ".$trainingout['eindtijd']."</td>\n";
					$output .= "<td width=\"35%\">".$trainingout['titel']."</td>\n";
					$output .= "<td width=\"15%\">\n";
					foreach ($trainingout['velden'] as $veld) {
						$output .= $veld." ";
					}
					$output .= "</td>\n";
					$output .= "<td width=\"30%\">\n";
					foreach ($trainingout['teams'] as $team) {
						$output .= $team." ";
					}
					$output .= "</td>\n";
					$output .= "</tr>\n";
				}
			}
			$output .= "</table>\n";
			}
		}
	} else {
		$output .= "<p>Geen trainingen gevonden</p>\n";
	}
	$output .= poweredbymijnclub($devOptions['showpowered']);
	$output .= '</div>';
	return $output;
}
?>