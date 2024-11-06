<?php
/**
 * Helper functions
 *
 * @package wp-concurrent-remote-requests
 */

namespace Alley\WP\Concurrent_Remote_Requests;

use WP_Error;

/**
 * Performs an HTTP request and returns its response.
 *
 * There are other API functions available which abstract away the HTTP method:
 *
 *  - Default 'GET'  for wp_remote_get()
 *  - Default 'POST' for wp_remote_post()
 *  - Default 'HEAD' for wp_remote_head()
 *
 * @since 2.7.0
 *
 * @see WP_Http::format_request() For information on default arguments.
 *
 * @param string|array $url  URL to retrieve or array of remote requests that will be run in parallel.
 * @param array        $args Optional. Request arguments. Default empty array.
 * @return array|WP_Error {
 *     The response array or a WP_Error on failure.
 *
 *     @type string[]                       $headers       Array of response headers keyed by their name.
 *     @type string                         $body          Response body.
 *     @type array                          $response      {
 *         Data about the HTTP response.
 *
 *         @type int|false    $code    HTTP response code.
 *         @type string|false $message HTTP response message.
 *     }
 *     @type WP_HTTP_Cookie[]               $cookies       Array of response cookies.
 *     @type WP_HTTP_Requests_Response|null $http_response Raw HTTP response object.
 * }
 */
function wp_remote_request( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->request( $url, $args );
}

/**
 * Performs an HTTP request using the GET method and returns its response.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string|array $url  URL to retrieve or array of remote requests that will be run in parallel.
 * @param array        $args Optional. Request arguments. Default empty array.
 * @return array|WP_Error The response or WP_Error on failure.
 */
function wp_remote_get( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->get( $url, $args );
}

/**
 * Performs an HTTP request using the POST method and returns its response.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::format_request() For default arguments information.
 *
 * @param string|array $url  URL to retrieve or array of remote requests that will be run in parallel.
 * @param array        $args Optional. Request arguments. Default empty array.
 * @return array|WP_Error The response or WP_Error on failure.
 */
function wp_remote_post( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->post( $url, $args );
}

/**
 * Performs an HTTP request using the HEAD method and returns its response.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::format_request() For default arguments information.
 *
 * @param string|array $url  URL to retrieve or array of remote requests that will be run in parallel.
 * @param array        $args Optional. Request arguments. Default empty array.
 * @return array|WP_Error The response or WP_Error on failure.
 */
function wp_remote_head( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->head( $url, $args );
}

/**
 * Returns the initialized WP_Http Object
 *
 * @since 2.7.0
 * @access private
 *
 * @return WP_Http HTTP Transport object.
 */
function _wp_http_get_object() {
	static $http = null;

	if ( is_null( $http ) ) {
		$http = new WP_Http();
	}

	return $http;
}
