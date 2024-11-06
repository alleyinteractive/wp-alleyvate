<?php
/**
 * WP_UnitTestCase class file.
 *
 * To prevent this file from being loaded, set the environment variable
 * DISABLE_WP_UNIT_TEST_CASE_SHIM to true.
 *
 * @package Mantle
 */

use Mantle\Testing\Concerns\Core_Shim;
use Mantle\Testkit\Test_Case;

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	/**
	 * WP_UnitTestCase class file.
	 *
	 * Acts as a extension of the TestKit TestCase to make it easier to switch to
	 * the Mantle Testing Framework.
	 */
	class WP_UnitTestCase extends Test_Case {
		use Core_Shim;
	}
}
