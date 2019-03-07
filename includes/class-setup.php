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
	protected $version = '0.5.0-alpha';

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
		// @todo Fetch the API data on plugin activation.
	}

	/**
	 * Deactivates the WSUWP A11y Status plugin.
	 *
	 * @since 0.1.0
	 */
	public static function deactivate() {
		// @todo Something.
	}

	/**
	 * Loads the WP API actions and hooks.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'wp_login', array( $this, 'update_a11y_status_usermeta' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_a11y_status_actions' ) );
		add_action( 'admin_notices', array( $this, 'user_a11y_status_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'manage_users_columns', array( $this, 'add_a11y_status_user_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'manage_a11y_status_user_column' ), 10, 3 );
		add_filter( 'user_row_actions', array( $this, 'add_a11y_status_user_row_action' ), 10, 2 );
	}

	/**
	 * Sets the WSU Accessibility Training API endpoint properties.
	 *
	 * @since 0.1.0
	 *
	 * @param $props {
	 *     @type string $url   Required. A valid API endpoint to check on WSU Accessibility Training status.
	 *     @type array  $users Required. An associative array of one or more WSU Net IDs to check in the format WP_ID => WSU_NID.
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
			$this->error( 'Users list in `set_endpoint_props()` must be an array.' );

			return false;
		}

		$this->url   = esc_url_raw( $props['url'] );
		$this->users = array_map( 'sanitize_user', $props['users'] );
	}

	/**
	 * Builds the list of username(s) (WSU IDs) to check.
	 *
	 * Super Admins and Administrators -- or anyone with the capability to
	 * access the admin Users screen -- will need to collect the accessibility
	 * training status of all registered users. All other users should only
	 * gather data from the API for themselves.
	 *
	 * @since 0.5.0
	 *
	 * @param object $current_user A WP_User instance of the current user.
	 * @return array An associative array of user_id => wsu_nid key-value pairs.
	 */
	private function get_usernames_list( $current_user ) {

		// Use WP_User object because current_user_can() isn't available yet.
		if ( $current_user->allcaps['list_users'] ) {
			// Add all users to the users array if current user is (super)admin.
			$wp_users = get_users( array( 'fields' => array( 'ID', 'user_email' ) ) );
		} else {
			// Add only the current user to the users array if not an admin.
			$wp_users[] = $current_user;
		}

		$usernames = array();
		foreach ( $wp_users as $wp_user ) {
			/*
			 * @todo Add a check here to try getting the WSU ID from a user
			 *       meta field (editable on the user profile page) before
			 *       trying to build it ourselves out of the email address.
			 *       This will allow specifiying the WSU ID for users not
			 *       using a WSU email address.
			 */

			// Save only the email usernames (everything to the last `@` sign).
			$usernames[ $wp_user->ID ] = implode( explode( '@', $wp_user->user_email, -1 ) );
		}

		return $usernames;
	}

	/**
	 * Updates one or more user's metadata with their WSU A11y Training status.
	 *
	 * A callback method for the `wp_login` action hook, which provides the user
	 * login and WP_User object for a successfully authenticated user on login.
	 * It is triggered when a users logs in by the `wp_signon()` function.
	 * This calls the internal method to fetch data from the API and update the
	 * user(s) metadata accordingly.
	 *
	 * @since 0.5.0
	 *
	 * @param string $user_login The authenticated user's login.
	 * @param object $user       The WP_User object for the authenticated user.
	 * @return array Associative array of user_id => `update_user_meta` responses (int|bool, meta ID if the key didn't exist, true on updated, false on failure or no change); or false if the request failed.
	 */
	public function update_a11y_status_usermeta( $user_login, $user ) {

		// Define the WSU A11y Training status API endpoint.
		$this->set_endpoint_props(
			array(
				'url'   => 'https://webserv.wsu.edu/accessibility/training/service',
				'users' => $this->get_usernames_list( $user ),
			)
		);

		foreach ( $this->users as $user_id => $username ) {
			/*
			 * @todo Add a check so that for certified users we only fetch new
			 * data when they're nearing expiration.
			 */

			// Fetch the accessibility training status data.
			$user_status = $this->fetch_a11y_status_response( $this->url, $username );

			// Save the accessibility training status to user metadata.
			$this->wsu_api_response[ $user_id ] = update_user_meta( $user_id, self::$slug, $user_status );

		}

		return $this->wsu_api_response;
	}

	/**
	 * Updates an individual user's metadata with their WSU A11y Training status.
	 *
	 * @since 0.6.0
	 *
	 * @param int $user_id The WP user ID of the user to update.
	 * @return array Array of user_id => `update_user_meta` responses (int|bool, meta ID if the key didn't exist, true on updated, false on failure or no change); or false if the request failed.
	 */
	public function update_a11y_status_by_user_id( $user_id ) {
		$api_url  = 'https://webserv.wsu.edu/accessibility/training/service';
		$wp_user  = get_user_by( 'id', $user_id );
		$username = implode( explode( '@', $wp_user->user_email, -1 ) );

		// Fetch the accessibility training status data.
		$user_status = $this->fetch_a11y_status_response( $api_url, $username );

		// Save the accessibility training status to user metadata.
		$this->wsu_api_response[ $user_id ] = update_user_meta( $user_id, self::$slug, $user_status );

		return $this->wsu_api_response;
	}

	/**
	 * Gets the WSU Accessibility Training status info from the WSU API.
	 *
	 * Connect to the API to retrieve info for given username(s) in JSON
	 * format and parse it, then add several additional items to the resulting
	 * array and return it. The returned array should include the following
	 * key-value pairs:
	 *
	 * array (
	 *     'isCertified'    => (string) "True" || "False",
	 *     'Expires'        => (string, datetime in format `M j Y g:iA`) "Mar 7 2019 6:52PM",
	 *     'trainingURL'    => (string) "https://url.to.wsu.accessibility.training/",
	 *     'last_checked'   => (string, datetime in format `YYYY-MM-DD HH:MM:SS`) "2019-03-05 09:48:57",
	 *     'ever_certified' => (bool) true || false,
	 * )
	 *
	 * @since 0.5.0
	 *
	 * @param string $url      The WSU Accessibility Training Status API url.
	 * @param string $username The WSU NID of the user to retrieve data for.
	 * @return array|false Array of accessibility training status data for the given username.
	 */
	private function fetch_a11y_status_response( $url, $username ) {

		// Build the request URI on a per-user basis.
		$request_uri = sprintf( '%1$s?NID=%2$s', $url, $username );

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

		$user_status = array_shift( $response );

		// Save date of last API check.
		$user_status['last_checked'] = current_time( 'mysql' );

		// Save user metadata to track if a user was ever certified in the past.
		if ( 'True' === $user_status['isCertified'] ) {
			$user_status['ever_certified'] = true;
		}

		return $user_status;
	}

	/**
	 * Gets the full a11y certification status of the given user.
	 *
	 * Takes a WordPress user ID and retrieves the full WSU accessibility
	 * training status data for that user if it exists in the user metadata.
	 *
	 * @since 0.5.0
	 *
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return array|false The accessibility status data for the given user or false if the user data is not found.
	 */
	public static function get_user_a11y_status( $user_id = '' ) {
		$user_id = ( '' !== $user_id ) ? absint( $user_id ) : get_current_user_id();

		$a11y_status = get_user_meta( $user_id, self::$slug, true );

		if ( ! empty( $a11y_status ) ) {
			return $a11y_status;
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
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return string|false The expiration date for the given user or false the user is not certified or not found.
	 */
	public static function get_user_a11y_expiration_date( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) && 'False' !== $user_status['isCertified'] ) {
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
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return string|false The expiration date for the given user or false the user is not certified or not found.
	 */
	public static function get_user_a11y_time_to_expiration( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) && 'False' !== $user_status['isCertified'] ) {
			$user_expiry_date   = date_create_from_format( 'M j Y g:iA', $user_status['Expires'] );
			$time_to_expiration = human_time_diff( date_format( $user_expiry_date, 'U' ) );

			return $time_to_expiration;
		}

		return false;
	}

	/**
	 * Determines time remaining in a user's 30-day A11y Training grace period.
	 *
	 * Returns the number of days remaining in a user's 30-day grace period,
	 * calculated by checking the difference between the current date and the
	 * user's WP registration date. Returns the string "0 days" if the grace
	 * period has expired by any number of days.
	 *
	 * @since 0.5.0
	 *
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return string|false A string containing the number of days remaining in human-readable format or "0 days" if the period has expired. False if no data found or user is certified.
	 */
	public static function get_user_a11y_grace_period_remaining( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( empty( $user_status ) || 'False' === $user_status['isCertified'] ) {
			$wp_user = ( '' !== $user_id ) ? get_user_by( 'id', $user_id ) : wp_get_current_user();

			$registration = date_create( $wp_user->user_registered );
			$diff         = $registration->diff( date_create() );

			if ( 1 <= $diff->m ) {
				// Grace period of one month has passed.
				$days_remaining = '0 days';
			} else {
				// The days remaining in the grace period.
				$days_remaining = human_time_diff( date_format( $registration, 'U' ), current_time( 'timestamp' ) );
			}

			return $days_remaining;
		}

		return false;
	}

	/**
	 * Determines whether a given user is A11y Training certified.
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return bool True if the user is certified, false if not or if the data is not found.
	 */
	public static function is_user_certified( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) && 'False' !== $user_status['isCertified'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether a user has been A11y Training certified in the past.
	 *
	 * @since 0.5.0
	 *
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return bool True if the user has ever been certified and false if not.
	 */
	public static function was_user_certified( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status['ever_certified'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if a user's a11y certification expires in less than a month.
	 *
	 * @since 0.2.0
	 *
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return bool True if the user's certification expires in less than one month and false if not, or if the data is not found.
	 */
	public static function is_user_a11y_lt_one_month( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) && 'False' !== $user_status['isCertified'] ) {
			$expiry = date_create_from_format( 'M j Y g:iA', $user_status['Expires'] );
			$diff   = $expiry->diff( date_create() );

			if ( 1 > $diff->m ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes the 'wsuwp_a11y_status' usermeta for the given user.
	 *
	 * @since 0.5.0
	 *
	 * @param int $user_id The WP user ID of the user to delete metadata for.
	 * @return bool True if successful, false if not.
	 */
	public static function flush_a11y_status_usermeta( $user_id ) {

		$deleted = delete_user_meta( $user_id, self::$slug );

		return $deleted;
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

		// Build the messages for uncertified, expired certification, and soon-to-expire certification.
		if ( false === $is_certified ) :

			$class = 'notice-error';

			if ( self::was_user_certified() ) {
				// User certification expired.
				$message    = __( 'Please renew your WSU Accessibility Training certification.', 'wsuwp-a11y-status' );
				$expiration = sprintf(
					/* translators: 1: the human readble time remaining; 2: the expiration date */
					__( 'Your certification expired %1$s ago, on %2$s.', 'wsuwp-a11y-status' ),
					self::get_user_a11y_time_to_expiration(),
					self::get_user_a11y_expiration_date()
				);
			} else {
				// User not certified now or ever.
				$message    = __( 'Please take the WSU Accessibility Training.', 'wsuwp-a11y-status' );
				$expiration = sprintf(
					/* translators: 1: the human readble time remaining; 2: the expiration date */
					__( 'You have %1$s remaining to complete the WSU Accessibility Training certification.', 'wsuwp-a11y-status' ),
					self::get_user_a11y_grace_period_remaining()
				);
			}

		elseif ( true === $is_certified ) :

			// User certification expires soon.
			if ( self::is_user_a11y_lt_one_month() ) {
				$class      = 'notice-warning';
				$message    = __( 'WSU Accessibility Training certification expiring soon.', 'wsuwp-a11y-status' );
				$expiration = sprintf(
					/* translators: 1: the human readble time remaining; 2: the expiration date */
					__( 'Your certification expires in %1$s, on %2$s.', 'wsuwp-a11y-status' ),
					self::get_user_a11y_time_to_expiration(),
					self::get_user_a11y_expiration_date()
				);
			} else {
				// Nothing if the certification lasts for more than one month.
				return;
			}

		else :

			return;

		endif;
		?>

		<div class="wsuwp-a11y-status notice <?php echo esc_attr( $class ); ?>">
			<p>
				<strong><?php echo esc_html( $message ); ?></strong>
				<?php echo esc_html( $expiration ); ?>
				<strong><a href="<?php echo esc_url( $training_link ); ?>" target="_blank" rel="noopener noreferrer">Take the training <span class="screen-reader-text">(opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays an admin notice following a successful a11y status data refresh.
	 *
	 * @since 0.6.0
	 *
	 * @return void
	 */
	public function a11y_status_action_notice__success() {
		if ( ! isset( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		if ( 'update_a11y_status' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			$message = __( 'Updated WSU Accessibility Training status info', 'wsuwp-a11y-status' );
			printf( '<div class="wsuwp-a11y-status notice notice-success is-dismissible"><p>%1$s</p></div>', esc_html( $message ) );
		} else {
			return;
		}
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
			$is_certified = self::is_user_certified( $user_id );

			if ( false === $is_certified ) {
				if ( self::was_user_certified( $user_id ) ) {
					$expired = self::get_user_a11y_time_to_expiration( $user_id );
					$output  = sprintf(
						'<span class="dashicons-before dashicons-warning notice-error">Expired %1$s ago</span>',
						esc_html( $expired )
					);
				} else {
					$output = '<span class="dashicons-before dashicons-no notice-error">None</span>';
				}
			} elseif ( true === $is_certified ) {
				$class   = ( self::is_user_a11y_lt_one_month( $user_id ) ) ? '-flag notice-warning' : '-awards notice-success';
				$expires = self::get_user_a11y_time_to_expiration( $user_id );
				$output  = sprintf(
					'<span class="dashicons-before dashicons%1$s">Expires in %2$s</span>',
					esc_attr( $class ),
					esc_html( $expires )
				);
			}
		}

		return $output;
	}

	/**
	 * Adds an "Update A11y" link to each list of user actions on the Users screen.
	 *
	 * Callback method for the `user_row_actions` filter hook. This adds an
	 * immediate action link to the list of action links displayed in each row
	 * of the WP Users list table.
	 *
	 * @since 0.6.0
	 *
	 * @param string[] $actions     An array of action links to be displayed. Default 'Edit', 'Delete' for single site and 'Edit', 'Remove' for multisite.
	 * @param WP_User  $user_object A WP_User object for the currently listed user.
	 * @return string[] The modified array of action links to be displayed.
	 */
	public function add_a11y_status_user_row_action( $actions, $user_object ) {
		if ( current_user_can( 'list_users' ) ) {

			$update_uri = wp_nonce_url( add_query_arg( array(
				'action'  => 'update_a11y_status',
				'user_id' => $user_object->ID,
			) ), 'update_a11y_' . $user_object->ID );

			$actions['update_a11y_status'] = '<a class="dashicons-before dashicons-update" href="' . esc_url( $update_uri ) . '">' . esc_html__( 'A11y', 'wsuwp-a11y-status' ) . ' <span class="screen-reader-text">(' . esc_html__( 'Refresh accessibility status', 'wsuwp-a11y-status' ) . ')</a>';
		}

		return $actions;
	}

	/**
	 * Routes actions based on the "action" query variable.
	 *
	 * Called on the `admin_init` hook, this will call the WSUWP_A11y_Status
	 * class update_a11y_status_by_user_id() method for the requested user ID
	 * to update that user's WSU accessibility training user metadata.
	 *
	 * @since 0.6.0
	 *
	 * @return array Array of user_id => `update_user_meta` responses (int|bool, meta ID if the key didn't exist, true on updated, false on failure or no change); or false if the request failed.
	 */
	public function handle_a11y_status_actions() {

		if ( ! isset( $_REQUEST['action'] ) ) {
			return;
		}

		$current_user = ( is_user_logged_in() ) ? wp_get_current_user() : null;

		if ( 'update_a11y_status' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			// Set the user ID of the user to be updated.
			$user_id = ( isset( $_REQUEST['user_id'] ) ) ? absint( $_REQUEST['user_id'] ) : 0;

			// Check permissions. Non-admins cannot update other users' information.
			if ( $current_user->ID === $user_id && ! current_user_can( 'list_users' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
			}

			// Check the nonce.
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'update_a11y_' . $user_id ) ) {
				wp_die();
			}

			// Checks completed, go ahead and update the user's a11y status data.
			$updated = $this->update_a11y_status_by_user_id( $user_id );

			if ( false !== $updated ) {
				add_action( 'admin_notices', array( $this, 'a11y_status_action_notice__success' ) );
			}
		}

		return $updated;
	}

}
