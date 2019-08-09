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

namespace WSUWP\A11yStatus\WSU_API;

use WSUWP\A11yStatus\Init;
use WSUWP\A11yStatus\formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The WSUWP A11y Status WSU API class.
 *
 * @since 1.0.0
 */
class WSU_API {
	/**
	 * The API request result.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $result;

	/**
	 * The WSU Accessibility Training API response.
	 *
	 * @since 0.1.0
	 * @var array|WP_Error
	 */
	protected $wsu_api_response;

	/**
	 * The WSU Accessibility Training API endpoint.
	 *
	 * @since 0.1.0
	 * @since 1.0.0 renamed to $api_url
	 * @var string
	 */
	protected $api_url;

	/**
	 * The WSU net ID(s) to retrieve data for.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	protected $api_nid;

	/**
	 * Connects to the API and does stuff
	 *
	 */
	public function __construct( $api_url, $api_nid ) {
		$this->api_url = $api_url;
		$this->api_nid = $api_nid;

		$this->fetch_api_response();
	}

	/**
	 * Gets the WSU Accessibility Training status info from the WSU API.
	 *
	 * Connect to the API to retrieve info for given username(s) in JSON
	 * format and parse it, then add several additional items to the resulting
	 * array and return it. The returned array should include the following
	 * key-value pairs:
	 *
	 * (
	 *   'isCertified'    => (bool)     whether the user is a11y certified
	 *   'Expires'        => (DateTime) the expiration date
	 *   'trainingURL'    => (string)   the training URL
	 *   'last_checked'   => (string)   the date last checked in mysql format
	 *   'was_certified' => (bool)     whether the user was ever certified
	 * )
	 *
	 * @since 1.0.0
	 *
	 * @param string $url      The WSU Accessibility Training Status API url.
	 * @param string $username The WSU NID of the user to retrieve data for.
	 * @return array|false Array of accessibility training status data for the given username.
	 */
	public function fetch_api_response() {
		// Build the request URI.
		$request_uri = add_query_arg( array( 'NID' => $this->api_nid ), $this->api_url );

		$this->wsu_api_response = wp_remote_get( esc_url_raw( $request_uri ) );

		// Check for a successful response.
		if ( is_wp_error( $this->wsu_api_response ) ) {
			Init\Setup::error( $this->wsu_api_response->get_error_message() );

			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $this->wsu_api_response );

		// Check for a successful connection.
		if ( 200 !== (int) $response_code ) {
			Init\Setup::error(
				sprintf(
					/* translators: 1: the API requst URL, 2: an HTTP error response code */
					__( 'WSU API request failed. The request for <%1$s> returned HTTP code: %2$s', 'wsuwp-a11y-status' ),
					esc_url_raw( $request_uri ),
					$response_code
				)
			);

			return false;
		}

		// Parse the desired content from the API response.
		$this->result = json_decode( wp_remote_retrieve_body( $this->wsu_api_response ), true );
		$this->result = array_shift( $this->result );

		// Sanitize API data for saving in the database.
		$this->result = formatting\sanitize_wsu_api_response( $this->result );

		return $this->result;
	}
}
