<?php
/**
 * Factory interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Cache;

/**
 * Cache Factory
 */
interface Factory {
	/**
	 * Retrieve a cache store by name.
	 *
	 * @param string|null $name Cache store name.
	 * @return Repository
	 */
	public function store( string $name = null );
}
