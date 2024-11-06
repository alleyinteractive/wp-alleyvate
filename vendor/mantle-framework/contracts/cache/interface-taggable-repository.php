<?php
/**
 * Taggable_Repository interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Cache;

/**
 * Cache Tag Contract
 */
interface Taggable_Repository extends Repository {
	/**
	 * Cache tags to apply.
	 *
	 * @param string[]|string $names Cache names.
	 * @return static
	 */
	public function tags( array|string $names );
}
