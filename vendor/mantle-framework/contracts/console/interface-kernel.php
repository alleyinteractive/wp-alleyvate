<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Console;

use Symfony\Component\Console\Tester\CommandTester;

/**
 * Console Kernel
 */
interface Kernel extends \Mantle\Contracts\Kernel {
	/**
	 * Run the console application.
	 *
	 * @param mixed      $input Console input.
	 * @param mixed|null $output Console output.
	 * @return int
	 */
	public function handle( $input, $output = null );

	/**
	 * Run the console application by command name.
	 *
	 * @param string $command Command name.
	 * @param array  $parameters Command parameters.
	 * @param mixed  $output_buffer Output buffer.
	 * @return int
	 */
	public function call( string $command, array $parameters = [], $output_buffer = null );

	/**
	 * Test a console command by name.
	 *
	 * @param string $command Command name.
	 * @param array  $parameters Command parameters.
	 */
	public function test( string $command, array $parameters = [] ): CommandTester;

	/**
	 * Register the application's commands.
	 */
	public function register_commands();

	/**
	 * Log to the console.
	 *
	 * @param string $message Message to log.
	 */
	public function log( string $message );

	/**
	 * Terminate the application.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface $input
	 * @param  int                                             $status
	 */
	public function terminate( $input, $status ): void;
}
