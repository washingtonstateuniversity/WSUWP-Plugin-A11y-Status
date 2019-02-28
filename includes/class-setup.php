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
	protected $version = '0.4.1';

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
		// Nothing for now.
	}

	/**
	 * Deactivates the WSUWP A11y Status plugin.
	 *
	 * @since 0.1.0
	 */
	public static function deactivate() {
		// Clear the a11y status transient.
		self::flush_transient_cache();
	}

	/**
	 * Loads the WP API actions and hooks.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'admin_init', array( $this, 'set_properties' ) );
		add_action( 'admin_init', array( $this, 'get_a11y_status_response' ), 20 );
		add_action( 'admin_notices', array( $this, 'user_a11y_status_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wsuwp_a11y_status_update', array( $this, 'get_a11y_status_response' ) );
		add_filter( 'manage_users_columns', array( $this, 'add_a11y_status_user_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'manage_a11y_status_user_column' ), 10, 3 );
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
	 * Gets the full a11y certification status of the given user.
	 *
	 * Takes an email address or WSU NID and retrieves the full accessibility
	 * status data for that user if it exists in the cached transient.
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_email Optional. The email address or WSU NID of a user to check. Defaults to the current user.
	 * @return array|object|false The accessibility status data for the given user, or a WP_Error object if no data was found, or false if the user is not found.
	 */
	public static function get_a11y_status_by_email( $user_email = '' ) {
		$a11y_status = get_transient( 'a11y_status_wsuwp_a11y_status' );

		if ( ! $a11y_status ) {
			// WP Error object in the format: new WP_Error( 'error_code', 'Message', $optional_data )
			return new WP_Error( 'no_a11y_data', __( 'No stored WSU A11y Status data.', 'wsuwp-a11y-status' ) );
		}

		if ( '' === $user_email ) {
			$current_user = wp_get_current_user();

			if ( ! $current_user->exists() ) {
				return false;
			}

			$user_email = implode( explode( '@', $current_user->user_email, -1 ) );
		}

		// Get the email username if given a full email string.
		if ( false !== strpos( $user_email, '@' ) ) {
			$user_email = implode( explode( '@', $user_email, -1 ) );
		}

		if ( array_key_exists( $user_email, $a11y_status ) ) {
			return $a11y_status[ $user_email ];
		}

		return false;
	}

	/**
	 * Gets the date a given user's a11y certification expires.
	 *
	 * Retrieves the date a given user's WSU Accessibility certification
	 * expires, formatted based on the WP site option `date_format`.
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_email Optional. The email address or WSU NID of a user to check. Defaults to the current user.
	 * @return string|object|false The expiration date for the given user, or a WP_Error object if the data is not found, or false the user is not certified.
	 */
	public static function get_user_a11y_expiration_date( $user_email = '' ) {
		$user_status = self::get_a11y_status_by_email( $user_email );

		if ( is_wp_error( $user_status ) ) {
			return $user_status;
		}

		if ( $user_status && 'False' !== $user_status['isCertified'] ) {
			$expires = date_create_from_format( 'M j Y g:iA', $user_status['Expires'] );

			return date_format( $expires, get_option( 'date_format' ) );
		}

		return false;
	}

	/**
	 * Gets the time remaining until a11y certification expires.
	 *
	 * Returns the time remaining until a given user's WSU Accessibility
	 * certification expires, formatted into a human readable format using
	 * the WP `human_time_diff` function {@see https://developer.wordpress.org/reference/functions/human_time_diff/}
	 * The time is returned in a human readable format such as "1 hour",
	 * "5 mins", or "2 days".
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_email Optional. The email address or WSU NID of a user to check. Defaults to the current user.
	 * @return string|object|false The expiration date for the given user, or a WP_Error object if the data is not found, or false the user is not certified.
	 */
	public static function get_user_a11y_time_to_expiration( $user_email = '' ) {
		$user_status = self::get_a11y_status_by_email( $user_email );

		if ( is_wp_error( $user_status ) ) {
			return $user_status;
		}

		if ( $user_status && 'False' !== $user_status['isCertified'] ) {
			$user_expiry_date   = date_create_from_format( 'M j Y g:iA', $user_status['Expires'] );
			$time_to_expiration = human_time_diff( date_format( $user_expiry_date, 'U' ) );

			return $time_to_expiration;
		}

		return false;
	}

	/**
	 * Determines whether a given user is A11y Training certified.
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_email Optional. The email address or WSU NID of a user to check. Defaults to the current user.
	 * @return bool|object True if the user is certified, false if not, and a WP_Error object if the data is not found.
	 */
	public static function is_user_certified( $user_email = '' ) {
		$user_status = self::get_a11y_status_by_email( $user_email );

		if ( is_wp_error( $user_status ) ) {
			return $user_status;
		}

		if ( $user_status && 'False' !== $user_status['isCertified'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if a user's a11y certification expires in less than a month.
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_email Optional. The email address or WSU NID of a user to check. Defaults to the current user.
	 * @return bool|object True if the user's certification expires in less than one month, false if not, and a WP_Error object if the data is not found.
	 */
	public static function is_user_a11y_lt_one_month( $user_email = '' ) {
		$time_to_expiration = self::get_user_a11y_time_to_expiration( $user_email );

		if ( is_wp_error( $time_to_expiration ) ) {
			return $time_to_expiration;
		}

		if ( false !== strpos( $time_to_expiration, 'months' ) || false !== strpos( $time_to_expiration, 'years' ) ) {
			return false;
		}

		return true;
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
	 * Enqueues the plugin admin styles.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wsuwp-a11y-status-dashboard', plugins_url( 'css/main.css', __DIR__ ), array(), $this->version );
	}

	/**
	 * Displays admin notices based on the current user's WSU A11y status.
	 *
	 * This will display an error message if the user is not certified and a
	 * warning message if the user's certification will expire in less than one
	 * month. Neither message is dismissible.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function user_a11y_status_notices() {
		$training_link = 'http://go.wsu.edu/web-accessibility';
		$is_certified  = self::is_user_certified();

		// Display error message if the current user is not certified.
		if ( false === $is_certified ) {
			$class      = 'notice-error';
			$message    = __( 'You need to take the WSU Accessibility Training.', 'wsuwp-a11y-status' );
			$expiration = '';
		} elseif ( true === $is_certified ) {
			// Display warning message the user's certification expires soon.
			if ( self::is_user_a11y_lt_one_month() ) {
				$class      = 'notice-warning';
				$message    = __( 'WSU Accessibility Certification Expiring Soon.', 'wsuwp-a11y-status' );
				$expiration = self::get_user_a11y_expiration_date();
			} else {
				return;
			}
		} else {
			return;
		}
		?>
		<div class="wsuwp-a11y-status notice <?php echo esc_attr( $class ); ?>">
			<p>
				<strong><?php echo esc_html( $message ); ?></strong>
				<?php
				if ( '' !== $expiration ) {
					printf(
						/* translators: 1: the human readble time remaining; 2: the expiration date */
						__( 'Your certification expires in %1$s, on %2$s.', 'wsuwp-a11y-status' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						esc_html( self::get_user_a11y_time_to_expiration() ),
						esc_html( $expiration )
					);
				}
				?>
				<strong><a href="<?php echo esc_url( $training_link ); ?>" target="_blank" rel="noopener noreferrer">Take the training <span class="screen-reader-text">(opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Adds an A11y Status column to the user table on the Users screen.
	 *
	 * Callback method for the `manage_users_columns` filter. Adds a column to
	 * display custom accessibility status data on the Users screen.
	 *
	 * @since 0.3.0
	 *
	 * @param array $columns Required. An array of column name => label.
	 * @return array An array of column name => label to modify the users table list columns.
	 */
	public function add_a11y_status_user_column( $columns ) {
		$columns['a11y_status'] = __( 'A11y Status', 'wsuwp-a11y-status' );

		return $columns;
	}

	/**
	 * Manages the output of the custom A11y Status column in the Users table.
	 *
	 * Callback method for the `manage_users_custom_column` filter. For
	 * accessibility training certified users it displays a message with the
	 * remaining time until their certification expires, and for non-certified
	 * users is displays "none."
	 *
	 * @since 0.3.0
	 *
	 * @param string $output      The custom column output. Defaults to empty.
	 * @param string $column_name The name of the column to filter.
	 * @param int    $user_id     The ID of the currently listed user.
	 * @return string HTML message to output in the column row.
	 */
	public function manage_a11y_status_user_column( $output, $column_name, $user_id ) {
		if ( 'a11y_status' === $column_name ) {
			$user         = get_userdata( $user_id );
			$user_email   = $user->user_email;
			$is_certified = self::is_user_certified( $user_email );

			if ( false === $is_certified ) {
				$output = '<span class="notice-error">None</span>';
			} elseif ( true === $is_certified ) {
				$class   = ( self::is_user_a11y_lt_one_month( $user_email ) ) ? 'notice-warning' : 'notice-success';
				$expires = self::get_user_a11y_time_to_expiration( $user_email );
				$output  = sprintf(
					'<span class="%1$s">Expires in %2$s</span>',
					esc_attr( $class ),
					esc_html( $expires )
				);
			}

			return $output;
		}

		return $output;
	}

}
