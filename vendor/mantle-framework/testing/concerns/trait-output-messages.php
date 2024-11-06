<?php
/**
 * Output_Messages trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use ErrorException;

use function Mantle\Support\Helpers\collect;
use function Termwind\render;

/**
 * Messages for testing managed with Termwind.
 */
trait Output_Messages {
	/**
	 * Render a message to the console.
	 *
	 * @param string $prefix Prefix for the message.
	 * @param string $prefix_color Color for the prefix.
	 * @param string $message Message to render.
	 * @param string $message_color Color for the message.
	 * @param string $parent_classes Parent classes for the message.
	 * @return void
	 */
	protected static function message(
		string $prefix,
		string $prefix_color,
		string $message,
		string $message_color = 'white',
		string $parent_classes = '',
	) {
		render(
			sprintf(
				'<div class="%s">
					<div class="px-1 bg-%s text-%s">%s:</div>
					<span class="ml-1">%s</span>
				</div>',
				$parent_classes,
				$prefix_color,
				$message_color,
				$prefix,
				$message,
			)
		);
	}

	/**
	 * Output a info message to the console.
	 *
	 * @param string $message Message to output.
	 * @param string $prefix Prefix to output.
	 */
	public static function info( string $message, $prefix = 'Install' ): void {
		static::message( $prefix, 'yellow-600', $message );
	}

	/**
	 * Output a success message to the console.
	 *
	 * @param string $message Message to output.
	 * @param string $prefix Prefix to output.
	 */
	public static function success( string $message, $prefix = 'Install' ): void {
		static::message( $prefix, 'lime-600', $message );
	}

	/**
	 * Output a error message to the console.
	 *
	 * @param string $message Message to output.
	 * @param string $prefix Prefix to output.
	 */
	public static function error( string $message, $prefix = 'Install' ): void {
		static::message( $prefix, 'red-800', $message, 'red-100', 'pt-1' );
	}

	/**
	 * Display a formatted code block.
	 *
	 * @link https://github.com/nunomaduro/termwind#code
	 *
	 * @param string|string[] $code Code to display.
	 */
	public static function code( $code ): void {
		if ( is_array( $code ) ) {
			$code = implode( PHP_EOL, $code );
		}

		render( "<div class=\"my-1\"><code>{$code}</code></div>" );
	}

	/**
	 * Outputs a trace message to the PHPUnit printer.
	 *
	 * @throws ErrorException With the trace found to trigger a trace.
	 *
	 * @param string $message Message to output.
	 * @param array  $trace Trace to output.
	 */
	public static function trace( string $message, array $trace ): void {
		$frames = collect( $trace );

		// Attempt to find the trace with the '_doing_it_wrong' function call.
		$function_call_index = $frames
			->filter(
				fn ( array $item ) => in_array(
					$item['function'],
					[
						'_doing_it_wrong',
					],
					true,
				)
			)
			->keys()
			->first();

		if ( null !== $function_call_index ) {
			$frame = $frames->get( $function_call_index );
		} else {
			// Attempt to find the first trace that is not apart of the testing
			// frameworks or packages.
			$frame = $frames
				->filter(
					fn ( array $item ) => false === strpos(
						(string) $item['file'],
						'phpunit/phpunit',
					)
				)
				->first();
		}

		throw new ErrorException(
			$message,
			E_USER_ERROR,
			E_USER_ERROR,
			$frame['file'],
			$frame['line'],
		);
	}
}
