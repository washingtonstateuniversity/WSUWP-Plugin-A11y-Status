<?php
/*
Plugin Name: WSUWP A11y Status
Version: 1.0.0-alpha-1
Description: A plugin to view users' WSU Accessibility Training status in the Admin area.
Author: washingtonstateuniversity, Adam Turner
Author URI: https://github.com/washingtonstateuniversity/
Plugin URI: https://github.com/washingtonstateuniversity/wsuwp-plugin-a11y-status
Text Domain: wsuwp-a11y-status
Requires at least: 4.7
Tested up to: 5.2.0
Requires PHP: 5.6
*/

namespace WSUWP\A11yStatus;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Loads the core plugin class.
 *
 * @since 0.1.0
 */
require_once __DIR__ . '/includes/class-setup.php';

// Starts things up.
add_action( 'plugins_loaded', __NAMESPACE__ . '\load_wsuwp_a11y_status' );

// Flushes rules on activation and cleans up on deactivation.
register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Init\Setup', 'activate' ) );
register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Init\Setup', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\Init\Setup', 'uninstall' ) );

/**
 * Creates an instance of the WSUWP A11y Status class.
 *
 * @since 0.1.0
 *
 * @return Setup An instance of the Setup class.
 */
function load_wsuwp_a11y_status() {
	$wsuwp_a11y_status = Init\Setup::get_instance();
	$wsuwp_a11y_status->setup_hooks();
	$wsuwp_a11y_status->set_properties( __FILE__ );

	return $wsuwp_a11y_status;
}
