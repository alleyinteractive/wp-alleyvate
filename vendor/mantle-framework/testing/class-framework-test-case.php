<?php
/**
 * Framework_Test_Case class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Testing\Concerns\Create_Application;

/**
 * Test case for use inside of the framework. For external use, please use
 * {@see Mantle\Testkit\Test_Case}.
 */
abstract class Framework_Test_Case extends Test_Case {
	use Create_Application;
}
