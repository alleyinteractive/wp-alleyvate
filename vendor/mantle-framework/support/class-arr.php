<?php
/**
 * Arr class file.
 *
 * @package Mantle
 *
 * phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration
 */

namespace Mantle\Support;

use ArrayAccess;
use InvalidArgumentException;
use Mantle\Support\Helpers;

/**
 * Array Helpers
 */
class Arr {

	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @param  mixed $value Value to check.
	 */
	public static function accessible( $value ): bool {
		return is_array( $value ) || $value instanceof ArrayAccess;
	}

	/**
	 * Add an element to an array using "dot" notation if it doesn't exist.
	 *
	 * @param  array  $array Array to check.
	 * @param  string $key Key to check.
	 * @param  mixed  $value Value to use.
	 */
	public static function add( array $array, string $key, $value ): array {
		if ( is_null( static::get( $array, $key ) ) ) {
			static::set( $array, $key, $value );
		}

		return $array;
	}

	/**
	 * Collapse an array of arrays into a single array.
	 *
	 * @param iterable $array Array to use.
	 * @return array
	 */
	public static function collapse( $array ) {
		$results = [];

		foreach ( $array as $values ) {
			if ( $values instanceof \Mantle\Support\Collection ) {
				$values = $values->all();
			} elseif ( ! is_array( $values ) ) {
				continue;
			}

			$results[] = $values;
		}

		return array_merge( [], ...$results );
	}

	/**
	 * Cross join the given arrays, returning all possible permutations.
	 *
	 * @param  iterable ...$arrays Arrays to join.
	 */
	public static function cross_join( ...$arrays ): array {
		$results = [ [] ];

		foreach ( $arrays as $index => $array ) {
			$append = [];

			foreach ( $results as $result ) {
				foreach ( $array as $item ) {
					$result[ $index ] = $item;

					$append[] = $result;
				}
			}

			$results = $append;
		}

		return $results;
	}

