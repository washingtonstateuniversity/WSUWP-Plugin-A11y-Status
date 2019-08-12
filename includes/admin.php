<?php
/**
 * WSU Accessibility Status plugin admin page functions.
 *
 * @package Setup
 * @since 1.0.0
 */

namespace WSUWP\A11yStatus\admin;

use WSUWP\A11yStatus\user;

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
function add_a11y_status_user_column( $columns ) {
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
function manage_a11y_status_user_column( $output, $column_name, $user_id ) {
	if ( 'a11y_status' === $column_name ) {
		$user = get_user_by( 'id', $user_id );
		$last_checked = user\get_a11y_user_meta( $user )['last_checked'];

		if ( ! user\is_user_certified( $user ) ) {
			if ( user\was_user_certified( $user ) ) {
				$expired = user\get_user_a11y_expire_diff( $user );
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
			$class   = ( user\is_user_a11y_expires_one_month( $user ) ) ? '-flag notice-warning' : '-awards notice-success';
			$expires = user\get_user_a11y_expire_diff( $user );
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
function add_a11y_status_user_row_action( $actions, $user_object ) {
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
function add_a11y_status_user_bulk_action( $actions ) {
	$actions['update_a11y_status_selected'] = __( 'Refresh A11y Status', 'wsuwp-a11y-status' );

	return $actions;
}

/**
 * Routes actions based on the "action" query variable.
 *
 * Called on the `admin_init` hook, this will call the
 * `user\update_a11y_user_meta()` function for the requested user ID to update
 * that user's WSU accessibility training user metadata.
 *
 * @since 0.6.0
 *
 * @return array Array of user_id => `update_user_meta` responses (int|bool, meta ID if the key didn't exist, true on updated, false on failure or no change); or false if the request failed or null if the wrong request.
 */
function handle_a11y_status_actions() {
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
			wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
		}

		// Checks completed, go ahead and update the user's a11y status data.
		$updated = user\update_a11y_user_meta( $user_id );

		return $updated;
	}
}

/**
 * Handles a11y status bulk actions from the Users screen.
 *
 * A callback method for the `handle_bulk_actions-{$screen}` filter. This filter
 * expects the redirect link to be modified, with success or failure feedback
 * from the action to be used to display feedback to the user.
 *
 * @since 0.6.0
 *
 * @param string $redirect_url The redirect URL.
 * @param string $doaction     The action being taken.
 * @param int[]  $user_ids     An array of user IDs matching the selected users.
 * @return string The modified redirect URL.
 */
function handle_a11y_status_bulk_actions( $redirect_url, $doaction, $user_ids ) {
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
