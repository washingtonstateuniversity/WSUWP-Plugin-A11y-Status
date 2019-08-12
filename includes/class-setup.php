<?php
/**
 * WSUWP A11y Status Setup: Setup class
 *
 * @package Setup
 * @since 0.1.0
 */

namespace WSUWP\A11yStatus\Init;

use WSUWP\A11yStatus\admin;
use WSUWP\A11yStatus\WSU_API;
use WSUWP\A11yStatus\notices;
use WSUWP\A11yStatus\settings;
use WSUWP\A11yStatus\user;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The WSUWP A11y Status setup class.
 *
 * @since 0.1.0
 */
class Setup {
	/**
	 * The plugin slug.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public static $slug = 'wsuwp_a11y_status';

	/**
	 * The plugin file basename.
	 *
	 * @since 0.8.0
	 * @var array
	 */
	private $basename;

	/**
	 * Instantiates WSUWP A11y Status singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Setup An instance of the Setup class.
	 */
	public static function get_instance( $file ) {
		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new Setup();

			$instance->setup_hooks();
			$instance->includes();
			$instance->basename = $file;
		}

		return $instance;
	}

	/**
	 * An empty constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		/* Nothing doing. */
	}

	/**
	 * Activates the WSUWP A11y Status plugin.
	 *
	 * @since 0.1.0
	 */
	public static function activate() {
		// Nothing for now.
	}

	/**
	 * Deactivates the WSUWP A11y Status plugin.
	 *
	 * @since 0.1.0
	 */
	public static function deactivate() {
		// Nothing for now.
	}

	/**
	 * Uninstalls the WSUWP A11y Status plugin.
	 *
	 * @since 0.7.0
	 */
	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Delete all user metadata saved by the plugin.
		$users = get_users( array( 'fields' => array( 'ID' ) ) );

		foreach ( $users as $user ) {
			user\delete_a11y_user_meta( $user );
			delete_user_meta( absint( $user->ID ), '_wsu_nid' );
		}
	}

	/**
	 * Loads the WP API actions and hooks.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Admin hooks.
		add_filter( 'manage_users_columns', 'WSUWP\A11yStatus\admin\add_a11y_status_user_column' );
		add_filter( 'manage_users_custom_column', 'WSUWP\A11yStatus\admin\manage_a11y_status_user_column', 10, 3 );
		add_filter( 'user_row_actions', 'WSUWP\A11yStatus\admin\add_a11y_status_user_row_action', 10, 2 );
		add_action( 'admin_init', 'WSUWP\A11yStatus\admin\handle_a11y_status_actions' );
		add_filter( 'bulk_actions-users', 'WSUWP\A11yStatus\admin\add_a11y_status_user_bulk_action', 10, 1 );
		add_filter( 'handle_bulk_actions-users', 'WSUWP\A11yStatus\admin\handle_a11y_status_bulk_actions', 10, 3 );

		// Notices hooks.
		add_action( 'admin_notices', 'WSUWP\A11yStatus\notices\user_a11y_status_notice__remind' );
		add_action( 'admin_notices', 'WSUWP\A11yStatus\notices\user_a11y_status_notice__action' );

		// Settings hooks.
		add_action( 'edit_user_profile', 'WSUWP\A11yStatus\settings\usermeta_form_field_nid' );
		add_action( 'show_user_profile', 'WSUWP\A11yStatus\settings\usermeta_form_field_nid' );
		add_action( 'edit_user_profile_update', 'WSUWP\A11yStatus\settings\usermeta_form_field_nid_update' );
		add_action( 'personal_options_update', 'WSUWP\A11yStatus\settings\usermeta_form_field_nid_update' );

		// User hooks.
		add_action( 'wp_login', 'WSUWP\A11yStatus\user\handle_user_login', 10, 2 );
		add_action( 'user_register', 'WSUWP\A11yStatus\user\update_a11y_user_meta', 10, 1 );
	}

	/**
	 * Includes required files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		// Admin page functions.
		require __DIR__ . '/admin.php';

		// The WSU API handler class.
		require __DIR__ . '/class-wsu-api.php';

		// The plugin formatting API.
		require __DIR__ . '/formatting.php';

		// Notices and user messaging functions.
		require __DIR__ . '/notices.php';

		// The plugin settings API.
		require __DIR__ . '/settings.php';

		// The plugin user API.
		require __DIR__ . '/user.php';
	}

	/**
	 * Enqueues the plugin admin styles.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {
		$plugin_meta = get_plugin_data( $this->basename );
		wp_enqueue_style(
			'wsuwp-a11y-status-dashboard',
			plugins_url( 'css/main.css', __DIR__ ),
			array(),
			$plugin_meta['Version']
		);
	}
}
