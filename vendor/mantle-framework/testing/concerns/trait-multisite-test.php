<?php
/**
 * Multisite_Test trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

/**
 * Trait to ensure the request is made in multisite mode and skipped otherwise.
 */
trait Multisite_Test {
	/**
	 * Setup the trait.
	 */
	public function multisite_test_set_up(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}
	}
}
