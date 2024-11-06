<?php
/**
 * Interacts_With_Console trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Console\Command;
use Mantle\Testing\Test_Command;

/**
 * Allow console commands to be tested against.
 */
trait Interacts_With_Console {
	/**
	 * Create a new Test_Command instance and run it.
	 *
	 * @param string $command Command to run.
	 * @param array  $args     Arguments to pass to the command.
	 */
	public function command( string $command, array $args = [] ): Test_Command {
		return new Test_Command( $this, $this->app, $command, $args );
	}
}
