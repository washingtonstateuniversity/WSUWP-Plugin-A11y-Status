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
use WSUWP\A11yStatus\admin;

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

		// User hooks.
		add_action( 'wp_login', 'WSUWP\A11yStatus\user\handle_user_login', 10, 2 );
		add_action( 'user_register', 'WSUWP\A11yStatus\user\update_a11y_user_meta', 10, 1 );

		// Notices hooks.
		add_action( 'admin_notices', array( $this, 'user_a11y_status_notice__remind' ) );
		add_action( 'admin_notices', array( $this, 'user_a11y_status_notice__action' ) );

		// Options hooks.
		add_action( 'edit_user_profile', array( $this, 'usermeta_form_field_nid' ) );
		add_action( 'show_user_profile', array( $this, 'usermeta_form_field_nid' ) );
		add_action( 'edit_user_profile_update', array( $this, 'usermeta_form_field_nid_update' ) );
		add_action( 'personal_options_update', array( $this, 'usermeta_form_field_nid_update' ) );

		// Admin hooks.
		add_filter( 'manage_users_columns', 'WSUWP\A11yStatus\admin\add_a11y_status_user_column' );
		add_filter( 'manage_users_custom_column', 'WSUWP\A11yStatus\admin\manage_a11y_status_user_column', 10, 3 );
		add_filter( 'user_row_actions', 'WSUWP\A11yStatus\admin\add_a11y_status_user_row_action', 10, 2 );
		add_filter( 'bulk_actions-users', 'WSUWP\A11yStatus\admin\add_a11y_status_user_bulk_action', 10, 1 );
		add_action( 'admin_init', 'WSUWP\A11yStatus\admin\handle_a11y_status_actions' );
		add_filter( 'handle_bulk_actions-users', 'WSUWP\A11yStatus\admin\handle_a11y_status_bulk_actions', 10, 3 );
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

		// The plugin user API.
		require __DIR__ . '/user.php';
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
		if ( ! user\is_user_certified() ) {
			$class = 'notice-error';

			if ( user\was_user_certified() ) {
				// User certification expired.
				$message    = __( 'Please renew your WSU Accessibility Training certification.', 'wsuwp-a11y-status' );
				$expiration = sprintf(
					/* translators: 1: the human readble time remaining; 2: the expiration date */
					__( 'Your certification expired %1$s ago, on %2$s.', 'wsuwp-a11y-status' ),
					user\get_user_a11y_expire_diff(),
					user\get_user_a11y_expiration_date()
				);
			} else {
				// User not certified now or ever.
				$message    = __( 'Please take the WSU Accessibility Training.', 'wsuwp-a11y-status' );
				$expiration = sprintf(
					/* translators: 1: the human readble time remaining; 2: the expiration date */
					__( 'You have %1$s remaining to complete the WSU Accessibility Training certification.', 'wsuwp-a11y-status' ),
					user\get_user_a11y_grace_period_remaining()
				);
			}
		} else {
			// User certification expires soon.
			if ( user\is_user_a11y_expires_one_month() ) {
				$class      = 'notice-warning';
				$message    = __( 'WSU Accessibility Training certification expiring soon.', 'wsuwp-a11y-status' );
				$expiration = sprintf(
					/* translators: 1: the human readble time remaining; 2: the expiration date */
					__( 'Your certification expires in %1$s, on %2$s.', 'wsuwp-a11y-status' ),
					user\get_user_a11y_expire_diff(),
					user\get_user_a11y_expiration_date()
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
					<strong><a href="<?php echo esc_url( user\get_user_a11y_training_url() ); ?>" target="_blank" rel="noopener noreferrer">Take the training<span class="screen-reader-text">(opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></strong>
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
