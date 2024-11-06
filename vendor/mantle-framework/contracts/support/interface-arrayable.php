<?php
/**
 * Arrayable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Support;

/**
 * Arrayable interface.
 *
 * @template TKey of array-key
 * @template TValue
 */
interface Arrayable {
	/**
	 * Get the instance as an array.
	 *
	 * @return array<TKey, TValue>
	 */
	public function to_array();
}
