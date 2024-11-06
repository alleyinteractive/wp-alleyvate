<?php
/**
 * Handler interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Exceptions;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Error Handler Contract
 */
interface Handler {
	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 *
	 * @throws \Exception Thrown on missing logger.
	 */
	public function report( Throwable $e );

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return bool
	 */
	public function should_report( Throwable $e );

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param \Mantle\Http\Request $request
	 * @param \Throwable           $e Exception thrown.
	 * @return mixed
	 *
	 * @throws Throwable Thrown on error rendering.
	 */
	public function render( $request, Throwable $e );

	/**
	 * Render an exception for the console.
	 *
	 * @param OutputInterface $output
	 * @param Throwable       $e
	 * @return void
	 */
	public function render_for_console( OutputInterface $output, Throwable $e );
}
