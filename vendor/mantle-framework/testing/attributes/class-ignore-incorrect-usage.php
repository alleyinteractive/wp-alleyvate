<?php
/**
 * Ignore_Incorrect_Usage class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Ignore Incorrect Usage
 *
 * Used to mark a test as ignoring a specific doing it wrong call. Supports * as a wildcard.
 */
#[Attribute]
class Ignore_Incorrect_Usage {
	/**
	 * Constructor.
	 *
	 * @param string $name Name of the function, method, or class that appears in
	 *                     the first argument of the source `_doing_it_wrong()`
	 *                     call. Supports * as a wildcard.
	 */
	public function __construct( public string $name = '*' ) {}
}
