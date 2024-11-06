<?php
/**
 * Application interface file
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

/**
 * Console Application Contract
 */
interface Application {
	/**
	 * Run the command through the console application.
	 *
	 * @param InputInterface|null  $input Input interface.
	 * @param OutputInterface|null $output Output interface.
	 */
	public function run( InputInterface $input = null, OutputInterface $output = null ): int;

	/**
	 * Run a command through the console application by name.
	 *
	 * @param string               $command Command name.
	 * @param array                $parameters Command parameters.
	 * @param OutputInterface|null $output_buffer Output buffer.
	 */
	public function call( string $command, array $parameters = [], $output_buffer = null ): int;

	/**
	 * Test a console command by name.
	 *
	 * @param string $command Command name.
	 * @param array  $parameters Command parameters.
	 */
	public function test( string $command, array $parameters = [] ): CommandTester;

	/**
	 * Render an exception for the console.
	 *
	 * @param Throwable       $e
	 * @param OutputInterface $output
	 * @return void
	 */
	public function render_throwable( Throwable $e, OutputInterface $output );
}
