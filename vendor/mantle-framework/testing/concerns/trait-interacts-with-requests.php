<?php
/**
 * Interacts_With_Requests trait file.
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName, Squiz.Commenting.FunctionComment.SpacingAfterParamType
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Http_Client\Request;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testing\Mock_Http_Sequence;
use Mantle\Testing\Utils;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;
use WP_Error;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\value;

/**
 * Allow Mock HTTP Requests
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Interacts_With_Requests {
	/**
	 * Storage of the callbacks to mock the requests.
	 *
	 * @var Collection<int, callable(string, array): Mock_Http_Response|Arrayable|WP_Error|null>
	 */
	protected Collection $stub_callbacks;

	/**
	 * Storage of request URLs.
	 *
	 * @var Collection<int, Request>
	 */
	protected Collection $recorded_requests;

	/**
	 * Flag to prevent external requests from being made. By default, this is
	 * false.
	 *
	 * @var Mock_Http_Response|callable|bool
	 */
	protected mixed $preventing_stray_requests = false;

	/**
	 * Recorded actual HTTP requests made during the test.
	 *
	 * @var Collection<int, string>
	 */
	protected Collection $recorded_actual_requests;

	/**
	 * Setup the trait.
	 */
	public function interacts_with_requests_set_up(): void {
		$this->stub_callbacks           = collect();
		$this->recorded_requests        = collect();
		$this->recorded_actual_requests = collect();

		\add_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX, 3 );
	}

	/**
	 * Remove the filter to intercept the request.
	 */
	public function interacts_with_requests_tear_down(): void {
		\remove_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX );

		$this->report_stray_requests();
	}

	/**
	 * Prevent stray external requests.
	 *
	 * @param Mock_Http_Response|\Closure|bool $response A default response or callback to use, boolean otherwise.
	 */
	public function prevent_stray_requests( Mock_Http_Response|Closure|bool $response = true ): void {
		$this->preventing_stray_requests = $response;
	}

	/**
	 * Allow stray external requests.
	 */
	public function allow_stray_requests(): void {
		$this->preventing_stray_requests = false;
	}

	/**
	 * Fake a remote request.
	 *
	 * A response object could be passed with a matching URL to fake. Also supports passing
	 * a closure that will be invoked when the HTTP request is made. The closure will be passed
	 * the request URL and request arguments to determine if it wishes to make a response. For more
	 * information on how this is used, see the `create_stub_request_callback()` method below and the
	 * relevant test for the trait (Mantle\Tests\Testing\Concerns\Test_Interacts_With_Requests).
	 *
	 * Example:
	 *
	 *   $this->fake_request();
	 *   $this->fake_request( 'https://testing.com/*' );
	 *   $this->fake_request( 'https://testing.com/*' )->with_response_code( 404 )->with_body( 'test body' );
	 *   $this->fake_request( fn () => Mock_Http_Response::create()->with_body( 'test body' ) );
	 *   $this->fake_request( 'https://testing.com/', fn () => Mock_Http_Response::create()->with_body( 'test body' ) );
	 *   $this->fake_request( [ 'https://example.org' => Mock_Http_Response::create()->with_body( 'test body' ) ] );
	 *
	 * @link https://mantle.alley.com/docs/testing/remote-requests#faking-requests Documentation
	 *
	 * @throws InvalidArgumentException Thrown on invalid argument when response object passed twice.
	 * @throws InvalidArgumentException Thrown on invalid argument.
	 * @throws InvalidArgumentException Thrown on invalid response type.
	 *
	 * @template TCallableReturn of Mock_Http_Sequence|Mock_Http_Response|Arrayable|null
	 *
	 * @param (callable(string, array): TCallableReturn)|Mock_Http_Response|string|array<string, Mock_Http_Response|callable> $url_or_callback URL to fake, array of URL and response pairs, or a closure
	 *                                                                                                                                         that will return a faked response.
	 * @param Mock_Http_Response|callable $response Optional response object, defaults to a 200 response with no body.
	 * @param string $method Optional request method to apply to, defaults to all. Does not apply to array of URL and response pairs OR callbacks.
	 */
	public function fake_request(
		Mock_Http_Response|callable|string|array|null $url_or_callback = null,
		Mock_Http_Response|callable $response = null,
		?string $method = null
	): static|Mock_Http_Response {
		if ( is_array( $url_or_callback ) ) {
			$this->stub_callbacks = $this->stub_callbacks->merge(
				collect( $url_or_callback )->map(
					fn ( $response, $url_or_callback ) => $this->create_stub_request_callback( $url_or_callback, $response, $method ),
				)
			);

			return $this;
		}

		// Allow a callback to be passed instead.
		if ( is_callable( $url_or_callback ) ) {
			$this->stub_callbacks->push( $url_or_callback );

			return $this;
		}

		// Prevent duplicate responses from being passed.
		if ( $url_or_callback instanceof Mock_Http_Response && $response instanceof Mock_Http_Response ) {
			throw new InvalidArgumentException( 'Response object passed twice, only one response object should be passed.' );
		}

		// Allow for a catch-all response to be passed in the first argument.
		if ( $url_or_callback instanceof Mock_Http_Response && ! $response ) {
			$this->stub_callbacks->push( $this->create_stub_request_callback( '*', $url_or_callback, $method ) );

			return $url_or_callback;
		}

		// Throw an exception on an unknown argument.
		if ( ! is_string( $url_or_callback ) && ! is_null( $url_or_callback ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Expected a URL string or a callback, got %s.',
					gettype( $url_or_callback )
				)
			);
		}

		// Renaming for clarity.
		$url = $url_or_callback ?? '*';

		// If no arguments passed, assume that all requests should return an 200 response.
		if ( is_null( $response ) ) {
			$response = new Mock_Http_Response();
		}

		// Ensure that the response is an instance of Mock_Http_Response.
		if ( ! $response instanceof Mock_Http_Response ) {
			throw new InvalidArgumentException( 'Response must be an instance of Mock_Http_Response or callable, ' . gettype( $response ) . ' given.' );
		}

		$this->stub_callbacks->push(
			$this->create_stub_request_callback( $url, $response, $method ),
		);

		return $response;
	}

	/**
	 * Fluently build a fake request sequence.
	 *
	 * @param string             $url URL to fake (supports * for wildcard matching).
	 * @param string|null        $method Request method, optional.
	 */
	public function fake_request_sequence( string $url, ?string $method = null ): Mock_Http_Sequence {
		$sequence = Mock_Http_Sequence::create();

		$this->fake_request( [ $url => $sequence ], method: $method );

		return $sequence;
	}

	/**
	 * Create a mock HTTP response.
	 *
	 * @param string $body   Response body.
	 * @param array $headers Response headers.
	 */
	public function mock_response( string $body = '', array $headers = [] ): Mock_Http_Response {
		return new Mock_Http_Response( $body, $headers );
	}

	/**
	 * Filters pre_http_request to intercept the request, mock a response, and
	 * return it. If the response has already been preempted, the preempt will
	 * be returned instead. Regardless, this object unhooks itself from the
	 * pre_http_request filter.
	 *
	 * @param false|array|\WP_Error $preempt      Whether to preempt an HTTP request's return value. Default false.
	 * @param array                 $request_args HTTP request arguments.
	 * @param string                $url          The request URL.
	 * @return mixed Array if the request has been preempted, any value that's
	 *               not false otherwise.
	 *
	 * @throws RuntimeException If the request was made without a matching faked request.
	 */
	public function pre_http_request( $preempt, $request_args, $url ) {
		// Bail early if the preemption is already set.
		if ( false !== $preempt ) {
			return $preempt;
		}

		$request = new Request( $request_args, $url );

		$this->recorded_requests[] = $request;

		$stub = $this->get_stub_response( $url, $request_args );

		if ( $stub ) {
			// If the request is for streaming the response to a file, store the
			// response body in the requested file.
			if ( ! is_wp_error( $stub ) && ! empty( $request_args['stream'] ) ) {
				try {
					return $this->store_streamed_response( $url, $stub, $request_args );
				} catch ( RuntimeException $e ) {
					return new WP_Error( 'http_request_failed', $e->getMessage() );
				}
			}

			return $stub;
		}

		// Store the actual request for later reporting.
		$this->recorded_actual_requests[] = method_exists( $this, 'getName' ) ? static::class . '::' . $this->getName() : static::class;

		return $preempt;
	}

	/**
	 * Retrieve the stub response for a given request URL and arguments.
	 *
	 * @throws RuntimeException If the request was made without a matching
	 *                          faked request when external requests are prevented.
	 *
	 * @param string $url          Request URL.
	 * @param array  $request_args Request arguments.
	 */
	protected function get_stub_response( string $url, array $request_args ): array|WP_Error|null {
		if ( ! $this->stub_callbacks->is_empty() ) {
			foreach ( $this->stub_callbacks as $stub_callback ) {
				$response = $stub_callback( $url, $request_args );

				if ( $response instanceof Mock_Http_Response || $response instanceof Arrayable ) {
					return $response->to_array();
				}

				// Throw an error when an unknown response type is returned from the callback.
				if ( $response && ! is_array( $response ) && ! is_wp_error( $response ) ) {
					throw new RuntimeException(
						sprintf(
							'Unknown response type returned for faked request to [%s]. Expected a (%s|%s|%s|array), got %s.',
							$url,
							Mock_Http_Response::class,
							Arrayable::class,
							WP_Error::class,
							gettype( $response )
						),
					);
				}

				if ( ! is_null( $response ) ) {
					return $response;
				}
			}
		}

		if ( false !== $this->preventing_stray_requests ) {
			$prevent = value( $this->preventing_stray_requests );

			if ( $prevent instanceof Mock_Http_Response || $prevent instanceof Arrayable ) {
				return $prevent->to_array();
			}

			throw new RuntimeException( "Attempted request to [{$url}] without a matching fake." );
		}

		return null;
	}

	/**
	 * Store the response body in the requested file for a streamed request.
	 *
	 * @throws RuntimeException If the directory is not writable.
	 * @throws RuntimeException If the file cannot be written.
	 *
	 * @param string $url          Request URL.
	 * @param array  $response     Stubbed response array.
	 * @param array  $request_args Request arguments.
	 * @return array The modified response array.
	 */
	protected function store_streamed_response( string $url, array $response, array $request_args ): array {
		if ( empty( $request_args['stream'] ) ) {
			return $response;
		}

		if ( empty( $request_args['filename'] ) ) {
			$request_args['filename'] = get_temp_dir() . basename( $url );
		}

		if ( ! wp_is_writable( dirname( (string) $request_args['filename'] ) ) ) {
			throw new RuntimeException( "The directory [{$request_args['filename']}] is not writable." );
		}

		if ( ! file_put_contents( $request_args['filename'], $response['body'] ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			throw new RuntimeException( "Unable to write to the file [{$request_args['filename']}]." );
		}

		// Replace the response body with an empty string to prevent the response
		// from being returned in the body (since it was streamed to a file).
		$response['body']     = '';
		$response['filename'] = $request_args['filename'];

		return $response;
	}

	/**
	 * Retrieve a callback for the stubbed response.
	 *
	 * @param string                      $url URL to stub.
	 * @param callable|Mock_Http_Response $response Response to send.
	 * @param string                      $method Request method, optional.
	 */
	protected function create_stub_request_callback( string $url, Mock_Http_Response|callable $response, ?string $method = null ): callable {
		return function ( string $request_url, array $request_args ) use ( $url, $response, $method ) {
			if ( ! Str::is( Str::start( $url, '*' ), $request_url ) ) {
				return;
			}

			// Validate the request method for the stub callback.
			if ( $method && isset( $request_args['method'] ) && strtoupper( $method ) !== strtoupper( (string) $request_args['method'] ) ) {
				return;
			}

			return is_callable( $response )
				? $response( $request_url, $request_args )
				: $response;
		};
	}

	/**
	 * Get a collection of the request pairs matching the given truth test.
	 *
	 * @param callable $callback Callback to invoke on each request.
	 */
	protected function recorded_requests( callable $callback ): Collection {
		if ( empty( $this->recorded_requests ) ) {
				return collect();
		}

		return collect( $this->recorded_requests )->filter( fn ( Request $response ) => $callback( $response ) );
	}

	/**
	 * Report any stray requests that were made during the unit test.
	 */
	protected function report_stray_requests(): void {
		if ( ! isset( $this->recorded_actual_requests ) || $this->recorded_actual_requests->is_empty() ) {
			return;
		}

		$this->recorded_actual_requests->map(
			fn ( $method, $index ) => Utils::info(
				"An HTTP request was made in <span class='font-bold'>{$method}</span> to <span class='font-bold'>{$this->recorded_requests[ $index ]->url()}</span> but no faked response was found.",
				'HTTP Requests',
			)
		);
	}

	/**
	 * Assert that a request was sent.
	 *
	 * @param string|callable $url_or_callback Specific URL to check against or a callback to
	 *                                         check against specific request information.
	 * @param int             $expected_times Number of times the request should have been
	 *                                        sent, optional.
	 */
	public function assertRequestSent( string|callable|null $url_or_callback = null, int $expected_times = null ): void {
		if ( is_null( $url_or_callback ) ) {
			PHPUnit::assertTrue( $this->recorded_requests->is_not_empty(), 'A request was made.' );

			return;
		}

		if ( is_string( $url_or_callback ) ) {
			$url_or_callback = fn ( $request ) => Str::is( $url_or_callback, $request->url() );
		}

		$requests = $this->recorded_requests( $url_or_callback )->count();

		PHPUnit::assertTrue(
			$requests > 0,
			'An expected request was not recorded.',
		);

		if ( null !== $expected_times ) {
			PHPUnit::assertEquals( $expected_times, $requests, 'Expected request count does not match.' );
		}
	}

	/**
	 * Assert that a request was not sent.
	 *
	 * @param string|callable $url_or_callback URL to check against or callback.
	 */
	public function assertRequestNotSent( string|callable|null $url_or_callback = null ): void {
		if ( is_string( $url_or_callback ) ) {
			$url_or_callback = fn ( $request ) => Str::is( $url_or_callback, $request->url() );
		}

		PHPUnit::assertEquals(
			0,
			$this->recorded_requests( $url_or_callback )->count(),
			'Unexpected request was recorded.',
		);
	}

	/**
	 * Assert that no request was sent.
	 */
	public function assertNoRequestSent(): void {
		PHPUnit::assertEmpty(
			$this->recorded_requests,
			'Requests were recorded',
		);
	}

	/**
	 * Assert a specific request count was sent.
	 *
	 * @param int $count Request count.
	 */
	public function assertRequestCount( int $count ): void {
		PHPUnit::assertCount( $count, $this->recorded_requests );
	}
}