	/**
	 * Divide an array into two arrays. One with keys and the other with values.
	 *
	 * @param  array $array Array to divide.
	 */
	public static function divide( $array ): array {
		return [ array_keys( $array ), array_values( $array ) ];
	}

	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param  iterable $array Array to process.
	 * @param  string   $prepend String to prepend, optional.
	 */
	public static function dot( $array, string $prepend = '' ): array {
		$results = [];

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				$results = array_merge( $results, static::dot( $value, $prepend . $key . '.' ) );
			} else {
				$results[ $prepend . $key ] = $value;
			}
		}

		return $results;
	}

	/**
	 * Get all of the given array except for a specified array of keys.
	 *
	 * @param  array        $array Array to process.
	 * @param  array|string $keys Keys to filter by.
	 */
	public static function except( array $array, $keys ): array {
		static::forget( $array, $keys );

		return $array;
	}

	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param  \ArrayAccess|array $array Array to process.
	 * @param  string|int         $key Key to check if it exists.
	 */
	public static function exists( $array, $key ): bool {
		if ( $array instanceof ArrayAccess ) {
			return $array->offsetExists( $key );
		}

		return array_key_exists( $key, $array );
	}

	/**
	 * Return the first element in an array passing a given truth test.
	 *
	 * @param  iterable      $array Array to process.
	 * @param  callable|null $callback Callback filter on, optional.
	 * @param  mixed         $default Default value.
	 * @return mixed
	 */
	public static function first( $array, callable $callback = null, $default = null ) {
		if ( is_null( $callback ) ) {
			if ( empty( $array ) ) {
				return Helpers\value( $default );
			}

			foreach ( $array as $item ) {
				return $item;
			}
		}

		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return $value;
			}
		}

		return Helpers\value( $default );
	}

	/**
	 * Return the last element in an array passing a given truth test.
	 *
	 * @param  array         $array Array to process.
	 * @param  callable|null $callback Callback to filter by, optional.
	 * @param  mixed         $default Default value.
	 * @return mixed
	 */
	public static function last( $array, callable $callback = null, $default = null ) {
		if ( is_null( $callback ) ) {
			return empty( $array ) ? Helpers\value( $default ) : end( $array );
		}

		return static::first( array_reverse( $array, true ), $callback, $default );
	}

	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param  iterable  $array Array to process.
	 * @param  int|float $depth Depth to handle.
	 */
	public static function flatten( iterable $array, int|float $depth = INF ): array {
		$result = [];

		foreach ( $array as $item ) {
			$item = $item instanceof Collection ? $item->all() : $item;

			if ( ! is_array( $item ) ) {
				$result[] = $item;
			} else {
				$values = 1 === $depth
					? array_values( $item )
					: static::flatten( $item, $depth - 1 );

				foreach ( $values as $value ) {
					$result[] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param  array        $array Array to handle.
	 * @param  array|string $keys Keys to use.
	 */
	public static function forget( &$array, $keys ): void {
		$original = &$array;

		$keys = (array) $keys;

		if ( count( $keys ) === 0 ) {
			return;
		}

		foreach ( $keys as $key ) {
			// if the exact key exists in the top-level, remove it.
			if ( static::exists( $array, $key ) ) {
				unset( $array[ $key ] );

				continue;
			}

			$parts = explode( '.', (string) $key );

			// Clean up before each pass.
			$array = &$original;

			while ( count( $parts ) > 1 ) { // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
				$part = array_shift( $parts );

				if ( isset( $array[ $part ] ) && is_array( $array[ $part ] ) ) {
					$array = &$array[ $part ];
				} else {
					continue 2;
				}
			}

			unset( $array[ array_shift( $parts ) ] );
		}
	}

	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array $array Array to process.
	 * @param  string|int|null    $key Key to retrieve.
	 * @param  mixed              $default Default value.
	 * @return mixed
	 */
	public static function get( $array, $key, $default = null ) {
		if ( ! static::accessible( $array ) ) {
			return Helpers\value( $default );
		}

		if ( is_null( $key ) ) {
			return $array;
		}

		if ( static::exists( $array, $key ) ) {
			return $array[ $key ];
		}

		if ( strpos( $key, '.' ) === false ) {
			return $array[ $key ] ?? Helpers\value( $default );
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( static::accessible( $array ) && static::exists( $array, $segment ) ) {
				$array = $array[ $segment ];
			} else {
				return Helpers\value( $default );
			}
		}

		return $array;
	}

	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array $array Array to process.
	 * @param  string|array       $keys Key to check.
	 */
	public static function has( $array, $keys ): bool {
		$keys = (array) $keys;

		if ( ! $array || [] === $keys ) {
			return false;
		}

		foreach ( $keys as $key ) {
			$sub_key_array = $array;

			if ( static::exists( $array, $key ) ) {
				continue;
			}

			foreach ( explode( '.', (string) $key ) as $segment ) {
				if ( static::accessible( $sub_key_array ) && static::exists( $sub_key_array, $segment ) ) {
					$sub_key_array = $sub_key_array[ $segment ];
				} else {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Determine if any of the keys exist in an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array $array Array to process.
	 * @param  string|array       $keys Keys to check.
	 */
	public static function has_any( $array, $keys ): bool {
		if ( empty( $keys ) ) {
			return false;
		}

		$keys = (array) $keys;

		if ( empty( $array ) ) {
			return false;
		}

		foreach ( $keys as $key ) {
			if ( static::has( $array, $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if an array is associative.
	 *
	 * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
	 *
	 * @param  array $array Array to process.
	 */
	public static function is_assoc( array $array ): bool {
		$keys = array_keys( $array );

		return array_keys( $keys ) !== $keys;
	}

	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param  array        $array Array to process.
	 * @param  array|string $keys Keys to process by.
	 */
	public static function only( array $array, array|string $keys ): array {
		return array_intersect_key( $array, array_flip( (array) $keys ) );
	}

	/**
	 * Pluck an array of values from an array.
	 *
	 * @param  iterable          $array Array to process.
	 * @param  string|array      $value Values to pluck.
	 * @param  string|array|null $key Key to use.
	 */
	public static function pluck( $array, $value, $key = null ): array {
		$results = [];

		[ $value, $key ] = static::explode_pluck_parameters( $value, $key );

		foreach ( $array as $item ) {
			$item_value = Helpers\data_get( $item, $value );

			// If the key is "null", we will just append the value to the array and keep
			// looping. Otherwise we will key the array using the value of the key we
			// received from the developer. Then we'll return the final array form.
			if ( is_null( $key ) ) {
				$results[] = $item_value;
			} else {
				$item_key = Helpers\data_get( $item, $key );

				if ( is_object( $item_key ) && method_exists( $item_key, '__toString' ) ) {
					$item_key = (string) $item_key;
				}

				$results[ $item_key ] = $item_value;
			}
		}

		return $results;
	}

	/**
	 * Explode the "value" and "key" arguments passed to "pluck".
	 *
	 * @param  string|array      $value Value to pluck.
	 * @param  string|array|null $key Key to use.
	 * @return array
	 */
	protected static function explode_pluck_parameters( $value, $key ) {
		$value = is_string( $value ) ? explode( '.', $value ) : $value;

		$key = is_null( $key ) || is_array( $key ) ? $key : explode( '.', $key );

		return [ $value, $key ];
	}

	/**
	 * Push an item onto the beginning of an array.
	 *
	 * @param  array $array Array to process.
	 * @param  mixed $value Item value.
	 * @param  mixed $key Item key.
	 */
	public static function prepend( array $array, $value, $key = null ): array {
		if ( is_null( $key ) ) {
			array_unshift( $array, $value );
		} else {
			$array = [ $key => $value ] + $array;
		}

		return $array;
	}

	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param  array  $array Array to process.
	 * @param  string $key Key to pull by.
	 * @param  mixed  $default Default value.
	 * @return mixed
	 */
	public static function pull( array &$array, $key, $default = null ) {
		$value = static::get( $array, $key, $default );

		static::forget( $array, $key );

		return $value;
	}

	/**
	 * Get one or a specified number of random values from an array.
	 *
	 * @param  array    $array Array to process.
	 * @param  int|null $number Number to pull.
	 * @return mixed
	 *
	 * @throws InvalidArgumentException Thrown when the requested number of items is greater
	 *                                   than the length of the array.
	 */
	public static function random( array $array, $number = null ) {
		$requested = is_null( $number ) ? 1 : $number;

		$count = count( $array );

		if ( $requested > $count ) {
			throw new InvalidArgumentException(
				"You requested {$requested} items, but there are only {$count} items available."
			);
		}

		if ( is_null( $number ) ) {
			return $array[ array_rand( $array ) ];
		}

		if ( 0 === (int) $number ) {
			return [];
		}

		$keys = array_rand( $array, $number );

		$results = [];

		foreach ( (array) $keys as $key ) {
			$results[] = $array[ $key ];
		}

		return $results;
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array       $array Array to process.
	 * @param  string|null $key Key to set.
	 * @param  mixed       $value Value to set.
	 */
	public static function set( array &$array, $key, $value ): array {
		if ( is_null( $key ) ) {
			$array = $value;
			return $array;
		}

		$keys = explode( '.', $key );

		foreach ( $keys as $i => $key ) {
			if ( count( $keys ) === 1 ) {
				break;
			}

			unset( $keys[ $i ] );

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
				$array[ $key ] = [];
			}

			$array = &$array[ $key ];
		}

		$array[ array_shift( $keys ) ] = $value;

		return $array;
	}

	/**
	 * Shuffle the given array and return the result.
	 *
	 * @param  array $array Array to process.
	 * @param  int   $seed Seed to use.
	 * @return array
	 */
	public static function shuffle( $array, ?int $seed = null ) {
		if ( is_null( $seed ) ) {
			shuffle( $array );
		} else {
			mt_srand( $seed ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
			shuffle( $array );
			mt_srand(); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		}

		return $array;
	}

	/**
	 * Sort the array using the given callback or "dot" notation.
	 *
	 * @param  array                $array Array to sort.
	 * @param  callable|string|null $callback Callback to sort by.
	 * @return array
	 */
	public static function sort( $array, $callback = null ) {
		return Collection::make( $array )->sort_by( $callback )->all();
	}

	/**
	 * Recursively sort an array by keys and values.
	 *
	 * @param  array $array Array to process.
	 * @return array
	 */
	public static function sort_recursive( $array ) {
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				$value = static::sort_recursive( $value );
			}
		}

		if ( static::is_assoc( $array ) ) {
			ksort( $array );
		} else {
			sort( $array );
		}

		return $array;
	}

	/**
	 * Convert the array into a query string.
	 *
	 * @param  array $array Array to process.
	 * @return string
	 */
	public static function query( $array ) {
		return http_build_query( $array, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Filter the array using the given callback.
	 *
	 * @param  array    $array Array to process.
	 * @param  callable $callback Callback to filter by.
	 * @return array
	 */
	public static function where( $array, callable $callback ) {
		return array_filter( $array, $callback, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * If the given value is not an array and not null, wrap it in one.
	 *
	 * @param  mixed $value Value to wrap by.
	 */
	public static function wrap( $value ): array {
		if ( is_null( $value ) ) {
			return [];
		}

		return is_array( $value ) ? $value : [ $value ];
	}
}
