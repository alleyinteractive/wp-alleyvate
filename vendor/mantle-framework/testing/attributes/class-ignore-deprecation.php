<?php
/**
 * Ignore_Deprecation class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Ignore Deprecation
 *
 * Used to mark a test as ignoring a specific deprecation notice. Supports * as a wildcard.
 */
#[Attribute]
class Ignore_Deprecation {
	/**
	 * Constructor.
	 *
	 * @param string $deprecation The expected deprecation to ignore. Defaults to all deprecations.
	 */
	public function __construct( public string $deprecation = '*' ) {}
}
