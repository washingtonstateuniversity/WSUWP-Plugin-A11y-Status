<?php
/*
Plugin Name: WSUWP A11y Status
Version: 0.4.1
Description: A plugin to view users' WSU Accessibility Training status in the Admin area.
Author: washingtonstateuniversity, Adam Turner
Author URI: https://github.com/washingtonstateuniversity/
Plugin URI: https://github.com/washingtonstateuniversity/wsuwp-plugin-a11y-status
Text Domain: wsuwp-a11y-status
Requires at least: 3.5
Tested up to: 5.2.0
Requires PHP: 5.3
*/

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
add_action( 'after_setup_theme', 'load_wsuwp_a11y_status' );

// Flushes rules on activation and cleans up on deactivation.
register_activation_hook( __FILE__, array( 'WSUWP_A11y_Status', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WSUWP_A11y_Status', 'deactivate' ) );

/**
 * Creates an instance of the WSUWP A11y Status class.
 *
 * @since 0.1.0
 *
 * @return object An instance of WSUWP_A11y_Status
 */
function load_wsuwp_a11y_status() {
	$wsuwp_a11y_status = WSUWP_A11y_Status::get_instance();
	$wsuwp_a11y_status->setup_hooks();

	return $wsuwp_a11y_status;
}
