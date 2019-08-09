<?php
/**
 * WSU Accessibility Status plugin User API.
 *
 * @package Setup
 * @since 1.0.0
 */

namespace WSUWP\A11yStatus\user;

use WSUWP\A11yStatus\Init;
use WSUWP\A11yStatus\WSU_API;

/**
 * Retrieves a user's WSU net ID from the user metadata or their email.
 *
 * Returns a saved WSU NID in the user's usermeta if it exists. If no NID
 * usermeta is found, it falls back to the user email address and formats
 * it into a WSU net ID.
 *
 * @since 0.9.0
 *
 * @param WP_User $user Required. A WP_User object for the user to get a NID for.
 * @return string A sanitized WSU network ID.
 */
function get_user_wsu_nid( $user ) {
	// Check for a saved WSU NID in the user's usermeta.
	$wsu_nid = get_user_meta( $user->ID, '_wsu_nid', true );

	if ( ! $wsu_nid ) {
		// If no WSU NID is found try building one out of the user email.
		$wsu_nid = implode( explode( '@', $user->user_email, -1 ) );
	}

	return sanitize_user( $wsu_nid );
}

/**
 * Handles user login actions.
 *
 * Fires when the user logs in to WordPress.
 *
 * @param string  $user_login The authenticated user's login.
 * @param WP_User $user       The WP User object for the authenticated user.
 */
function handle_user_login( $user_login, $user ) {
	update_a11y_user_meta( $user );

	// TODO Modify the user registration email to notify them of their status.
}

/**
 * Gets the full a11y user meta value of the given user.
 *
 * Takes a WordPress user ID and retrieves the full WSU accessibility training
 * status data for that user if it exists in the user metadata.
 *
 * @since 0.5.0
 *
 * @param WP_User $user Optional. The WP_User object of a user to check. Defaults to the current user.
 * @return array|false The accessibility status data for the given user or false if the user data is not found.
 */
function get_a11y_user_meta( $user = '' ) {
	if ( '' === $user ) {
		$user = wp_get_current_user();
	}

	$a11y_status = get_user_meta( $user->ID, Init\Setup::$slug, true );

	if ( ! empty( $a11y_status ) ) {
		return $a11y_status;
	}

	return false;
}

/**
 * Updates an individual user's metadata with their WSU A11y Training status.
 *
 * @since 0.6.0
 *
 * @param WP_User|int $user The WP_User object of the user to update or a user ID.
 * @return array Array of user_id => `update_user_meta` responses (int|bool, meta ID if the key didn't exist, true on updated, false on failure or no change); or false if the request failed.
 */
function update_a11y_user_meta( $user ) {
	if ( is_int( $user ) ) {
		$user = get_user_by( 'id', $user );
	}

	$username = get_user_wsu_nid( $user );

	// TODO: Move this to a plugin setting/option.
	$url = esc_url_raw( 'https://webserv.wsu.edu/accessibility/training/service' );

	// Fetch the accessibility training status data.
	$user_status = new WSU_API\WSU_API( $url, $username );

	// Save the accessibility training status to user metadata.
	return update_user_meta( $user->ID, Init\Setup::$slug, $user_status->result );
}

/**
 * Deletes the 'wsuwp_a11y_status' usermeta for the given user.
 *
 * @since 0.5.0
 *
 * @param WP_User $user Required. The WP_User instance of the user to delete metadata for.
 * @return bool True if successful, false if not.
 */
function delete_a11y_user_meta( $user ) {
	$deleted = delete_user_meta( $user->ID, Init\Setup::$slug );

	return $deleted;
}
