<?php
/**
 * WSU Accessibility Status plugin formatting API.
 *
 * @package Setup
 * @since 1.0.0
 */

namespace WSUWP\A11yStatus\formatting;

/**
 * Sanitizes and formats the WSU API response for the MySQL database.
 *
 * @since 1.0.0
 *
 * @param array $raw_result The WSU API response result to sanitize.
 * @return array The sanitized API response.
 */
function sanitize_wsu_api_response( $raw_result ) {
	if ( 'True' === $raw_result['isCertified'] ) {
		$result['is_certified']  = true;
		$result['was_certified'] = true;
	} else {
		$result['is_certified'] = false;
	}

	$result['expire_date']  = date_create_from_format( 'M j Y g:iA', $raw_result['Expires'] );
	$result['last_checked'] = current_time( 'mysql' );
	$result['training_url'] = esc_url_raw( $raw_result['trainingURL'] );

	/**
	 * Filters the sanitized API response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $result The sanitized API response.
	 * @param array $raw_result The API response prior to sanitizing.
	 */
	$result = apply_filters( 'sanitize_wsu_api_response', $result, $raw_result );

	return $result;
}
