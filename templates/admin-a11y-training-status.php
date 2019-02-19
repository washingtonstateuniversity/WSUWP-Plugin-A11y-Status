<?php
/**
 * WSUWP A11y Status Dashboard Template
 *
 * @package WSUWP_A11y_Status
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! current_user_can( 'list_users' ) ) {
	wp_die(
		'<h1>' . esc_html__( 'You need a higher level of permission.', 'wsuwp-a11y-status' ) . '</h1>' .
		'<p>' . esc_html__( 'Sorry, you are not allowed to list users.', 'wsuwp-a11y-status' ) . '</p>',
		403
	);
}

// Verify nonce before proceeding if user requested a manual data refresh.
$force_refresh = ( isset( $_GET['force-refresh'] ) ) ? true : false;

if ( false !== $force_refresh && isset( $_GET['_wsuwp_a11y_refresh_nonce'] ) ) {
	if ( wp_verify_nonce( $_GET['_wsuwp_a11y_refresh_nonce'], WSUWP_A11y_Status::$slug . '_force-refresh' ) ) {
		// On a forced refresh, clear the cache and then execute the request again.
		WSUWP_A11y_Status::flush_transient_cache();
		do_action( 'wsuwp_a11y_status_update' );
	} else {
		wp_die(
			'<h1>' . esc_html__( 'You need a higher level of permission.', 'wsuwp-a11y-status' ) . '</h1>' .
			'<p>' . esc_html__( 'Sorry, you are not allowed to list users.', 'wsuwp-a11y-status' ) . '</p>',
			403
		);
	}
}

$force_refresh_url = wp_nonce_url( self_admin_url( 'users.php?page=' . WSUWP_A11y_Status::$slug . '&force-refresh=1' ), WSUWP_A11y_Status::$slug . '_force-refresh', '_wsuwp_a11y_refresh_nonce' );
$users             = get_users( array( 'fields' => array( 'user_email' ) ) );
?>

<div class="wrap wsuwp-a11y-status">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'WSU Accessiblity Training Status', 'wsuwp-a11y-status' ); ?></h1>

	<a class="page-title-action" href="<?php echo esc_url( $force_refresh_url ); ?>"><?php esc_html_e( 'Refresh Data', 'wsuwp-a11y-status' ); ?></a>

	<hr class="wp-header-end">

	<h2 class="screen-reader-text">Users list</h2>
	<table class="wp-list-table widefat fixed striped users wsuwp-a11y-status">
		<thead>
			<tr>
				<th scope="col" id="wsu-nid" class="manage-column column-wsu-nid column-primary">WSU NID</th>
				<th scope="col" id="user-email" class="manage-column column-user-email">Email</th>
				<th scope="col" id="a11y-status" class="manage-column column-a11y-status">A11y Training Status</th>
				<th scope="col" id="a11y-expires" class="manage-column column-a11y-expires">Training Expiration</th>
				<th scope="col" id="a11y-remaining" class="manage-column column-a11y-remaining">Time to Renew</th>
			</tr>
		</thead>
		<tbody id="the-list">
			<?php
			foreach ( $users as $user ) {
				$user_nid     = implode( explode( '@', $user->user_email, -1 ) );
				$is_certified = WSUWP_A11y_Status::is_user_certified( $user_nid );
				$expires      = WSUWP_A11y_Status::get_user_a11y_expiration_date( $user_nid );
				$remaining    = WSUWP_A11y_Status::get_user_a11y_time_to_expiration( $user_nid );
				$row_class    = 'notice-success';

				if ( ! $is_certified ) {
					$row_class = 'notice-error';
				} elseif ( WSUWP_A11y_Status::is_user_a11y_lt_one_month( $user_nid ) ) {
					$row_class = 'notice-warning';
				}

				?>

				<tr id="user-<?php echo esc_attr( $user_nid ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
					<td class="wsu-nid column-wsu-nid" data-colname="WSU NID"><?php echo esc_html( $user_nid ); ?></td>
					<td class="user-email column-user-email" data-colname="Email"><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></td>
					<td class="a11y-status column-a11y-status" data-colname="A11y Training Status"><?php echo ( $is_certified ) ? 'Passing' : 'None'; ?></td>
					<td class="a11y-expires column-a11y-expires" data-colname="Training Expiration"><?php echo esc_html( $expires ); ?></td>
					<td class="a11y-remaining column-a11y-remaining" data-colname="Time to Renew"><?php echo esc_html( $remaining ); ?></td>
				</tr>

				<?php
			}
			?>
		</tbody>
	</table>
</div>
