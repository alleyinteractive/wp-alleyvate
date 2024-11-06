<?php
/**
 * Cache_Middleware class
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Closure;
use DateTimeInterface;

/**
 * Cache Middleware for Http Client.
 *
 * Allows for simple caching of HTTP requests.
 */
class Cache_Middleware {
	/**
	 * Cache group.
	 */
	public const CACHE_GROUP = 'httpclient';

	/**
	 * Constructor.
	 *
	 * @param int|DateTimeInterface|callable $ttl Time to live for the cache.
	 */
	public function __construct( protected mixed $ttl ) {}

	/**
	 * Invoke the middleware.
	 *
	 * @param Pending_Request $request Request to process.
	 * @param Closure         $next Next middleware in the stack.
	 * @return Response Response from the request.
	 */
	public function __invoke( Pending_Request $request, Closure $next ): Response {
		$cache_key = $this->get_cache_key( $request );
		$cache     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( $cache && $cache instanceof Response ) {
			return $cache;
		}

		$response = $next( $request );

		wp_cache_set( $cache_key, $response, self::CACHE_GROUP, $this->calculate_ttl( $request ) ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined

		return $response;
	}

	/**
	 * Purge the cache for a request.
	 *
	 * @param Pending_Request $request Request to purge the cache for.
	 */
	public function purge( Pending_Request $request ): bool {
		return wp_cache_delete( $this->get_cache_key( $request ), self::CACHE_GROUP );
	}

	/**
	 * Retrieve the cache key for the request.
	 *
	 * @param Pending_Request $request Request to retrieve the cache key for.
	 */
	protected function get_cache_key( Pending_Request $request ): string {
		return md5( json_encode( [ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$request->base_url(),
			$request->url(),
			$request->method(),
			$request->body(),
			$request->headers(),
		] ) );
	}

	/**
	 * Calculate the time to live for the cache in seconds.
	 *
	 * @param Pending_Request $request Request to calculate the TTL for.
	 */
	protected function calculate_ttl( Pending_Request $request ): int {
		if ( is_int( $this->ttl ) ) {
			return $this->ttl;
		}

		if ( $this->ttl instanceof DateTimeInterface ) {
			return $this->ttl->getTimestamp() - time();
		}

		$callback = $this->ttl;

		return (int) $callback( $request );
	}
}
