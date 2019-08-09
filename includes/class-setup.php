<?php
/**
 * WSUWP A11y Status Setup: Setup class
 *
 * @package Setup
 * @since 0.1.0
 */

namespace WSUWP\A11yStatus\Init;

use WSUWP\A11yStatus\WSU_API;
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
			self::flush_a11y_status_usermeta( absint( $user->ID ) );
			delete_user_meta( absint( $user->ID ), '_wsu_nid' );
		}
	}

	/**
	 * Loads the WP API actions and hooks.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'wp_login', 'WSUWP\A11yStatus\user\handle_user_login', 10, 2 );
		add_action( 'user_register', 'WSUWP\A11yStatus\user\update_a11y_user_meta', 10, 1 );

		add_action( 'admin_init', array( $this, 'handle_a11y_status_actions' ) );
		add_action( 'admin_notices', array( $this, 'user_a11y_status_notice__remind' ) );
		add_action( 'admin_notices', array( $this, 'user_a11y_status_notice__action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'edit_user_profile', array( $this, 'usermeta_form_field_nid' ) );
		add_action( 'show_user_profile', array( $this, 'usermeta_form_field_nid' ) );
		add_action( 'edit_user_profile_update', array( $this, 'usermeta_form_field_nid_update' ) );
		add_action( 'personal_options_update', array( $this, 'usermeta_form_field_nid_update' ) );
		add_filter( 'manage_users_columns', array( $this, 'add_a11y_status_user_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'manage_a11y_status_user_column' ), 10, 3 );
		add_filter( 'user_row_actions', array( $this, 'add_a11y_status_user_row_action' ), 10, 2 );
		add_filter( 'bulk_actions-users', array( $this, 'add_a11y_status_user_bulk_action' ), 10, 1 );
		add_filter( 'handle_bulk_actions-users', array( $this, 'handle_a11y_status_bulk_actions' ), 10, 3 );
	}

	/**
	 * Includes required files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		// The WSU API handler class.
		require __DIR__ . '/class-wsu-api.php';

		// The plugin formatting API.
		require __DIR__ . '/formatting.php';

		// The plugin user API.
		require __DIR__ . '/user.php';
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
	 * Gets the URL to the WSU Accessibility Training course.
	 *
	 * Note: This returns an unescaped URL string. Users should handle escaping
	 * before using this.
	 *
	 * @since 0.8.0
	 *
	 * @param string $user_ID Optional. The WP user ID of a user to check. Defaults to the current user.
	 * @return string|false An unecaped URL to the WSU Accessibility Training course or false if the data is not found.
	 */
	private function get_user_a11y_training_url( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) ) {
			return $user_status['trainingURL'];
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
	 * @return string|false The expiration date for the given user or false if no data.
	 */
	public static function get_user_a11y_expiration_date( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) ) {
			return date_format( $user_status['Expires'], get_option( 'date_format' ) );
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
	 * @return string|false The time remaining until a11y certification expires for the given user or false if no data.
	 */
	public static function get_user_a11y_time_to_expiration( $user_id = '' ) {
		$user_status = self::get_user_a11y_status( $user_id );

		if ( ! empty( $user_status ) ) {
			return human_time_diff( date_format( $user_status['Expires'], 'U' ) );
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

		if ( empty( $user_status ) || ! $user_status['isCertified'] ) {
			$wp_user = ( '' !== $user_id ) ? get_user_by( 'id', $user_id ) : wp_get_current_user();

			$registration = date_create( $wp_user->user_registered );

			$end   = $registration->add( new \DateInterval( 'P30D' ) );
			$today = date_create();

			if ( $today > $end ) {
				$days_remaining = '0 days';
			} else {
				$days_remaining = date_diff( $end, $today )->format( '%a days' );
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

		if ( ! empty( $user_status ) && false !== $user_status['isCertified'] ) {
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

		if ( ! empty( $user_status['was_certified'] ) ) {
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

		if ( ! empty( $user_status ) && false !== $user_status['isCertified'] ) {
			$diff = $user_status['Expires']->diff( date_create() );

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
	 * @param string|string[] $message    Required. The error message to display. Accepts a single string or an array of strings.
	 * @param string          $error_code Optional. A computer-readable string to identify the error.
	 * @return void|false The HTML formatted error message if debug display is enabled and false if not.
	 */
	public static function error( $message, $error_code = '500' ) {
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
		$plugin_meta = get_plugin_data( $this->basename );
		wp_enqueue_style( 'wsuwp-a11y-status-dashboard', plugins_url( 'css/main.css', __DIR__ ), array(), $plugin_meta['Version'] );
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
	public function user_a11y_status_notice__remind() {
		// Build the messages for uncertified, expired certification, and soon-to-expire certification.
		if ( ! self::is_user_certified() ) {
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
		} else {
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
		}

		if ( $message ) {
			$user_id    = get_current_user_id();
			$update_uri = wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'update_a11y_status',
						'user_id' => $user_id,
					),
					admin_url()
				),
				'update_a11y_' . $user_id
			);
			?>
			<div class="wsuwp-a11y-status notice <?php echo esc_attr( $class ); ?>">
				<p>
					<strong><?php echo esc_html( $message ); ?></strong>
					<?php echo esc_html( $expiration ); ?>
					<strong><a href="<?php echo esc_url( self::get_user_a11y_training_url() ); ?>" target="_blank" rel="noopener noreferrer">Take the training<span class="screen-reader-text">(opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></strong>
					<a class="button" href="<?php echo esc_url( $update_uri ); ?>"><?php esc_html_e( 'Refresh', 'wsuwp-a11y-status' ); ?> <span class="screen-reader-text">(<?php esc_html_e( 'Refresh accessibility status', 'wsuwp-a11y-status' ); ?>)</span></a>
				</p>
			</div>
			<?php
		}

	}

	/**
	 * Displays an admin notice following a successful a11y status data refresh.
	 *
	 * @since 0.6.0
	 *
	 * @return void
	 */
	public function user_a11y_status_notice__action() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['action'] ) ) {
			return;
		}

		$messages = array();

		if ( 'update_a11y_status' === $_REQUEST['action'] ) {
			$messages[] = array(
				'class' => 'notice-success',
				'text'  => __( 'Updated WSU Accessibility Training status info.', 'wsuwp-a11y-status' ),
			);
		}

		if ( 'update_a11y_status_selected' === $_REQUEST['action'] ) {
			if ( isset( $_REQUEST['success'] ) && 0 < $_REQUEST['success'] ) {
				$messages[] = array(
					'class' => 'notice-success',
					'text'  => sprintf(
						/* translators: 1: the number of users updated in integer format */
						__( 'Updated WSU Accessibility Training status info for %1$s users.', 'wsuwp-a11y-status' ),
						absint( $_REQUEST['success'] )
					),
				);
			}

			if ( isset( $_REQUEST['fail'] ) && 0 < $_REQUEST['fail'] ) {
				$messages[] = array(
					'class' => 'notice-error',
					'text'  => sprintf(
						/* translators: 1: the number of users updated in integer format */
						__( 'WSU Accessibility Training status update failed for %1$s users.', 'wsuwp-a11y-status' ),
						absint( $_REQUEST['fail'] )
					),
				);
			}
		}
		// phpcs:enable

		foreach ( $messages as $message ) {
			printf(
				'<div class="wsuwp-a11y-status notice is-dismissible %1$s"><p>%2$s</p></div>',
				esc_attr( $message['class'] ),
				esc_html( $message['text'] )
			);
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
			$last_checked = self::get_user_a11y_status( $user_id )['last_checked'];

			if ( ! self::is_user_certified( $user_id ) ) {
				if ( self::was_user_certified( $user_id ) ) {
					$expired = self::get_user_a11y_time_to_expiration( $user_id );
					$output  = sprintf(
						'<span title="Updated %1$s" class="dashicons-before dashicons-warning notice-error">Expired %2$s ago</span>',
						esc_attr( $last_checked ),
						esc_html( $expired )
					);
				} else {
					$output = sprintf(
						'<span title="Updated %1$s" class="dashicons-before dashicons-no notice-error">None</span>',
						esc_attr( $last_checked )
					);
				}
			} else {
				$class   = ( self::is_user_a11y_lt_one_month( $user_id ) ) ? '-flag notice-warning' : '-awards notice-success';
				$expires = self::get_user_a11y_time_to_expiration( $user_id );
				$output  = sprintf(
					'<span title="Updated %1$s" class="dashicons-before dashicons%2$s">Expires in %3$s</span>',
					esc_attr( $last_checked ),
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

			$update_uri = wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'update_a11y_status',
						'user_id' => $user_object->ID,
					)
				),
				'update_a11y_' . $user_object->ID
			);

			$actions['update_a11y_status'] = '<a class="dashicons-before dashicons-update" href="' . esc_url( $update_uri ) . '">' . esc_html__( 'A11y', 'wsuwp-a11y-status' ) . ' <span class="screen-reader-text">(' . esc_html__( 'Refresh accessibility status', 'wsuwp-a11y-status' ) . ')</a>';
		}

		return $actions;
	}

	/**
	 * Adds an option to the bulk actions dropdown element on the Users screen.
	 *
	 * @since 0.6.0
	 *
	 * @param string[] $actions An array of bulk action options to be displayed. Default 'Edit', 'Delete' for single site and 'Edit', 'Remove' for multisite.
	 * @return string[] The modified array of builk action options to be displayed.
	 */
	public function add_a11y_status_user_bulk_action( $actions ) {
		$actions['update_a11y_status_selected'] = __( 'Refresh A11y Status', 'wsuwp-a11y-statis' );

		return $actions;
	}

	/**
	 * Routes actions based on the "action" query variable.
	 *
	 * Called on the `admin_init` hook, this will call the Setup
	 * class user\update_a11y_user_meta() method for the requested user ID
	 * to update that user's WSU accessibility training user metadata.
	 *
	 * @since 0.6.0
	 *
	 * @return array Array of user_id => `update_user_meta` responses (int|bool, meta ID if the key didn't exist, true on updated, false on failure or no change); or false if the request failed or null if the wrong request.
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
			if ( $current_user->ID !== $user_id && ! current_user_can( 'list_users' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
			}

			// Check the nonce.
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'update_a11y_' . $user_id ) ) {
				wp_die();
			}

			// Checks completed, go ahead and update the user's a11y status data.
			$updated = user\update_a11y_user_meta( $user_id );

			return $updated;
		}
	}

	/**
	 * Handles a11y status bulk actions from the Users screen.
	 *
	 * A callback method for the `handle_bulk_actions-{$screen}` filter. This
	 * filter expects the redirect link to be modified, with success or
	 * failure feedback from the action to be used to display feedback to the
	 * user.
	 *
	 * @since 0.6.0
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $doaction     The action being taken.
	 * @param int[]  $user_ids     An array of user IDs matching the selected users.
	 * @return string The modified redirect URL.
	 */
	public function handle_a11y_status_bulk_actions( $redirect_url, $doaction, $user_ids ) {
		// Return early if not the a11y action.
		if ( 'update_a11y_status_selected' !== $doaction ) {
			return $redirect_url;
		}

		// Check permissions. Non-admins cannot update other users' information.
		if ( ! current_user_can( 'list_users' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
		}

		// Perform an update for each selected user and count successes.
		$successful = 0;
		foreach ( $user_ids as $user_id ) {
			// Checks completed, go ahead and update the user's a11y status data.
			$updated = user\update_a11y_user_meta( absint( $user_id ) );

			if ( false !== $updated ) {
				$successful++;
			}
		}
		$unsuccessful = count( $user_ids ) - $successful;

		$redirect_url = add_query_arg(
			array(
				'action'  => 'update_a11y_status_selected',
				'success' => $successful,
				'fail'    => $unsuccessful,
			),
			$redirect_url
		);

		return $redirect_url;
	}

	/**
	 * Displays a field on the user profile screen to add a WSU NID.
	 *
	 * Callback method for the `edit_user_profile` and `show_user_profile`
	 * hooks that allow adding fields and data to the user profile page for,
	 * respectively, users viewing other user profiles and users viewing their
	 * own profile.
	 *
	 * @since 0.9.0
	 *
	 * @param WP_User $user The WP_User object of the user being edited.
	 * @return void
	 */
	public function usermeta_form_field_nid( $user ) {
		?>
		<h2>WSU Network ID</h2>
		<table class="form-table">
			<tbody>
				<tr class="user-wsu-nid-wrap">
					<th>
						<label for="wsu_nid"><?php esc_html_e( 'WSU NID', 'wsuwp-a11y-status' ); ?></label>
					</th>
					<td>
						<input type="text" name="wsu_nid" id="wsu_nid" aria-describedby="nid-description" value="<?php echo esc_attr( get_user_meta( $user->ID, '_wsu_nid', true ) ); ?>" class="regular-text">
						<p class="description" id="nid-description">Enter a WSU Network ID if not using a WSU email address.</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handles data submitted from the WSU NID field on the user profile screen.
	 *
	 * Callback method for the `edit_user_profile_update` hook, which triggers
	 * when a user submits data to update another user's profile, and the
	 * `personal_options_update`, which triggers when a user submits data to
	 * update their own profile.
	 *
	 * @since 0.9.0
	 *
	 * @param int $user_id Optional. The user ID of the user being edited.
	 * @return int|bool Meta ID if a new key was created, or true if value was updated and false on failure or no change
	 */
	public function usermeta_form_field_nid_update( $user_id ) {
		check_admin_referer( 'update-user_' . $user_id );

		// Check permissions.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// Sanitize input data.
		$wsu_nid = sanitize_text_field( wp_strip_all_tags( $_POST['wsu_nid'] ) );

		// Create/update user metadata for the given user ID.
		return update_user_meta( $user_id, '_wsu_nid', $wsu_nid );
	}

}
