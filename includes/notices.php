<?php
/**
 * WSUWP A11y Status WSU API: WSU_API class
 *
 * This class handles communicating with the WSU API to fetch accessibility
 * certification status data and prepare it for use with WordPress.
 *
 * @package A11y_API
 * @since 1.0.0
 */

namespace WSUWP\A11yStatus\notices;

use WSUWP\A11yStatus\Init;
use WSUWP\A11yStatus\user;

/**
 * Prints errors if debugging is enabled.
 *
 * @since 0.1.0
 *
 * @param string|string[] $message    Required. The error message to display. Accepts a single string or an array of strings.
 * @param string          $error_code Optional. A computer-readable string to identify the error.
 * @return void|false The HTML formatted error message if debug display is enabled and false if not.
 */
function error( $message, $error_code = '500' ) {
	if ( ! WP_DEBUG || ! WP_DEBUG_DISPLAY || ! current_user_can( 'install_plugins' ) ) {
		return false;
	}

	if ( is_array( $message ) ) {
		foreach ( $message as $msg ) {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s error:</strong> %2$s</p></div>',
				esc_html( Init\Setup::$slug ),
				esc_html( $msg['message'] )
			);
		}
	} else {
		printf(
			'<div class="notice notice-error"><p><strong>%1$s error:</strong> %2$s</p></div>',
			esc_html( Init\Setup::$slug ),
			esc_html( $message )
		);
	}
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
function user_a11y_status_notice__remind() {
	// Don't show the reminder if the database is out of date.
	$options = get_option( Init\Setup::$slug . '_plugin-status' );
	if ( 'need_db_update' === $options['status'] ) {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$db_update_uri = wp_nonce_url(
			add_query_arg(
				array( 'action' => Init\Setup::$slug . '_db_update' ),
				admin_url( 'plugins.php' )
			),
			Init\Setup::$slug . '_db_update'
		);
		printf(
			'<div class="wsuwp-a11y-status notice notice-warning"><p>%1$s <a href="%2$s"><strong>%3$s</strong></a></p></div>',
			__( 'The WSU Accessibility Status plugin requires a database update.' ),
			esc_url( $db_update_uri ),
			__( 'Update Now' )
		);
		return;
	}

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
			$expiration = __( 'You must pass the WSU Accessibility Training certification to publish content on this website.', 'wsuwp-a11y-status' );
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
function user_a11y_status_notice__action() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_REQUEST['update_a11y'] ) ) {
		return;
	}

	$messages = array();

	if ( 'refresh' === $_REQUEST['update_a11y'] ) {
		$messages[] = array(
			'class' => 'notice-success',
			'text'  => __( 'Updated WSU Accessibility Training status info.', 'wsuwp-a11y-status' ),
		);
	}

	if ( 'fail' === $_REQUEST['update_a11y'] ) {
		$messages[] = array(
			'class' => 'notice-error',
			'text'  => __( 'WSU Accessibility Training status update failed.', 'wsuwp-a11y-status' ),
		);
	}

	if ( 'remind_success' === $_REQUEST['update_a11y'] ) {
		$messages[] = array(
			'class' => 'notice-success',
			'text'  => __( 'The WSU Accessibility Training reminder has been sent.', 'wsuwp-a11y-status' ),
		);
	}

	if ( 'remind_fail' === $_REQUEST['update_a11y'] ) {
		$messages[] = array(
			'class' => 'notice-error',
			'text'  => __( 'The WSU Accessibility Training reminder could not be sent.', 'wsuwp-a11y-status' ),
		);
	}

	if ( 'refresh_selected' === $_REQUEST['update_a11y'] ) {
		if ( isset( $_REQUEST['success'] ) && 0 < $_REQUEST['success'] ) {
			$messages[] = array(
				'class' => 'notice-success',
				'text'  => sprintf(
					/* translators: 1: the number of users updated in integer format */
					__( 'WSU Accessibility Training task completed for %1$s users.', 'wsuwp-a11y-status' ),
					absint( $_REQUEST['success'] )
				),
			);
		}
		if ( isset( $_REQUEST['fail'] ) && 0 < $_REQUEST['fail'] ) {
			$messages[] = array(
				'class' => 'notice-error',
				'text'  => sprintf(
					/* translators: 1: the number of users updated in integer format */
					__( 'WSU Accessibility Training task failed for %1$s users.', 'wsuwp-a11y-status' ),
					absint( $_REQUEST['fail'] )
				),
			);
		}
	}

	if ( Init\Setup::$slug . '_db_update_complete' === $_REQUEST['update_a11y'] ) {
		$messages[] = array(
			'class' => 'notice-success',
			'text'  => __( 'Updated WSUWP Accessibility Status plugin database.', 'wsuwp-a11y-status' ),
		);
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
 * Sends a user an email with their accessibility status.
 *
 * @since 1.1.0
 *
 * @param int $user_id The WP user ID of the user to update.
 * @return booleen True on successful email sent, false on failure.
 */
function send_a11y_user_reminder( $user_id = '' ) {
	if ( ! $user_id ) {
		error( __( 'Please supply a valid user ID.', 'wsuwp-a11y-status' ) );
		return false;
	}

	$user = get_user_by( 'id', $user_id );

	if ( is_multisite() ) {
		$site_name = get_network()->site_name;
	} else {
		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	$user_email = $user->user_email;
	$user_login = $user->user_login;

	$title   = 'WSU Accessibility Training';
	$message = __( 'This WSU Accessibility Training status update is for the following account:', 'wsuwp-a11y-status' ) . "\r\n\r\n";
	/* translators: %s: Site name. */
	$message .= sprintf( __( 'Site Name: %s', 'wsuwp-a11y-status' ) . "\r\n", $site_name );
	/* translators: %s: User login */
	$message .= sprintf( __( 'Username: %s', 'wsuwp-a11y-status' ) . "\r\n\r\n", $user_login );
	$message .= __( 'If this is not your account or you believe the reported status is incorrect, please contact your site administrator.', 'wsuwp-a11y-status' ) . "\r\n\r\n";

	// Build the messages for uncertified, expired certification, and soon-to-expire certification.
	if ( ! user\is_user_certified( $user ) ) {
		if ( user\was_user_certified( $user ) ) {
			// User certification expired.
			$title    = 'Expired: WSU Accessibility Training';
			$message .= sprintf(
				/* translators: 1: the human readble time remaining; 2: the expiration date */
				__( 'Your certification expired %1$s ago, on %2$s.', 'wsuwp-a11y-status' ) . "\r\n\r\n",
				user\get_user_a11y_expire_diff( $user ),
				user\get_user_a11y_expiration_date( $user )
			);
			$message .= __( 'Please renew your accessibility certification at the following web address:', 'wsuwp-a11y-status' ) . "\r\n";
		} else {
			// User not certified now or ever.
			$title    = 'Required: WSU Accessibility Training';
			$message .= __( 'No certification found.', 'wsuwp-a11y-status' ) . "\r\n\r\n";
			$message .= __( 'Please take the WSU Accessibility Training at the following web address:', 'wsuwp-a11y-status' ) . "\r\n";
		}
		$message .= esc_url( 'https://go.wsu.edu/web-accessibility' ) . "\r\n\r\n";
	} else {
		$message .= sprintf(
			/* translators: 1: the human readble time remaining; 2: the expiration date */
			__( 'Your certification expires in %1$s, on %2$s.', 'wsuwp-a11y-status' ) . "\r\n\r\n",
			user\get_user_a11y_expire_diff( $user ),
			user\get_user_a11y_expiration_date( $user )
		);
		if ( user\is_user_a11y_expires_one_month( $user ) ) {
			// User certification expires soon.
			$title    = 'Expiring soon: WSU Accessibility Training';
			$message .= __( 'Please renew your accessibility certification at the following web address:', 'wsuwp-a11y-status' ) . "\r\n";
			$message .= esc_url( 'https://go.wsu.edu/web-accessibility' ) . "\r\n\r\n";
		}
	}

	$message .= __( 'About the Required Accessibility Training:' ) . "\r\n";
	/* translators: %s: Accessibility training URL. */
	$message .= __( 'The WSU web accessibility policy, Executive Policy 7 (effective August 23, 2017), requires all content publishers at the University to successfully complete the annual web accessibility training before publishing content online (see BPPM 85.55).', 'wsuwp-a11y-status' );

	// Try sending the email.
	if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
		return false;
	}

	return true;
}
