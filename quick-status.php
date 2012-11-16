<?php
/*
Plugin Name: Status
Plugin URI: http://premium.wpmudev.org/
Description: Quickly post your status
Version: 1.6.1
Author: Ve Bailovity (Incsub)
Author URI: http://premium.wpmudev.org
WDP ID: 242

Copyright 2009-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );
	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	}
}
/* --------------------------------------------------------------------- */

define ('WDQS_PLUGIN_SELF_DIRNAME', basename(dirname(__FILE__)), true);
define ('WDQS_PLUGIN_CORE_BASENAME', plugin_basename(__FILE__), true);
define ('WDQS_PUBLISH_CAPABILITY', 'publish_wdqs_posts', true);

//Setup proper paths/URLs and load text domains
if (is_multisite() && defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDQS_PLUGIN_LOCATION', 'mu-plugins', true);
	define ('WDQS_PLUGIN_BASE_DIR', WPMU_PLUGIN_DIR, true);
	define ('WDQS_PLUGIN_URL', WPMU_PLUGIN_URL, true);
	$textdomain_handler = 'load_muplugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . WDQS_PLUGIN_SELF_DIRNAME . '/' . basename(__FILE__))) {
	define ('WDQS_PLUGIN_LOCATION', 'subfolder-plugins', true);
	define ('WDQS_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . '/' . WDQS_PLUGIN_SELF_DIRNAME, true);
	define ('WDQS_PLUGIN_URL', WP_PLUGIN_URL . '/' . WDQS_PLUGIN_SELF_DIRNAME, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDQS_PLUGIN_LOCATION', 'plugins', true);
	define ('WDQS_PLUGIN_BASE_DIR', WP_PLUGIN_DIR, true);
	define ('WDQS_PLUGIN_URL', WP_PLUGIN_URL, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else {
	// No textdomain is loaded because we can't determine the plugin location.
	// No point in trying to add textdomain to string and/or localizing it.
	wp_die(__('There was an issue determining where Status plugin is installed. Please reinstall.'));
}
$textdomain_handler('wdqs', false, WDQS_PLUGIN_SELF_DIRNAME . '/languages/');

/**
 * Removes WordPress wpautop filter on WDQS entries.
 */
function wdqs_kill_wpautop_on_ql_posts ($content) {
	if (preg_match('/class=[\'"]wdqs\b/', $content)) remove_filter('the_content', 'wpautop');
	return $content;
}

/**
 * Adds the status update publishing capability to the list.
 */
function wdqs_wp_capabilities_list () {
	global $wp_roles;
	if (!is_object($wp_roles)) return;
	$wp_roles->use_db = true;
	$administrator = $wp_roles->get_role('administrator');
	if (!$administrator->has_cap(WDQS_PUBLISH_CAPABILITY)) $wp_roles->add_cap('administrator', WDQS_PUBLISH_CAPABILITY);
}
add_filter('wp', 'wdqs_wp_capabilities_list');

/**
 * Filters capabilities probing for our posting ability.
 */
function wdqs_wp_user_capability_check ($all, $requested, $args=array()) {
	if (WDQS_PUBLISH_CAPABILITY != $args[0]) return $all; // Not a Status capability
	if (isset($all[WDQS_PUBLISH_CAPABILITY]) && $all[WDQS_PUBLISH_CAPABILITY]) return $all; // We already have this granted

	$data = new Wdqs_Options;
	$minimum_capacity = $data->get('subscribers') && current_user_can('subscriber') ? 'read' : ($data->get('contributors') ? 'edit_posts' : 'publish_posts');
	$publish_capability = $data->get('contributors') || $data->get('subscribers') ? $minimum_capacity : 'publish_posts';

	// ----- Not overriding, or using legacy overrides -----
	if (!$data->get('override_publishing_settings') && isset($all[$publish_capability]) && $all[$publish_capability]) return array_merge($all, array(WDQS_PUBLISH_CAPABILITY => 1)); // We're not overriding, or we're in legacy override mode
	else if (!$data->get('override_publishing_settings')) return $all; // Not overriding

	// ----- Actual capability override ----- */
	// ... TBD
	return $all;
}
add_filter('user_has_cap', 'wdqs_wp_user_capability_check', 10, 3);

require_once WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_installer.php';
Wdqs_Installer::check();

require_once WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_options.php';

require_once (WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_widget_status.php');
add_action('widgets_init', create_function('', "register_widget('Wdqs_WidgetStatus');"));

if (is_admin()) {
	require_once WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_admin_form_renderer.php';
	require_once WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_admin_pages.php';
	Wdqs_AdminPages::serve();
} else {
	//require_once WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_public_pages.php';
	//Wdqs_PublicPages::serve();
	add_filter('the_content', 'wdqs_kill_wpautop_on_ql_posts', 1);
	require_once WDQS_PLUGIN_BASE_DIR . '/lib/class_wdqs_public_pages.php';
	Wdqs_PublicPages::serve();
}