<?php
/**
 * Repository interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Config;

/**
 * Config Repository Contract
 */
interface Repository {
	/**
	 * Check if a configuration value exists.
	 *
	 * @param string $key Key to get, period-delimited.
	 */
	public function has( string $key ): bool;

	/**
	 * Retrieve a configuration value.
	 *
	 * @param string $key Configuration key to get, period-delimited.
	 * @param mixed  $default Default value, optional.
	 * @return mixed
	 */
	public function get( string $key, $default = null );

	/**
	 * Set a configuration value.
	 *
	 * @param array|string $key Key(s) to set.
	 * @param mixed        $value Value to set.
	 */
	public function set( $key, $value );

	/**
	 * Get all configuration values.
	 */
	public function all(): array;
}
