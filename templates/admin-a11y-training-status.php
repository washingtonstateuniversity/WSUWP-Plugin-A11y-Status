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
$a11y_status       = get_transient( 'a11y_status_wsuwp_a11y_status' );
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
				<th scope="col" id="a11y-status" class="manage-column column-a11y-status">A11y Training Status</th>
				<th scope="col" id="a11y-expires" class="manage-column column-a11y-expires">Training Expiration</th>
				<th scope="col" id="a11y-remaining" class="manage-column column-a11y-remaining">Time to Renew</th>
			</tr>
		</thead>
		<tbody id="the-list">
			<?php
			foreach ( $a11y_status as $user => $status ) {
				$expires      = '';
				$remaining    = '';
				$is_certified = 'None';
				$row_class    = 'warning';

				// If user is certified then populate the expiration details.
				if ( 'False' !== $status['isCertified'] ) {
					$expires_raw  = date_create_from_format( 'M j Y g:iA', $status['Expires'] );
					$expires      = date_format( $expires_raw, get_option( 'date_format' ) );
					$remaining    = human_time_diff( date_format( $expires_raw, 'U' ) );
					$is_certified = 'Passing';

					if ( false !== strpos( $remaining, 'months' ) || false !== strpos( $remaining, 'years' ) ) {
						$is_less_than_one_month = false;
					} else {
						$is_less_than_one_month = true;
					}

					$row_class  = 'success';
					$row_class .= ( $is_less_than_one_month ) ? ' less-than-one-month' : '';
				}

				?>
				<tr id="user-<?php echo esc_attr( $user ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
					<td class="wsu-nid column-wsu-nid" data-colname="WSU NID"><?php echo esc_html( $user ); ?></td>
					<td class="a11y-status column-a11y-status" data-colname="A11y Training Status"><?php echo esc_html( $is_certified ); ?></td>
					<td class="a11y-expires column-a11y-expires" data-colname="Training Expiration"><?php echo esc_html( $expires ); ?></td>
					<td class="a11y-remaining column-a11y-remaining" data-colname="Time to Renew"><?php echo esc_html( $remaining ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
