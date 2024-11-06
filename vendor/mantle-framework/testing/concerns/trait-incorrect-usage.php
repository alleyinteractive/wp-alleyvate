<?php
/**
 * This file contains the Incorrect_Usage Trait
 *
 * @package Mantle
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

namespace Mantle\Testing\Concerns;

use Mantle\Support\Str;
use Mantle\Testing\Attributes\Expected_Incorrect_Usage;
use Mantle\Testing\Attributes\Ignore_Incorrect_Usage;

use function Mantle\Support\Helpers\collect;

/**
 * Check for _doing_it_wrong() calls during testing.
 *
 * If a _doing_it_wrong() call is made, the test will fail unless it is marked
 * as expected or ignored.
 */
trait Incorrect_Usage {
	use Output_Messages;
	use Reads_Annotations;

	/**
	 * Expected "doing it wrong" calls.
	 *
	 * @var string[]
	 */
	private $expected_doing_it_wrong = [];

	/**
	 * Ignored "doing it wrong" calls.
	 *
	 * @var string[]
	 */
	private $ignored_doing_it_wrong = [];

	/**
	 * Caught "doing it wrong" calls.
	 *
	 * @var array
	 */
	private $caught_doing_it_wrong = [];

	/**
	 * Trace storage for "doing it wrong" calls.
	 *
	 * @var array
	 */
	private $caught_doing_it_wrong_traces = [];

	/**
	 * Sets up the expectations for testing a deprecated call.
	 */
	public function incorrect_usage_set_up(): void {
		$annotations = $this->get_annotations_for_method();

		foreach ( [ 'class', 'method' ] as $depth ) {
			if ( ! empty( $annotations[ $depth ]['expectedIncorrectUsage'] ) ) {
				$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $annotations[ $depth ]['expectedIncorrectUsage'] );
			}
		}

		add_action( 'doing_it_wrong_run', [ $this, 'doing_it_wrong_run' ] ); // @phpstan-ignore-line Action callback returns false
		add_action( 'doing_it_wrong_trigger_error', '__return_false' ); // @phpstan-ignore-line Action callback returns false

		// Allow attributes to define the expected and ignored incorrect usages.
		foreach ( $this->get_attributes_for_method( Expected_Incorrect_Usage::class ) as $attribute ) {
			$this->setExpectedIncorrectUsage( $attribute->newInstance()->name );
		}

		foreach ( $this->get_attributes_for_method( Ignore_Incorrect_Usage::class ) as $attribute ) {
			$this->ignoreIncorrectUsage( $attribute->newInstance()->name );
		}
	}

	/**
	 * Set up handling a _doing_it_wrong() call.
	 */
	public function incorrect_usage_tear_down(): void {
		$errors = [];

		$not_caught_doing_it_wrong = array_diff( $this->expected_doing_it_wrong, $this->caught_doing_it_wrong );
		foreach ( $not_caught_doing_it_wrong as $not_caught ) {
			$errors[] = "Failed to assert that $not_caught triggered an incorrect usage notice";
		}

		$unexpected_doing_it_wrong = collect( $this->caught_doing_it_wrong )
			->filter(
				function ( string $caught ): bool {
					$ignored_and_expected = array_merge( $this->expected_doing_it_wrong, $this->ignored_doing_it_wrong );

					if ( in_array( $caught, $ignored_and_expected, true ) ) {
						return false;
					}

					// Allow partial matches when ignoring a _doing_it_wrong() call.
					foreach ( $this->ignored_doing_it_wrong as $ignored ) {
						if ( Str::is( $ignored, $caught ) ) {
							return false;
						}
					}

					return true;
				}
			)
			->all();

		foreach ( $unexpected_doing_it_wrong as $index => $unexpected ) {
			$errors[] = $unexpected;

			if ( ! empty( $this->caught_doing_it_wrong_traces[ $index ] ) ) {
				static::trace(
					message: "Unexpected incorrect usage notice for $unexpected",
					trace: $this->caught_doing_it_wrong_traces[ $index ],
				);
			}
		}

		// Perform an assertion, but only if there are expected or unexpected
		// deprecated calls or wrongdoings.
		if (
			! empty( $this->expected_doing_it_wrong ) || ! empty( $this->caught_doing_it_wrong )
		) {
			if ( ! empty( $errors ) ) {
				$this->fail( 'Unexpected incorrect usage notice(s) triggered: ' . implode( ', ', $errors ) );
			} else {
				$this->assertTrue( true );
			}
		}
	}

	/**
	 * Declare an expected `_doing_it_wrong()` call from within a test.
	 *
	 * Note: If a `_doing_it_wrong()` call isn't made within the test, the test
	 * will fail. To ignore a `_doing_it_wrong()` call, use
	 * {@see Incorrect_Usage::ignoreIncorrectUsage()}.
	 *
	 * @param string $doing_it_wrong Name of the function, method, or class that
	 *                               appears in the first argument of the source
	 *                               `_doing_it_wrong()` call.
	 */
	public function setExpectedIncorrectUsage( $doing_it_wrong ): void {
		$this->expected_doing_it_wrong[] = $doing_it_wrong;
	}

	/**
	 * Ignore a `_doing_it_wrong()` call from within a test.
	 *
	 * Supports partial matches using `Str::is()` syntax with * as a wildcard.
	 *
	 * @param string $doing_it_wrong Name of the function, method, or class that
	 *                               appears in the first argument of the source
	 *                               `_doing_it_wrong()` call. Supports * as a
	 *                               wildcard.
	 */
	public function ignoreIncorrectUsage( $doing_it_wrong = '*' ): void {
		$this->ignored_doing_it_wrong[] = $doing_it_wrong;
	}

	/**
	 * Adds a function called in a wrong way to the list of `_doing_it_wrong()` calls.
	 *
	 * @param string $function The function to add.
	 */
	public function doing_it_wrong_run( $function ): void {
		if ( ! in_array( $function, $this->caught_doing_it_wrong, true ) ) {
			$this->caught_doing_it_wrong[] = $function;

			$this->caught_doing_it_wrong_traces[] = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		}
	}
}
