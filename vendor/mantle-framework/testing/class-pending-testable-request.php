<?php
/**
 * Pending_Testable_Request class file
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Database\Model\Model;
use Mantle\Framework\Http\Kernel as HttpKernel;
use Mantle\Http\Request;
use Mantle\Support\Str;
use Mantle\Support\Traits\Conditionable;
use Mantle\Testing\Doubles\Spy_REST_Server;
use Mantle\Testing\Exceptions\Exception;
use Mantle\Testing\Exceptions\WP_Redirect_Exception;
use Mantle\Testing\Test_Case;
use Mantle\Testing\Test_Response;
use Mantle\Testing\Utils;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use WP_Query;
use WP;

/**
 * Pending Testable Request
 *
 * A fluent request that is being made to the application. Performs a SUT
 * (System Under Test) operation on WordPress and returns a response.
 */
class Pending_Testable_Request {
	use Conditionable;

	/**
	 * Indicates whether redirects should be followed.
	 */
	public bool $follow_redirects = false;

	/**
	 * The headers for the request.
	 */
	public HeaderBag $headers;

	/**
	 * Indicates whether the request should be made over HTTPS.
	 */
	public bool $https = false;

	/**
	 * The cookies for the request.
	 */
	public InputBag $cookies;

	/**
	 * Store flag if the request was for the REST API.
	 *
	 * @var array{body: string, headers: array<string, string>, status: int}|null
	 */
	protected ?array $rest_api_response = null;

	/**
	 * Create a new pending testable request instance.
	 *
	 * @param Test_Case $test_case Test case instance.
	 */
	public function __construct( public Test_Case $test_case ) {
		$this->headers = new HeaderBag();
		$this->cookies = new InputBag();
	}

	/**
	 * Define additional headers to be sent with the request.
	 *
	 * @param array $headers Headers for the request.
	 */
	public function with_headers( array $headers ): static {
		$this->headers->add( $headers );

		return $this;
	}

	/**
	 * Define additional header to be sent with the request.
	 *
	 * @param string $name  Header name (key).
	 * @param string $value Header value.
	 */
	public function with_header( string $name, string $value ): static {
		return $this->with_headers( [ $name => $value ] );
	}

	/**
	 * Define whether the request should be made over HTTPS.
	 *
	 * @param bool $value Whether to use HTTPS.
	 */
	public function with_https( bool $value ): static {
		$this->https = $value;

		return $this;
	}

	/**
	 * Set the referer header and previous URL session value in order to simulate
	 * a previous request.
	 *
	 * @param string $url URL for the referer header.
	 */
	public function from( string $url ): static {
		return $this->with_header( 'referer', $url );
	}

	/**
	 * Make a request with a set of cookies.
	 *
	 * @param array<string, string> $cookies Cookies to be sent with the request.
	 */
	public function with_cookies( array $cookies ): static {
		$this->cookies->add( $cookies );

		return $this;
	}

	/**
	 * Make a request with a specific cookie.
	 *
	 * @param string $name  Cookie name.
	 * @param string $value Cookie value.
	 */
	public function with_cookie( string $name, string $value ): static {
		return $this->with_cookies( [ $name => $value ] );
	}

	/**
	 * Automatically follow any redirects returned from the response.
	 *
	 * @param bool $value Whether to follow redirects.
	 */
	public function following_redirects( bool $value = true ): static {
		$this->follow_redirects = $value;

		return $this;
	}

