<?php
/**
 * This file contains the Makes_Http_Requests trait
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Pending_Testable_Request;
use Mantle\Testing\Test_Response;
use RuntimeException;

use function Mantle\Support\Helpers\tap;

/**
 * Trait for Test_Case classes which want to make http-like requests against
 * WordPress.
 */
trait Makes_Http_Requests {
	/**
	 * Additional cookies for the request.
	 *
	 * @var array<string, string>
	 */
	protected array $default_cookies = [];

	/**
	 * Additional headers for the request.
	 *
	 * @var array<string, string>
	 */
	protected array $default_headers = [];

	/**
	 * Whether to use HTTPS by default.
	 */
	protected bool|null $default_https = null;

	/**
	 * The array of callbacks to be run before the event is started.
	 *
	 * @var array<callable>
	 */
	protected array $before_callbacks = [];

	/**
	 * The array of callbacks to be run after the event is finished.
	 *
	 * @var array<callable>
	 */
	protected array $after_callbacks = [];

	/**
	 * Setup the trait in the test case.
	 */
	public function makes_http_requests_set_up(): void {
		global $wp_rest_server, $wp_actions;

		// Clear out the existing REST Server to allow for REST API routes to be re-registered.
		$wp_rest_server = null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals

		// Mark 'rest_api_init' as an un-run action.
		unset( $wp_actions['rest_api_init'] );

		// Clear before/after callbacks.
		$this->before_callbacks = [];
		$this->after_callbacks  = [];
	}

	/**
	 * Create a new request instance with the default headers and cookies.
	 */
	protected function create_pending_request(): Pending_Testable_Request {
		return tap(
			new Pending_Testable_Request( $this ),
			function ( Pending_Testable_Request $request ): void {
				$request->cookies->add( $this->default_cookies );
				$request->headers->add( $this->default_headers );
				$request->with_https( $this->default_https ?? false );
			},
		);
	}

	/**
	 * Add default headers to all requests.
	 *
	 * @param array<string, string>|string $headers Headers to be added to all requests.
	 * @param string|null                  $value   Header value.
	 */
	public function add_default_header( array|string $headers, ?string $value = null ): void {
		if ( is_array( $headers ) ) {
			$this->default_headers = array_merge( $this->default_headers, $headers );
		} else {
			$this->default_headers[ $headers ] = $value;
		}
	}

	/**
	 * Set the default HTTPS setting for all requests.
	 *
	 * @param bool|null $value Whether to use HTTPS by default.
	 */
	public function set_default_https( bool|null $value = true ): void {
		$this->default_https = $value;
	}

	/**
	 * Flush all the configured headers.
	 */
	public function flush_default_headers(): static {
		$this->default_headers = [];

		return $this;
	}

	/**
	 * Create a pending request with a specific headers included.
	 *
	 * @param array $headers Headers for the request.
	 */
	public function with_headers( array $headers ): Pending_Testable_Request {
		return $this->create_pending_request()->with_headers( $headers );
	}

	/**
	 * Create a pending request with a specific header included.
	 *
	 * @param string $name  Header name (key).
	 * @param string $value Header value.
	 */
	public function with_header( string $name, string $value ): Pending_Testable_Request {
		return $this->with_headers( [ $name => $value ] );
	}

	/**
	 * Create a pending request with the HTTPS enabled/disabled.
	 *
	 * @param bool $value Whether to use HTTPS.
	 */
	public function with_https( bool $value = true ): Pending_Testable_Request {
		return $this->create_pending_request()->with_https( $value );
	}

	/**
	 * Set the referer header and previous URL session value in order to simulate
	 * a previous request.
	 *
	 * @param string $url URL for the referer header.
	 */
	public function from( string $url ): Pending_Testable_Request {
		return $this->with_header( 'referer', $url );
	}

	/**
	 * Make a request with a set of cookies.
	 *
	 * @param array<string, string>|string $cookies Cookies to be sent with the request.
	 * @param string|null                  $value   Cookie value.
	 */
	public function add_default_cookie( array|string $cookies, ?string $value = null ): static {
		if ( is_array( $cookies ) ) {
			$this->default_cookies = array_merge( $this->default_cookies, $cookies );
		} else {
			$this->default_cookies[ $cookies ] = $value;
		}

		return $this;
	}

	/**
	 * Flush the cookies for the request.
	 */
	public function flush_default_cookies(): static {
		$this->default_cookies = [];

		return $this;
	}

