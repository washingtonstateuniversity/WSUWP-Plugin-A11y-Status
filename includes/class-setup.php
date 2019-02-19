<?php
/**
 * WSUWP A11y Status Setup: WSUWP_A11y_Status class
 *
 * @package WSUWP_A11y_Status
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The WSUWP A11y Status setup class.
 *
 * @since 0.1.0
 */
class WSUWP_A11y_Status {
	/**
	 * The plugin slug.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public static $slug = 'wsuwp_a11y_status';

	/**
	 * The plugin version number.
	 *
	 * @todo Set this using a setter method instead.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $version = '0.2.0';

	/**
	 * The WSU Accessibility Training API endpoint.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $url;

	/**
	 * One or more user IDs to check with the API.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $users;

	/**
	 * The WSU Accessibility Training API response.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $wsu_api_response;

	/**
	 * Instantiates WSUWP A11y Status singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return object WSUWP_A11y_Status
	 */
	public static function get_instance() {
		static $instance = null;

		// Only set up and activate the plugin if it hasn't already been done.
		if ( null === $instance ) {
			$instance = new WSUWP_A11y_Status();
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
		// Register a scheduled action only on activation.
		if ( ! wp_next_scheduled( 'wsuwp_a11y_status_update' ) ) {
			wp_schedule_event( time(), 'hourly', 'wsuwp_a11y_status_update' );
		}
	}

	/**
	 * Deactivates the WSUWP A11y Status plugin.
	 *
	 * @since 0.1.0
	 */
	public static function deactivate() {
		// Clear the a11y status transient.
		self::flush_transient_cache();

		// Remove the scheduled event on plugin deactivation.
		wp_clear_scheduled_hook( 'wsuwp_a11y_status_update' );
	}

	/**
	 * Loads the WP API actions and hooks.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'admin_init', array( $this, 'set_properties' ) );
		add_action( 'admin_menu', array( $this, 'a11y_status_menu' ) );
		add_action( 'wsuwp_a11y_status_update', array( $this, 'get_a11y_status_response' ) );
	}

	/**
	 * Sets the WSU A11y Status plugin properties.
	 *
	 * Creates an array of usernames to feed to the API using the email
	 * addresses of registered WP users on the current site. Also sets the API
	 * endpoint URL.
	 *
	 * @todo Set the endpoint URL using plugin options.
	 * @todo Allow manually adding additional usernames to check from plugin
	 *       options via merge.
	 *
	 * @since 0.2.0
	 */
	public function set_properties() {
		$wp_users = get_users( array( 'fields' => array( 'user_email' ) ) );

		$usernames = array();
		foreach ( $wp_users as $wp_user ) {
			// Save only the email usernames (everything to the last `@` sign).
			$usernames[] = implode( explode( '@', $wp_user->user_email, -1 ) );
		}

		// Define the WSU A11y Training status API endpoint.
		$this->set_endpoint_props(
			array(
				'url'   => 'https://webserv.wsu.edu/accessibility/training/service',
				'users' => $usernames,
			)
		);

	}

	/**
	 * Sets the WSU Accessibility Training API endpoint properties.
	 *
	 * @since 0.1.0
	 *
	 * @param $props {
	 *     @type string       $url   Required. A valid API endpoint to check on WSU Accessibility Training status.
	 *     @type string|array $users Required. One or more WSU Net IDs to check.
	 * }
	 * @return void
	 */
	private function set_endpoint_props( $props ) {
		$defaults = array(
			'url'   => $this->url,
			'users' => $this->users,
		);

		$props = wp_parse_args( $props, $defaults );

		if ( ! is_array( $props['users'] ) ) {
			$props['users'] = array_map( 'trim', explode( ',', $props['users'] ) );
		}

		$this->url   = esc_url_raw( $props['url'] );
		$this->users = array_map( 'sanitize_user', $props['users'] );
	}

	/**
	 * Gets the WSU Accessibility Training status info from the WSU API.
	 *
	 * Connect to the API to retrieve info for a given user ID(s) in JSON
	 * format and parse it.
	 *
	 * @since 0.1.0
	 *
	 * @return array|false Array of parsed JSON WSU API a11y status details or false if the request failed.
	 */
	public function get_a11y_status_response() {

		// Try to get plugin details from the cache before checking the API.
		$this->wsu_api_response = get_transient( 'a11y_status_' . self::$slug );

		// If a cached value exists, return it and don't execute a new request.
		if ( false !== $this->wsu_api_response ) {
			return $this->wsu_api_response;
		}

		if ( empty( $this->users ) ) {
			$this->error( 'WSU API Error: Please supply at least one WSU NID for the API query.' );

			return false;
		}

		// Preliminary checks completed; proceed with API request.
		$response = array();

		foreach ( $this->users as $user ) :

			// Build the request URI on a per-user basis.
			$request_uri = sprintf( '%1$s?NID=%2$s', $this->url, $user );

			$raw_response = wp_remote_get( esc_url_raw( $request_uri ) );

			if ( is_wp_error( $raw_response ) ) {
				$this->error( $raw_response->get_error_message() );

				return false;
			}

			$response_code = wp_remote_retrieve_response_code( $raw_response );

			if ( 200 !== (int) $response_code ) {
				$this->error( sprintf(
					'WSU API request failed. The request for <%1$s> returned HTTP code: %2$s',
					esc_url_raw( $request_uri ),
					$response_code
				) );

				return false;
			}

			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

			$this->wsu_api_response[ $user ] = array_shift( $response );

		endforeach;

		if ( ! is_wp_error( $this->wsu_api_response ) ) {
			// Save results of a successful API call to a 12-hour transient cache.
			set_transient( 'a11y_status_' . self::$slug, $this->wsu_api_response, 43200 );
		}

		return $this->wsu_api_response;
	}

	/**
	 * Deletes the `a11y_status_{plugin-slug}` transient to flush the cache.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if successful, false otherwise or if transient doesn't exist.
	 */
	public static function flush_transient_cache() {
		if ( get_transient( 'a11y_status_' . self::$slug ) ) {
			$deleted = delete_transient( 'a11y_status_' . self::$slug );
		}

		return false;
	}

	/**
	 * Prints errors if debugging is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @param string|array $message The error message to display. Accepts a single string or an array of strings.
	 * @param string $error_code Optional. A computer-readable string to identify the error.
	 * @return void|false The HTML formatted error message if debug display is enabled and false if not.
	 */
	private function error( $message, $error_code = '500' ) {
		if ( ! WP_DEBUG || ! WP_DEBUG_DISPLAY || ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		if ( is_array( $message ) ) {
			foreach ( $message as $msg ) {
				printf(
					/* translators: 1: the plugin name, 2: the error message */
					__( '<div class="notice notice-error"><p><strong>%1$s error:</strong> %2$s</p></div>', 'wsuwp-a11y-status' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( self::$slug ),
					esc_html( $msg['message'] )
				);
			}
		} else {
			printf(
				/* translators: 1: the plugin name, 2: the error message */
				__( '<div class="notice notice-error"><p><strong>%1$s error:</strong> %2$s</p></div>', 'wsuwp-a11y-status' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( self::$slug ),
				esc_html( $message )
			);
		}
	}

	/**
	 * Adds a new dashboard page for the A11y Status plugin with load hooks.
	 *
	 * This creates a new submenu under the Users section of the main admin
	 * menu. It also adds a callback to the `load-{admin page}` hook that fires
	 * whenever the new dashboard page is loaded. This function is a callback
	 * for the `user_admin_menu` action. {@see https://codex.wordpress.org/Plugin_API/Action_Reference/admin_menu}.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if user menu added. False if user lacks permission.
	 */
	public function a11y_status_menu() {

		if ( current_user_can( 'list_users' ) ) {
			$hook = add_submenu_page(
				'users.php',
				'A11y Training Status',
				'A11y Status',
				'read',
				self::$slug,
				array( $this, 'display_a11y_status_dashboard' )
			);

			add_action( 'load-' . $hook, array( $this, 'load_a11y_status_dashboard_cb' ) );

			return true;
		}

		return false;
	}

	/**
	 * Retrieves the admin dashboard page template.
	 *
	 * This function is a callback for `add_dashboard_page()`, called in the
	 * `$this->a11y_status_menu()` function.
	 *
	 * @since 0.1.0
	 */
	public function display_a11y_status_dashboard() {
		/**
		 * Loads the admin dashboard page.
		 *
		 * @since 0.1.0
		 */
		include plugin_dir_path( __DIR__ ) . 'templates/admin-a11y-training-status.php';
	}

	/**
	 * Adds the A11y Status plugin scripts to the admin dashboard page.
	 *
	 * @since 0.1.0
	 */
	public function load_a11y_status_dashboard_cb() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues the plugin admin styles.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wsuwp-a11y-status-dashboard', plugins_url( 'css/main.css', __DIR__ ), array(), $this->version );
	}

}
