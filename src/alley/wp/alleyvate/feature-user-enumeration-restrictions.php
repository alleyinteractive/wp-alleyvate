<?php
/**
 * User enumeration restrictions feature
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

add_filter( 'rest_request_before_callbacks', __NAMESPACE__ . '\restrict_rest_user_enumeration', 10, 3 );

/**
 * Require that a user be logged in before enumerating users over the REST API.
 *
 * This filter precedes and augments the 'permission_callback' for the route,
 * if any, since the result of a permission callback is not filterable.
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
 * @param array                                            $handler  Route handler used for the request.
 * @param WP_REST_Request                                  $request  Request used to generate the response.
 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed The updated result.
 */
function restrict_rest_user_enumeration( $response, $handler, $request ) {
	$route = $request->get_route();

	if (
		preg_match( '#^/wp/v\d+/users($|/)#', $route ) // This is a core users route.
		&& $request->get_method() === 'GET' // This is an enumeration request.
		&& ! is_user_logged_in() // Authorization check.
	) {
		$response = new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to list users.', 'alley' ),
			[
				'status' => rest_authorization_required_code(),
			],
		);
	}

	return $response;
}
