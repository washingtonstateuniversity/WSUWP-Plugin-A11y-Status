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
	 * @since 1.0.0 Converted to public static access.
	 * @var string
	 */
	public static $basename;

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
			$instance        = new Setup();
			Setup::$basename = $file;

			$instance->setup_hooks();
			$instance->includes();
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
		/**
		 * Track activation with an option because the activation hook fires
		 * before the plugin is actually set up, which prevents taking certain
		 * actions in this method.
		 *
		 * @link https://stackoverflow.com/questions/7738953/is-there-a-way-to-determine-if-a-wordpress-plugin-is-just-installed/13927297#13927297
		 */
		$options = get_option( self::$slug . '_plugin-status' );
		if ( ! $options ) {
			add_option( self::$slug . '_plugin-status', array( 'status' => 'activated' ) );
		} else {
			$options['status'] = 'activated';
			update_option( self::$slug . '_plugin-status', $options );
		}
	}

	/**
	 * Deactivates the WSUWP A11y Status plugin.
	 *
	 * @since 0.1.0
	 */
	public static function deactivate() {
		// Set plugin status to 'deactivated'.
		$options           = get_option( self::$slug . '_plugin-status' );
		$options['status'] = 'deactivated';

		update_option( self::$slug . '_plugin-status', $options );
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
			delete_user_meta( $user->ID, self::$slug );
			delete_user_meta( $user->ID, '_wsu_nid' );
		}

		// Unregister plugin settings.
		unregister_setting(
			self::$slug,
			self::$slug . '_options'
		);

		// Delete plugin options.
		delete_option( self::$slug . '_plugin-status' );
		delete_option( self::$slug . '_options' );
	}

	/**
	 * Loads the WP API actions and hooks.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'manage_plugin_status' ) );
		add_action( 'admin_init', array( $this, 'handle_plugin_db_update_actions' ) );

		// Admin hooks.
		add_filter( 'manage_users_columns', 'WSUWP\A11yStatus\admin\add_a11y_status_user_column' );
		add_filter( 'manage_users_custom_column', 'WSUWP\A11yStatus\admin\manage_a11y_status_user_column', 10, 3 );
		add_filter( 'manage_users_sortable_columns', 'WSUWP\A11yStatus\admin\manage_a11y_status_user_column_sortable', 10, 1 );
		add_filter( 'user_row_actions', 'WSUWP\A11yStatus\admin\add_a11y_status_user_row_action', 10, 2 );
		add_action( 'admin_init', 'WSUWP\A11yStatus\admin\handle_a11y_status_actions' );
		add_filter( 'bulk_actions-users', 'WSUWP\A11yStatus\admin\add_a11y_status_user_bulk_action', 10, 1 );
		add_filter( 'handle_bulk_actions-users', 'WSUWP\A11yStatus\admin\handle_a11y_status_bulk_actions', 10, 3 );
		add_action( 'admin_menu', 'WSUWP\A11yStatus\admin\add_admin_page' );

		// Notices hooks.
		add_action( 'admin_notices', 'WSUWP\A11yStatus\notices\user_a11y_status_notice__remind' );
		add_action( 'admin_notices', 'WSUWP\A11yStatus\notices\user_a11y_status_notice__action' );

		// Settings hooks.
		add_action( 'admin_init', 'WSUWP\A11yStatus\settings\register_settings' );
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
		// Get the plugin version, if it exists, or set to fallback '0.0.0'.
		$plugin  = get_option( self::$slug . '_plugin-status' );
		$version = ( isset( $plugin['version'] ) ) ? $plugin['version'] : '0.0.0';

		wp_enqueue_style(
			'wsuwp-a11y-status-dashboard',
			plugins_url( 'css/main.css', __DIR__ ),
			array(),
			$version
		);
	}

	/**
	 * Manages the plugin status based on version number and database keys.
	 *
	 * Checks on the plugin status to watch for updates and activation and calls
	 * additional actions as needed.
	 *
	 * @since 1.0.0
	 */
	public function manage_plugin_status() {
		if ( ! is_admin() || ! function_exists( 'get_plugin_data' ) ) {
			return;
		}

		$status = get_option( self::$slug . '_plugin-status' );
		$plugin = get_plugin_data( self::$basename );

		$saved_version = ( isset( $status['version'] ) ) ? $status['version'] : '0.0.0';
		$new_version   = ( isset( $plugin['Version'] ) ) ? $plugin['Version'] : '0.0.0';

		// Update the version if just activated or the versions don't match.
		if ( 'activated' === $status['status'] || $saved_version !== $new_version ) {
			$status = array(
				'status'  => 'active',
				'version' => $new_version,
			);
			update_option( self::$slug . '_plugin-status', $status );

			// Sets the default plugin settings if they don't already exist.
			settings\set_default_settings();
		}

		// Check for out-of-date database keys if current user can do updates.
		if ( current_user_can( 'update_plugins' ) ) {
			$a11y_status = get_user_meta( get_current_user_id(), self::$slug );
			if (
				! empty( $a11y_status ) &&
				( isset( $a11y_status['isCertified'] ) || isset( $a11y_status[0]['isCertified'] ) )
			) {
				$status['status'] = 'need_db_update';
				update_option( self::$slug . '_plugin-status', $status );
			}
		}
	}

	/**
	 * Watches for plugin database update requests and handles them.
	 *
	 * @since 1.0.0
	 */
	public function handle_plugin_db_update_actions() {
		if ( ! isset( $_REQUEST['action'] ) ) {
			return;
		}

		if ( self::$slug . '_db_update' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			// Check permissions.
			if ( ! current_user_can( 'update_plugins' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
			}

			// Check the nonce.
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], self::$slug . '_db_update' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
			}

			// Checks completed, go ahead and run the update.
			$this->upgrade();

			// Redirect to a clean URL after the upgrade is finished.
			wp_safe_redirect(
				add_query_arg(
					array( 'update_a11y' => self::$slug . '_db_update_complete' ),
					admin_url( 'plugins.php' )
				)
			);
			exit();
		}
	}

	/**
	 * Handles tasks required for plugin upgrades.
	 *
	 * @since 1.0.0
	 */
	private function upgrade() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$users = get_users(
			array(
				'meta_key'     => self::$slug,
				'meta_value'   => '',
				'meta_compare' => '!=',
				'fields'       => array( 'ID' ),
			)
		);
		foreach ( $users as $user ) {
			$a11y_status = get_user_meta( $user->ID, self::$slug );

			// If keys exist in the old syntax, update them.
			if ( isset( $a11y_status['isCertified'] ) ) {
				$was_certified    = isset( $a11y_status['ever_certified'] ) ? $a11y_status['ever_certified'] : 0;
				$sanitized_status = array(
					'is_certified'  => $a11y_status['isCertified'],
					'was_certified' => $was_certified,
					'expire_date'   => $a11y_status['Expires'],
					'last_checked'  => $a11y_status['last_checked'],
					'training_url'  => $a11y_status['trainingURL'],
				);
				update_user_meta( $user->ID, self::$slug, $sanitized_status );
			}
			if ( isset( $a11y_status[0]['isCertified'] ) ) {
				$was_certified    = isset( $a11y_status[0]['ever_certified'] ) ? $a11y_status[0]['ever_certified'] : 0;
				$sanitized_status = array(
					'is_certified'  => $a11y_status[0]['isCertified'],
					'was_certified' => $was_certified,
					'expire_date'   => $a11y_status[0]['Expires'],
					'last_checked'  => $a11y_status[0]['last_checked'],
					'training_url'  => $a11y_status[0]['trainingURL'],
				);
				update_user_meta( $user->ID, self::$slug, $sanitized_status );
			}
		}

		$options           = get_option( self::$slug . '_plugin-status' );
		$options['status'] = 'active';
		update_option( self::$slug . '_plugin-status', $options );
	}
}