	/**
	 * Visit the given URI with a GET request.
	 *
	 * @param mixed $uri     Request URI.
	 * @param array $headers Request headers.
	 */
	public function get( $uri, array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'GET', $uri, [], $server );
	}

	/**
	 * Legacy support for the WordPress core unit test's `go_to()` method.
	 *
	 * @deprecated Use {@see Mantle\Testing\Concerns\Makes_Http_Requests::get()} instead.
	 * @param string $url The URL for the request.
	 */
	public function go_to( string $url ): Test_Response {
		return $this->get( $url );
	}

	/**
	 * Infer the request URL from an object like a post or term.
	 *
	 * @param mixed $source Source from which to infer the URL.
	 */
	protected function infer_url( mixed $source ): string {
		return match ( true ) {
			$source instanceof \WP_Post => get_permalink( $source ),
			$source instanceof \WP_Term => get_term_link( $source ),
			$source instanceof \WP_User => \get_author_posts_url( $source->ID ),
			$source instanceof Model && method_exists( $source, 'permalink' ) => $source->permalink(),
			default => '',
		};
	}

	/**
	 * Transform headers array to array of $_SERVER vars with HTTP_* format.
	 *
	 * @param array $headers Headers to convert to $_SERVER vars.
	 */
	protected function transform_headers_to_server_vars( array $headers ): array {
		$headers           = array_merge( $this->headers->all(), $headers );
		$formatted_headers = [];

		foreach ( $headers as $name => $value ) {
			$name = strtr( strtoupper( $name ), '-', '_' );

			$formatted_headers[ $this->format_server_header_key( $name ) ] = $value;
		}

		return $formatted_headers;
	}

	/**
	 * Format the header name for the server array.
	 *
	 * @param string $name Header name.
	 * @return string
	 */
	protected function format_server_header_key( $name ) {
		if ( ! Str::starts_with( $name, 'HTTP_' ) && 'CONTENT_TYPE' !== $name && 'REMOTE_ADDR' !== $name ) {
			return 'HTTP_' . $name;
		}

		return $name;
	}

	/**
	 * Call the given URI and return the Response.
	 *
	 * @param string      $method     Request method.
	 * @param mixed       $uri        Request URI.
	 * @param array       $parameters Request params.
	 * @param array       $server     Server vars.
	 * @param array       $cookies Cookies to be sent with the request.
	 * @param string|null $content Request content.
	 */
	public function call( string $method, mixed $uri, array $parameters = [], array $server = [], array $cookies = [], ?string $content = null ): Test_Response {
		$this->reset_request_state();

		if ( ! is_string( $uri ) ) {
			$uri = $this->infer_url( $uri );
		}

		// Build a full URL from partial URIs.
		if ( '/' === $uri[0] ) {
			$url = 'https://' . WP_TESTS_DOMAIN . $uri;
		} elseif ( false === strpos( $uri, '://' ) ) {
			$url = 'https://' . WP_TESTS_DOMAIN . '/' . $uri;
		} else {
			$url = $uri;
		}

		$this->set_server_state(
			$method,
			$url,
			$server,
			$parameters,
			array_merge( $this->cookies->all(), $cookies ),
		);

		$response_status  = null;
		$response_headers = [];

		$intercept_status = function ( $status_header, $code ) use ( &$response_status ): int {
			$response_status = $code;

			return $code;
		};

		$intercept_headers = function ( $send_headers ) use ( &$response_headers ): array {
			$response_headers = $send_headers;

			return $send_headers;
		};

		$intercept_redirect = function ( $location, $status ) use ( &$response_status, &$response_headers ): void {
			$response_status              = $status;
			$response_headers['Location'] = $location;
			throw new WP_Redirect_Exception();
		};

		add_filter( 'exit_on_http_head', '__return_false', 9999 );
		add_filter( 'wp_using_themes', '__return_true', 9999 );

		$this->test_case->call_before_callbacks();

		// Attempt to run the query through the Mantle router.
		if ( isset( $this->test_case->app['router'] ) ) {
			$kernel = new HttpKernel( $this->test_case->app, $this->test_case->app['router'] );

			// Setup the current request object.
			$request = new Request(
				$_GET,
				$_POST,
				[],
				$_COOKIE,
				$_FILES,
				$_SERVER,
				$content
			);

			// Mirror the logic from Request::createFromGlobals().
			if (
				str_starts_with( (string) $request->headers->get( 'CONTENT_TYPE', '' ), 'application/x-www-form-urlencoded' )
			&& \in_array( strtoupper( (string) $request->server->get( 'REQUEST_METHOD', 'GET' ) ), [ 'PUT', 'DELETE', 'PATCH' ], true )
			) {
				parse_str( $request->getContent(), $data );

				$request->request = new InputBag( $data );
			}

			$this->test_case->app->instance( 'request', $request );

			$response = $kernel->send_request_through_router( $request );

			if ( $response ) {
				$response = new Test_Response(
					$response->getContent(),
					$response->getStatusCode(),
					$response->headers->all(),
					$this->test_case,
				);
			}
		}

		// Attempt to run the query through the Mantle router.
		if ( empty( $response ) ) {
			add_filter( 'status_header', $intercept_status, 9999, 2 );
			add_filter( 'wp_headers', $intercept_headers, 9999 );
			add_filter( 'wp_redirect', $intercept_redirect, 9999, 2 ); // @phpstan-ignore-line Filter callback

			ob_start();

			$this->setup_wordpress_query();

			if ( $this->rest_api_response ) {
				// Use the response from the REST API server.
				ob_end_clean();

				$response_content = $this->rest_api_response['body'];
				$response_headers = array_merge( (array) $response_headers, $this->rest_api_response['headers'] );
				$response_status  = $this->rest_api_response['status'];
			} else {
				try {
					// Execute the request, inasmuch as WordPress would.
					require ABSPATH . WPINC . '/template-loader.php';
				} catch ( Exception ) { // phpcs:ignore
					// Mantle Exceptions are thrown to prevent some code from running, e.g.
					// the tail end of wp_redirect().
				}

				$response_content = ob_get_clean();
			}

			remove_filter( 'status_header', $intercept_status, 9999 );
			remove_filter( 'wp_headers', $intercept_headers, 9999 );
			remove_filter( 'wp_redirect', $intercept_redirect, 9999 );

			$response = new Test_Response(
				$response_content,
				$response_status ?? 200,
				$response_headers,
				$this->test_case,
			);
		}

		$response->set_app( $this->test_case->app );

		$this->test_case->call_after_callbacks( $response );

		remove_filter( 'exit_on_http_head', '__return_false', 9999 );
		remove_filter( 'wp_using_themes', '__return_true', 9999 );

		if ( $this->follow_redirects ) {
			return $this->follow_redirects( $response );
		}

		return $response;
	}

	/**
	 * Reset the global state related to requests.
	 */
	protected function reset_request_state(): void {
		// phpcs:disable

		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET    = [];
		$_POST   = [];
		$_COOKIE = [];
		foreach (
			[
				'query_string',
				'id',
				'postdata',
				'authordata',
				'day',
				'currentmonth',
				'page',
				'pages',
				'multipage',
				'more',
				'numpages',
				'pagenow',
				'current_screen',
			] as $v
		) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}

		$this->rest_api_response = null;

		// Remove all HTTP_* headers from $_SERVER.
		foreach ( $_SERVER as $key => $value ) {
			if ( str_starts_with( $key, 'HTTP_' ) && 'HTTP_HOST' !== $key ) {
				unset( $_SERVER[ $key ] );
			}

			if ( isset( $_SERVER['CONTENT_TYPE'] ) ) {
				unset( $_SERVER['CONTENT_TYPE'] );
			}

			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				unset( $_SERVER['REMOTE_ADDR'] );
			}
		}

		if ( $this->https ) {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset( $_SERVER['HTTPS'] );
		}

		// phpcs:enable
	}

	/**
	 * Set $_SERVER keys for the request.
	 *
	 * @param string $method HTTP method.
	 * @param string $url    Request URI.
	 * @param array  $server Additional $_SERVER args to set.
	 * @param array  $data   POST data to set.
	 * @param array  $cookies Cookies to be sent with the request.
	 */
	protected function set_server_state( $method, $url, $server, $data, array $cookies = [] ): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$_SERVER['REQUEST_METHOD'] = strtoupper( $method );
		$_SERVER['SERVER_NAME']    = WP_TESTS_DOMAIN;
		$_SERVER['SERVER_PORT']    = '80';
		unset( $_SERVER['PATH_INFO'] );

		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = $parts['path'] ?? '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				parse_str( (string) $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}

		$_SERVER['REQUEST_URI'] = $req;

		$_POST = $data;

		// The ini setting variable_order determines order; assume GP for simplicity.
		$_REQUEST = array_merge( $_GET, $_POST );
		$_SERVER  = array_merge( $_SERVER, $server );

		// Set the cookies for the request.
		$_COOKIE = $cookies; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

		// phpcs:enable
	}

	/**
	 * Sets the WordPress query as if a given URL has been requested.
	 *
	 * This sets:
	 * - The super globals.
	 * - The globals.
	 * - The query variables.
	 * - The main query.
	 */
	protected function setup_wordpress_query(): void {
		Test_Case::flush_cache();

		// phpcs:disable WordPress.WP.GlobalVariablesOverride
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];

		$public_query_vars  = $GLOBALS['wp']->public_query_vars;
		$private_query_vars = $GLOBALS['wp']->private_query_vars;

		$GLOBALS['wp']                     = new WP();
		$GLOBALS['wp']->public_query_vars  = $public_query_vars;
		$GLOBALS['wp']->private_query_vars = $private_query_vars;

		Utils::cleanup_query_vars();

		$this->replace_rest_api();

		$GLOBALS['wp']->main();

		// phpcs:enable WordPress.WP.GlobalVariablesOverride
	}

	/**
	 * Replace the REST API request.
	 *
	 * This will:
	 * - Initiate the REST API.
	 * - Set the WordPress REST Server to use the Mantle Spy REST Server to allow
	 *   for the responses to be read.
	 * - Replace the REST API `rest_api_loaded` method to allow the REST response
	 *   to be read without terminating the script.
	 */
	protected function replace_rest_api(): void {
		// Ensure the Mantle REST Spy Server is used.
		add_filter( 'wp_rest_server_class', [ Utils::class, 'wp_rest_server_class_filter' ], PHP_INT_MAX );

		rest_api_init();

		// Replace the `rest_api_loaded()` method with one we can control.
		remove_filter( 'parse_request', 'rest_api_loaded' );
		add_action( 'parse_request', [ $this, 'serve_rest_api_request' ] );
	}

	/**
	 * Server the REST API request if applicable.
	 *
	 * Mirroring `{@see rest_api_loaded()}`, this method fires the REST API
	 * request and stores the response.
	 */
	public function serve_rest_api_request(): void {
		if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return;
		}

		$server = rest_get_server();

		if ( $server instanceof Spy_REST_Server ) {
			// Reset the spy to ensure we're not using any previous data.
			$server->reset_spy();

			$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );

			if ( empty( $route ) ) {
				$route = '/';
			}

			$server->serve_request( $route );

			if ( isset( $server->sent_body ) ) {
				$this->rest_api_response = [
					'body'    => $server->sent_body,
					'headers' => $server->sent_headers,
					'status'  => $server->sent_status,
				];
			}
		} else {
			trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				'Expected the Mantle Spy REST Server to be used. Not able to be tested against.',
				E_USER_WARNING,
			);
		}
	}

	/**
	 * Turn the given URI into a fully qualified URL.
	 *
	 * @param string $uri URI to fully-qualify.
	 */
	protected function prepare_url_for_request( $uri ): string {
		return Str::trailing_slash( home_url( $uri ) );
	}

	/**
	 * Follow a redirect chain until a non-redirect is received.
	 *
	 * @param Test_Response $response Test response.
	 */
	protected function follow_redirects( $response ): Test_Response {
		while ( $response->is_redirect() ) {
			$response = $this->get( $response->get_header( 'Location' ) );
		}

		$this->follow_redirects = false;

		return $response;
	}

	/**
	 * Visit the given URI with a GET request, expecting a JSON response.
	 *
	 * @param string $uri     URI to "get".
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function get_json( $uri, array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'GET', $uri, [], $headers, $options );
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
		$content = json_encode( $data, $options ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode

		$headers = array_merge(
			$headers,
			[
				'Accept'         => 'application/json',
				'Content-Length' => mb_strlen( $content, '8bit' ),
				'Content-Type'   => 'application/json',
			]
		);

		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( $method, $uri, $data, $server, [], $content );
	}

	/**
	 * Visit the given URI with a POST request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function post( string $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'POST', $uri, $data, $server );
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
		return $this->json( 'POST', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a PUT request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function put( string $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PUT', $uri, $data, $server );
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
		return $this->json( 'PUT', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a PATCH request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function patch( $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PATCH', $uri, $data, $server );
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
		return $this->json( 'PATCH', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a DELETE request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function delete( $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'DELETE', $uri, $data, $server );
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
		return $this->json( 'DELETE', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a OPTIONS request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function options( $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'OPTIONS', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function options_json( $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'OPTIONS', $uri, $data, $headers, $options );
	}
}
