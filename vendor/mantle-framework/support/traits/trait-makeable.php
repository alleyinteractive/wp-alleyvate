<?php
/**
 * Makeable trait file.
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

trait Makeable {
	/**
	 * Create a new static instance from arguments.
	 *
	 * @param mixed ...$arguments Arguments to make from.
	 * @return static
	 */
	public static function make( ...$arguments ) {
		return new static( ...$arguments );
	}
}
