<?php
/**
 * Singleton trait file
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

/**
 * Make a class into a singleton.
 */
trait Singleton {
	/**
	 * Existing instances.
	 *
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * Get class instance.
	 *
	 * @return static
	 */
	public static function instance() {
		$class = static::class;

		if ( ! isset( static::$instances[ $class ] ) ) {
			static::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}
}
