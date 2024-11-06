<?php
/**
 * Integration_Test_Case class file
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use Mantle\Testkit\Concerns\Installs_WordPress;
use Mantle\Testkit\Test_Case as Testing_Test_Case;

/**
 * Testkit Integration Test Case
 *
 * For use in integration tests. Will install WordPress during Test Class
 * set up process.
 */
abstract class Integration_Test_Case extends Testing_Test_Case {
	use Installs_WordPress;
}
