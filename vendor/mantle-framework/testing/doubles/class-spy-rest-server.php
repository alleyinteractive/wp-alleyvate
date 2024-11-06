<?php // phpcs:disable

namespace Mantle\Testing\Doubles;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Spy REST Server
 *
 * A spy class for WP_REST_Server that allows us to inspect the headers and body
 * of the response without sending it to the client.
 */
class Spy_REST_Server extends WP_REST_Server {

	/**
	 * @var array<string, string>
	 */
	public ?array $sent_headers  = [];

	public ?int $sent_status = null;

	public ?string $sent_body = null;

	public ?WP_REST_Request $last_request = null;

	public bool $override_by_default = false;

	/**
	 * Gets the raw endpoints data from the server.
	 *
	 * @return array
	 */
	public function get_raw_endpoint_data() {
		return $this->endpoints;
	}

	/**
	 * Allow calling protected methods from tests.
	 *
	 * @param string $method Method to call.
	 * @param array  $args   Arguments to pass to the method.
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		return call_user_func_array( [ $this, $method ], $args );
	}

	/**
	 * Sends an HTTP status code.
	 *
	 * @param int $code HTTP status.
	 */
	protected function set_status( $code ) {
		$this->sent_status = $code;
	}

	/**
	 * Adds a header to the list of sent headers.
	 *
	 * @param string $header Header name.
	 * @param string $value  Header value.
	 */
	public function send_header( $header, $value ): void {
		$this->sent_headers[ $header ] = $value;
	}

	/**
	 * Removes a header from the list of sent headers.
	 *
	 * @param string $header Header name.
	 */
	public function remove_header( $header ): void {
		unset( $this->sent_headers[ $header ] );
	}

	/**
	 * Overrides the dispatch method so we can get a handle on the request object.
	 *
	 * @param  \WP_REST_Request $request Request to attempt dispatching.
	 * @return \WP_REST_Response Response returned by the callback.
	 */
	public function dispatch( $request ) {
		$this->last_request = $request;

		return parent::dispatch( $request );
	}

	/**
	 * Overrides the register_route method so we can re-register routes internally if needed.
	 *
	 * @param string $namespace  Namespace.
	 * @param string $route      The REST route.
	 * @param array  $route_args Route arguments.
	 * @param bool   $override   Optional. Whether the route should be overridden if it already exists.
	 *                           Default false. Also set `$GLOBALS['wp_rest_server']->override_by_default = true`
	 *                           to set overrides when you don't have access to the caller context.
	 */
	public function register_route( $namespace, $route, $route_args, $override = false ): void {
		parent::register_route( $namespace, $route, $route_args, $override || $this->override_by_default );
	}

	/**
	 * Serves the request and returns the result.
	 *
	 * @param string $path Optional. The request route. If not set, `$_SERVER['PATH_INFO']` will be used.
	 *                     Default null.
	 * @return null|false Null if not served and a HEAD request, false otherwise.
	 */
	public function serve_request( $path = null ) {
		ob_start();
		$result          = parent::serve_request( $path );
		$this->sent_body = ob_get_clean();
		return $result;
	}

	/**
	 * Clear the stored response data for the spy.
	 */
	public function reset_spy(): void {
		$this->sent_headers = null;
		$this->sent_status  = null;
		$this->sent_body    = null;
		$this->last_request = null;
	}
}
