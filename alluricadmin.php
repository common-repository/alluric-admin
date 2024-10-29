<?php
/*
Plugin Name: Alluric Admin
Plugin URI: http://vagrantconcept.com/downloads/alluric-admin/
Description: Give your admin panel a new look, with drop-down menus. Credit goes to Dean Robinson for backend and Ozh for drop-down feature. <strong>WordPress 2.5+ only</strong>
Author: Justin Wong
Version: 0.9.1
Author URI: http://vagrantconcept.com/
*/ 

function wp_admin_fluency_css() {
	echo '<link rel="stylesheet" type="text/css" href="' . get_settings('siteurl') . '/wp-content/plugins/alluric-admin/wp-admin.css?version=0.9.1" />'."\n";
}

add_action('admin_head', 'wp_admin_fluency_css',100);

function wp_ozh_adminmenu() {
	$menu = wp_ozh_adminmenu_build();
		
	$ozh_menu = '</ul><ul id="ozhmenu">'; // close original <ul id="dashmenu"> and add ours
	
	foreach ($menu as $k=>$v) {
		$url 	= $v['url'];
		$name 	= $k;
		$anchor = $v['name'];
		$class	= $v['class'];

		$ozh_menu .= "\t<li class='ozhmenu_toplevel'><a href='$url'$class>$anchor</a>";
		if (is_array($v['sub'])) {
			
			$ulclass='';
			if ($class) $ulclass = " class='ulcurrent'";
			$ozh_menu .= "\n\t\t<ul$ulclass>\n";

			foreach ($v['sub'] as $subk=>$subv) {
				$suburl = $subv['url'];
				$subanchor = $subv['name'];
				$subclass='';
				if (array_key_exists('class',$subv)) $subclass=$subv['class'];
				$ozh_menu .= "\t\t\t<li class='ozhmenu_sublevel'><a href='$suburl'$subclass>$subanchor</a></li>\n";
			}
			$ozh_menu .= "\t</ul>\n";
		}
		$ozh_menu .="\t</li>\n";
	}
	
	echo $ozh_menu;
	
}
 
 
/* Core stuff : builds an array populated with all the infos needed for menu and submenu */
function wp_ozh_adminmenu_build () {
	global $menu, $submenu, $plugin_page, $pagenow;
	
	/* Most of the following garbage are bits from admin-header.php,
	 * modified to populate an array of all links to display in the menu
	 */
	 
	$self = preg_replace('|^.*/wp-admin/|i', '', $_SERVER['PHP_SELF']);
	$self = preg_replace('|^.*/plugins/|i', '', $self);
	
	get_admin_page_parent();
	
	$altmenu = array();
	
	/* Step 1 : populate first level menu as per user rights */
	foreach ($menu as $item) {
		// 0 = name, 1 = capability, 2 = file
		if ( current_user_can($item[1]) ) {
			if ( file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") )
				$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/admin.php?page={$item[2]}";			
			else
				$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/{$item[2]}";

			if (( strcmp($self, $item[2]) == 0 && empty($parent_file)) || ($parent_file && ($item[2] == $parent_file)))
			$altmenu[$item[2]]['class'] = " class='current'";
			
			$altmenu[$item[2]]['name'] = $item[0];

			/* Windows installs may have backslashes instead of slashes in some paths, fix this */
			$altmenu[$item[2]]['name'] = str_replace(chr(92),chr(92).chr(92),$altmenu[$item[2]]['name']);
		}
	}
	
	/* Step 2 : populate second level menu */
	foreach ($submenu as $k=>$v) {
		foreach ($v as $item) {
			if (array_key_exists($k,$altmenu) and current_user_can($item[1])) {
				
				// What's the link ?
				$menu_hook = get_plugin_page_hook($item[2], $k);
				if (file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") || ! empty($menu_hook)) {
					if ( 'admin.php' == $pagenow )
						$link = get_settings('siteurl') . "/wp-admin/admin.php?page={$item[2]}";
					else
						$link = get_settings('siteurl') . "/wp-admin/{$k}?page={$item[2]}";
				} else {
					$link = get_settings('siteurl') . "/wp-admin/{$item[2]}";
				}
				/* Windows installs may have backslashes instead of slashes in some paths, fix this */
				$link = str_replace(chr(92),chr(92).chr(92),$link);
				
				$altmenu[$k]['sub'][$item[2]]['url'] = $link;
				
				// Is it current page ?
				$class = '';
				if ( (isset($plugin_page) && $plugin_page == $item[2] && $pagenow == $k) || (!isset($plugin_page) && $self == $item[2] ) ) $class=" class='current'";
				if ($class) {
					$altmenu[$k]['sub'][$item[2]]['class'] = $class;
					$altmenu[$k]['class'] = $class;
				}
				
				// What's its name again ?
				$altmenu[$k]['sub'][$item[2]]['name'] = $item[0];
			}
		}
	}
	
	// Uncomment to see how neat it is now !
	/**
	global $wpdb;
	$wpdb->wp_ozh_adminmenu_neat_array = "<pre style='font-size:80%'>Our Oh-So-Beautiful-4-Levels-".htmlentities(print_r($altmenu,true))."</pre>";
	add_action('admin_footer', create_function('', 'global $wpdb; echo $wpdb->wp_ozh_adminmenu_neat_array;')); 
	/**/

	return ($altmenu);
}


function wp_ozh_adminmenu_js($menu = '') {
	echo <<<JS
<script type="text/javascript"><!--//--><![CDATA[//><!--
jQuery(document).ready(function() {
	// Remove unnecessary links in the top right corner
	var uselesslinks = jQuery('#user_info p').html();
	if (uselesslinks) {
		uselesslinks = uselesslinks.replace(/ \| <a href="http:\/\/codex.wordpress.org.*$/i, '');
		jQuery('#user_info p').html(uselesslinks);
		jQuery('#user_info').css('z-index','81');
		// Remove original menus
		jQuery('#sidemenu').hide();
		jQuery('#adminmenu').hide();
		jQuery('#submenu').html('');
		jQuery('#dashmenu').hide();
		jQuery('#user_info').css('right','1em');
		// jQueryfication of the Son of Suckerfish Drop Down Menu
		// Original at: http://www.htmldog.com/articles/suckerfish/dropdowns/
		jQuery('#ozhmenu li.ozhmenu_toplevel').each(function() {
			jQuery(this).mouseover(function(){
				jQuery(this).addClass('ozhmenu_over');
				if (jQuery.browser.msie) {ozhmenu_hide_selects(true);}
			}).mouseout(function(){
				jQuery(this).removeClass('ozhmenu_over');
				if (jQuery.browser.msie) {ozhmenu_hide_selects(false);}
			});
		});
		// Function to hide <select> elements (display bug with MSIE)
		function ozhmenu_hide_selects(hide) {
			var hidden = (hide) ? 'hidden' : 'visible';
			jQuery('select').css('visibility',hidden);
		}
		// Show our new menu
		jQuery('#ozhmenu').show();
		// Make title header smaller
		jQuery('#wphead #viewsite a').css('font-size','10px');
		jQuery('#wphead h1').css('font-size','25px');
	}
})
//--><!]]></script>
JS;

}


function wp_ozh_adminmenu_css() {
	echo <<<CSS
<style type="text/css">
#ozhmenu {margin: 0 auto 25px;}
#ozhmenu li { /* all list items */
	display:inline;
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	margin:0;
	padding:0;
	white-space:nowrap;
	float: left;
	width: 1*; /* maybe needed for some Opera ? */
}
#ozhmenu a { /* all links */
	text-decoration:none;
	font-family: Arial;
	color: #333;
	line-height:200%;
	padding:0px 10px;
	display:block;
	width:1*;  /* maybe needed for some Opera ? */
}
#ozhmenu li:hover,
#ozhmenu li.ozhmenu_over,
#ozhmenu li .current {
	background: #fff;
	color: #000;
}
#ozhmenu .ozhmenu_sublevel a:hover,
#ozhmenu .ozhmenu_sublevel a.current,
#ozhmenu .ozhmenu_sublevel a.current:hover {
	background: #e4f2fd;
	color: #555;
}
#ozhmenu li ul { /* drop down lists */
	padding: 0;
	margin: 0;
	padding-bottom:5px;
	list-style: none;
	position: absolute;
	background: white;
	opacity:0.95;
	filter:alpha(opacity=95);
	border:1px solid #c6d9e9;
	width: 1*;  /* maybe needed for some Opera ? */
	min-width:6em;
	left: -999em; /* using left instead of display to hide menus because display: none isn't read by screen readers */
	list-style-position:auto;
	list-style-type:auto;
}
#ozhmenu li ul li { /* dropped down lists item */
	float:none;
	text-align:left;
}
#ozhmenu li ul li a { /* links in dropped down list items*/
	margin:0px;
	color:#666;
}
#ozhmenu li:hover ul, #ozhmenu li.ozhmenu_over ul { /* lists dropped down under hovered list items */
	left: auto;
	z-index:999999;
}
#ozhmenu li a #awaiting-mod {
	position: absolute;
	margin-left: 0.1em;
	font-size: 0.8em;
	background-image: url(images/comment-stalk.gif);
	background-repeat: no-repeat;
	background-position: -160px bottom;
	height: 1.7em;
	width: 1em;
}
#ozhmenu li.ozhmenu_over a #awaiting-mod, #ozhmenu li a:hover #awaiting-mod {
	background-position: -2px bottom;
}
#ozhmenu li a #awaiting-mod span {
	color: #fff;
	top: -0.3em;
	right: -0.5em;
	position: absolute;
	display: block;
	height: 1.3em;
	line-height: 1.3em;
	padding: 0 0.8em;
	background-color: #2583AD;
}
#ozhmenu li.ozhmenu_over a #awaiting-mod span, #ozhmenu li a:hover #awaiting-mod span {
	background-color:#D54E21;
}
#ozhmenu .current {
	border:0px; /* MSIE insists on having this */
}
#ozhmenu li ul li a.current:before {
	content: "\\00BB \\0020";
	color:#d54e21;
}
</style>
CSS;
}

/***** Hook things in ****/

if (is_admin()) {
	add_action('init', create_function('', 'wp_enqueue_script("jquery");')); 
}
add_action('dashmenu', 'wp_ozh_adminmenu');
add_action('admin_head', 'wp_ozh_adminmenu_head');

function wp_ozh_adminmenu_head() {
	wp_ozh_adminmenu_css();
	wp_ozh_adminmenu_js();
}

?>