	/**
	 * Make a request with a set of cookies.
	 *
	 * @param array<string, string> $cookies Cookies to be sent with the request.
	 */
	public function with_cookies( array $cookies ): Pending_Testable_Request {
		return $this->create_pending_request()->with_cookies( $cookies );
	}

	/**
	 * Make a request with a specific cookie.
	 *
	 * @param string $name  Cookie name.
	 * @param string $value Cookie value.
	 */
	public function with_cookie( string $name, string $value ): Pending_Testable_Request {
		return $this->with_cookies( [ $name => $value ] );
	}

	/**
	 * Automatically follow any redirects returned from the response.
	 *
	 * @param bool $value Whether to follow redirects.
	 */
	public function following_redirects( bool $value = true ): Pending_Testable_Request {
		return $this->create_pending_request()->following_redirects( $value );
	}

	/**
	 * Visit the given URI with a GET request.
	 *
	 * @param mixed $uri     Request URI.
	 * @param array $headers Request headers.
	 */
	public function get( $uri, array $headers = [] ): Test_Response {
		return $this->create_pending_request()->get( $uri, $headers );
	}

	/**
	 * Legacy support for the WordPress core unit test's `go_to()` method.
	 *
	 * @deprecated Use {@see Mantle\Testing\Concerns\Makes_Http_Requests::get()} instead.
	 * @param string $url The URL for the request.
	 */
	public function go_to( string $url ): Test_Response {
		return $this->create_pending_request()->get( $url );
	}

	/**
	 * Visit the given URI with a GET request, expecting a JSON response.
	 *
	 * @param string $uri     URI to "get".
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function get_json( $uri, array $headers = [], int $options = 0 ): Test_Response {
		return $this->create_pending_request()->get_json( $uri, $headers, $options );
	}

	/**
	 * Call the given URI with a JSON request.
	 *
	 * @param string $method  Request method.
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 *
	 * @throws RuntimeException If not implemented.
	 */
	public function json( string $method, string $uri, array $data = [], array $headers = [], int $options = 1 ): Test_Response {
		return $this->create_pending_request()->json( $method, $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a POST request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function post( string $uri, array $data = [], array $headers = [] ): Test_Response {
		return $this->create_pending_request()->post( $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a POST request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function post_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->create_pending_request()->json( 'POST', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a PUT request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function put( string $uri, array $data = [], array $headers = [] ): Test_Response {
		return $this->create_pending_request()->put( $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a PUT request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function put_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->create_pending_request()->json( 'PUT', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a PATCH request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function patch( $uri, array $data = [], array $headers = [] ): Test_Response {
		return $this->create_pending_request()->patch( $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a PATCH request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function patch_json( $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->create_pending_request()->json( 'PATCH', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a DELETE request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function delete( $uri, array $data = [], array $headers = [] ): Test_Response {
		return $this->create_pending_request()->delete( $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a DELETE request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function delete_json( $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->create_pending_request()->json( 'DELETE', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a OPTIONS request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function options( $uri, array $data = [], array $headers = [] ): Test_Response {
		return $this->create_pending_request()->options( $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 * @return Test_Response
	 */
	public function options_json( $uri, array $data = [], array $headers = [], int $options = 0 ) {
		return $this->create_pending_request()->json( 'OPTIONS', $uri, $data, $headers, $options );
	}

	/**
	 * Call a given Closure/method before requests and inject its dependencies.
	 *
	 * @param callable|string $callback Callback to invoke.
	 * @return static
	 */
	public function before_request( $callback ) {
		$this->before_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Call a given Closure/method after requests and inject its dependencies.
	 *
	 * Callback will be invoked with a 'response' argument.
	 *
	 * @param callable|string $callback Callback to invoke.
	 * @return static
	 */
	public function after_request( $callback ) {
		$this->after_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Call all of the "before" callbacks for the requests.
	 */
	public function call_before_callbacks(): void {
		foreach ( $this->before_callbacks as $before_callback ) {
			$this->app->call( $before_callback );
		}
	}

	/**
	 * Call all of the "after" callbacks for the request.
	 *
	 * @param Test_Response $response Response object.
	 */
	public function call_after_callbacks( Test_Response $response ): void {
		foreach ( $this->after_callbacks as $after_callback ) {
			$this->app->call(
				$after_callback,
				[
					'response' => $response,
				]
			);
		}
	}
}
