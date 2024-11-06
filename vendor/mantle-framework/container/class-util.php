<?php
/**
 * Util class file.
 *
 * @package Mantle
 */

namespace Mantle\Container;

use Closure;

/**
 * Container Utilities
 */
class Util {

	/**
	 * If the given value is not an array and not null, wrap it in one.
	 *
	 * From Arr::wrap() in Illuminate\Support.
	 *
	 * @param mixed $value Value to wrap.
	 * @return array
	 */
	public static function array_wrap( $value ) {
		if ( is_null( $value ) ) {
			return [];
		}

		return is_array( $value ) ? $value : [ $value ];
	}

	/**
	 * Return the default value of the given value.
	 *
	 * From global value() helper in Illuminate\Support.
	 *
	 * @param mixed $value Value to unwrap.
	 * @return mixed
	 */
	public static function unwrap_if_closure( $value ) {
		return $value instanceof Closure ? $value() : $value;
	}
}
