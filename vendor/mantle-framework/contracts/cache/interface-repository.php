<?php
/**
 * Repository interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Cache;

use Closure;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache Repository
 * Implements PSR-16 standard and follows PSR code naming conventions.
 *
 * @link https://www.php-fig.org/psr/psr-16/
 */
interface Repository extends CacheInterface {
	/**
	 * Retrieve a value from cache.
	 *
	 * @template TCacheValue
	 *
	 * @param string                                $key Cache key.
	 * @param TCacheValue|(\Closure(): TCacheValue) $default Default value.
	 * @return (TCacheValue is null ? mixed : TCacheValue)
	 */
	public function get( string $key, mixed $default = null ): mixed;

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @template TCacheValue
	 *
	 * @param  string                                $key
	 * @param TCacheValue|(\Closure(): TCacheValue) $default Default value.
	 * @return (TCacheValue is null ? mixed : TCacheValue)
	 */
	public function pull( string $key, mixed $default = null ): mixed;

	/**
	 * Store an item in the cache.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 */
	public function put( string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null ): bool;

	/**
	 * Store an item in the cache if the key does not exist.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 */
	public function add( string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null ): bool;

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string $key
	 * @param  int    $value
	 */
	public function increment( string $key, int $value = 1 ): int|bool;

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string $key
	 * @param  int    $value
	 */
	public function decrement( string $key, int $value = 1 ): int|bool;

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 */
	public function forever( string $key, mixed $value ): bool;

	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 *
	 * @template TCacheValue
	 *
	 * @param  string                                    $key
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 * @param  (\Closure(): TCacheValue)                 $callback
	 */
	public function remember( string $key, \DateTimeInterface|\DateInterval|int|null $ttl, Closure $callback ): mixed;

	/**
	 * Get an item from the cache, or execute the given Closure and store the result forever.
	 *
	 * @param  string   $key
	 * @param  \Closure $callback
	 */
	public function sear( string $key, Closure $callback ): mixed;

	/**
	 * Get an item from the cache, or execute the given Closure and store the result forever.
	 *
	 * @template TCacheValue
	 *
	 * @param  string                    $key
	 * @param  (\Closure(): TCacheValue) $callback
	 */
	public function remember_forever( string $key, Closure $callback ): mixed;

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string $key
	 */
	public function forget( string $key ): bool;
}
