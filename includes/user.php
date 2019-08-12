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

	// Retrieve existing accessibility status for the user.
	$user_status = get_a11y_user_meta( $user );

	// Fetch the most recent accessibility training status data.
	$new_user_status = new WSU_API\WSU_API( $url, $username );
	$new_user_status = $new_user_status->result;

	if ( ! empty( $user_status ) ) {
		// Don't update expiration date if user certification has expired.
		if (
			( isset( $user_status['was_certified'] ) && true === $user_status['was_certified'] )
			&& true !== $new_user_status['is_certified']
		) {
			$new_user_status['expire_date'] = $user_status['expire_date'];
		}

		// Merge the new user status into the existing user status.
		$user_status = wp_parse_args( $new_user_status, $user_status );
	} else {
		$user_status = $new_user_status;
	}

	// Save the accessibility training status to user metadata.
	return update_user_meta( $user->ID, Init\Setup::$slug, $user_status );
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

/**
 * Determines whether a given user is Accessibility Training certified.
 *
 * @since 0.2.0
 *
 * @param WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return bool True if the user is certified, false if not or if the data is not found.
 */
function is_user_certified( $user = '' ) {
	$user_status = get_a11y_user_meta( $user );

	if ( ! empty( $user_status ) && false !== $user_status['is_certified'] ) {
		return true;
	}

	return false;
}

/**
 * Determines whether a user has been A11y Training certified in the past.
 *
 * @since 0.5.0
 *
 * @param WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return bool True if the user has ever been certified and false if not.
 */
function was_user_certified( $user = '' ) {
	$user_status = get_a11y_user_meta( $user );

	if ( ! empty( $user_status['was_certified'] ) && false !== $user_status['was_certified'] ) {
		return true;
	}

	return false;
}

/**
 * Determines if a user's a11y certification expires in less than a month.
 *
 * @since 0.2.0
 *
 * @param WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return bool True if the user's certification expires in less than one month and false if not, or if the data is not found.
 */
function is_user_a11y_expires_one_month( $user = '' ) {
	$user_status = get_a11y_user_meta( $user );

	if ( ! empty( $user_status ) && false !== $user_status['is_certified'] ) {
		$diff = $user_status['expire_date']->diff( date_create() );

		if ( 1 > $diff->m ) {
			return true;
		}
	}

	return false;
}

/**
 * Gets the date a given user's a11y certification expires.
 *
 * Retrieves the date a given user's WSU Accessibility certification expires,
 * formatted based on the WP site option `date_format`.
 *
 * @since 0.2.0
 *
 * @param WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return string|false The expiration date for the given user or false if no data.
 */
function get_user_a11y_expiration_date( $user = '' ) {
	$user_status = get_a11y_user_meta( $user );

	if ( ! empty( $user_status ) ) {
		return date_format( $user_status['expire_date'], get_option( 'date_format' ) );
	}

	return false;
}

/**
 * Gets the time difference between user's certification expiration and now.
 *
 * Returns the time between when a given user's WSU Accessibility certification
 * expires and the current time, formatted into a human readable format using
 * the WP `human_time_diff` function {@see https://developer.wordpress.org/reference/functions/human_time_diff/}
 * The time is returned in a human readable format such as "1 hour", "5 mins",
 * or "2 days".
 *
 * @since 0.2.0
 *
 * @param WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return string|false The time remaining until a11y certification expires for the given user or false if no data.
 */
function get_user_a11y_expire_diff( $user = '' ) {
	$user_status = get_a11y_user_meta( $user );

	if ( ! empty( $user_status ) ) {
		return human_time_diff( date_format( $user_status['expire_date'], 'U' ) );
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
 * @param  WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return string|false A string containing the number of days remaining in human-readable format or "0 days" if the period has expired. False if no data found or user is certified.
 */
function get_user_a11y_grace_period_remaining( $user = '' ) {
	$user_status = get_a11y_user_meta( $user );

	if ( empty( $user_status ) || ! $user_status['is_certified'] ) {
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
 * Gets the URL to the WSU Accessibility Training course.
 *
 * Note: This returns an unescaped URL string. Users should handle escaping
 * before using this.
 *
 * @since 0.8.0
 *
 * @param WP_User $user Optional. The WP_User instance of a user to check. Defaults to the current user.
 * @return string|false An unecaped URL to the WSU Accessibility Training course or false if the data is not found.
 */
function get_user_a11y_training_url( $user = '' ) {
	$user_status = get_a11y_user_meta( $user_id );

	if ( ! empty( $user_status ) ) {
		return $user_status['training_url'];
	}

	return false;
}
