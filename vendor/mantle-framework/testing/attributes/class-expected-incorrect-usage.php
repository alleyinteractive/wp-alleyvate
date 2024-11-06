<?php
/**
 * Expected_Incorrect_Usage class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Expected Incorrect Usage
 *
 * Used to mark a test as expecting a specific doing it wrong call. Supports * as a wildcard.
 */
#[Attribute]
class Expected_Incorrect_Usage {
	/**
	 * Constructor.
	 *
	 * @param string $name Name of the function, method, or class that appears in
	 *                     the first argument of the source `_doing_it_wrong()`
	 *                     call. Supports * as a wildcard.
	 */
	public function __construct( public string $name = '*' ) {}
}
