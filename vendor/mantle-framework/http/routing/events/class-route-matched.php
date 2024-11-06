<?php
/**
 * Route_Matched class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Events;

use Mantle\Http\Request;
use Mantle\Http\Routing\Route;
use WP_REST_Request;

/**
 * Event for route matched event.
 */
class Route_Matched {
	/**
	 * Constructor.
	 *
	 * @param Route|array             $route Route matched, Mantle route or an array of route
	 *                                       information for REST API routes.
	 * @param Request|WP_REST_Request $request Current request.
	 */
	public function __construct( public Route|array $route, public Request|WP_REST_Request $request ) {
	}
}
