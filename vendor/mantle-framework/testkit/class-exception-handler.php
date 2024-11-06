<?php
/**
 * Exception_Handler class file
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use Mantle\Contracts\Exceptions\Handler as Exceptions_Handler;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Testkit Exception Handler
 *
 * Used to display errors during testing.
 */
class Exception_Handler implements Exceptions_Handler {
	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 */
	public function report( Throwable $e ): void {
		dump( static::class . '::' . __FUNCTION__ . '()', $e );
	}

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param Throwable $e Exception thrown.
	 */
	public function should_report( Throwable $e ): bool {
		return true;
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param \Mantle\Http\Request $request
	 * @param \Throwable           $e Exception thrown.
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function render( $request, Throwable $e ) {
		return new Response();
	}

	/**
	 * Render an exception for the console.
	 *
	 * @param OutputInterface $output
	 * @param Throwable       $e
	 */
	public function render_for_console( OutputInterface $output, Throwable $e ): void {
		$output->writeln( "<error>Exception: {$e->getMessage()}</error>" );
	}
}
