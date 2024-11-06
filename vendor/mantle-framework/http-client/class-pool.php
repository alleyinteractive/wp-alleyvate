<?php
/**
 * Pool class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use function Alley\WP\Concurrent_Remote_Requests\wp_remote_request;

/**
 * Http Pool for making requests concurrently.
 *
 * @mixin \Mantle\Http_Client\Pending_Request
 */
class Pool {
	/**
	 * Pool of pending requests.
	 *
	 * @var array<string|int, Pending_Request>
	 */
	protected array $pool = [];

	/**
	 * Constructor.
	 *
	 * @param Pending_Request $base_request
	 */
	public function __construct( protected Pending_Request $base_request ) {
	}

	/**
	 * Create a pending request for the pool
	 */
	protected function create_request(): Pending_Request {
		return ( clone $this->base_request )->pooled();
	}

	/**
	 * Retrieve the requests for the given pool
	 *
	 * @throws Http_Client_Exception Thrown in error in response from wp_remote_request().
	 * @return array<int|string, Response>
	 */
	public function results(): array {
		// Execute the pool of requests.
		$results = wp_remote_request(
			array_map(
				fn ( Pending_Request $request ) => [
					$request->url(),
					$request->get_request_args(),
				],
				$this->pool,
			)
		);

		if ( is_wp_error( $results ) ) {
			throw new Http_Client_Exception( Response::create( $results ) );
		}

		return array_map(
			fn ( array $result ) => Response::create( $result ),
			$results,
		);
	}

	/**
	 * Call a pending request a specific index name.
	 *
	 * @param string $key The name of the pending request.
	 */
	public function as( string $key ): Pending_Request {
		$this->pool[ $key ] = $this->create_request();

		return $this->pool[ $key ];
	}

	/**
	 * Add a request to the pool with a numeric index.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments for the method.
	 * @return Pending_Request
	 */
	public function __call( string $method, array $args = [] ) {
		$request = $this->create_request()->{$method}( ...$args );

		$this->pool[] = $request;

		return $request;
	}
}
