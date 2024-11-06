<?php
/**
 * Expectation class file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Expectation;

use PHPUnit\Framework\Assert as PHPUnit;
use SebastianBergmann\Exporter\Exporter;

/**
 * Expectation for an action to be added/applied.
 */
class Expectation {
	/**
	 * Arguments for the hook.
	 *
	 * @var mixed
	 */
	protected $args;

	/**
	 * Number of times for the hook to execute.
	 */
	protected int|null $times = null;

	/**
	 * Return value comparison callback.
	 *
	 * @var callable|null
	 */
	protected $return_value_callback;

	/**
	 * Record of the start value of the hook
	 *
	 * @var array
	 */
	protected $record_start = [];

	/**
	 * Record of the return value for a hook.
	 *
	 * @var array
	 */
	protected $record_stop = [];

	/**
	 * Constructor.
	 *
	 * @param string $action Action to expect.
	 * @param string $hook Hook to listen to.
	 * @param mixed  $args Arguments for the hook.
	 */
	public function __construct( protected readonly string $action, protected readonly string $hook, $args = null ) {
		if ( ! empty( $args ) ) {
			$this->args = $args;
		}

		if ( Expectation_Container::ACTION_APPLIED === $action ) {
			$this->setup_applied_hooks();
		}
	}

	/**
	 * Setup listeners for the applied action.
	 */
	protected function setup_applied_hooks() {
		add_action( // @phpstan-ignore-line Action callback
			$this->hook,
			[ $this, 'record_start' ],
			PHP_INT_MIN,
			99
		);

		add_action( // @phpstan-ignore-line Action callback
			$this->hook,
			[ $this, 'record_stop' ],
			PHP_INT_MAX,
			99
		);
	}

	/**
	 * Record the start of a hook being applied.
	 *
	 * @param array ...$args Hook arguments.
	 * @return mixed
	 */
	public function record_start( ...$args ) {
		$this->record_start[] = $args;

		return array_shift( $args );
	}

	/**
	 * Record the stop of a hook being applied.
	 *
	 * @param array ...$args Hook arguments.
	 * @return mixed
	 */
	public function record_stop( ...$args ) {
		$this->record_stop[] = $args;

		return array_shift( $args );
	}

	/**
	 * Validate if an expectation meets its expectations.
	 */
	public function validate(): void {
		$exporter = new Exporter();

		if ( Expectation_Container::ACTION_APPLIED === $this->action ) {
			if ( null !== $this->times ) {
				$count = count( $this->record_start );

				PHPUnit::assertEquals(
					$this->times,
					$count,
					"Expected that hook [{$this->hook}] ({$count}) would be applied [{$this->times}] times."
				);
			} else {
				PHPUnit::assertTrue(
					! empty( $this->record_start ),
					"Expected that hook [{$this->hook}] would be applied at least once."
				);
			}

			// Compare the arguments for the hook.
			if ( null !== $this->args ) {
				foreach ( $this->record_start as $record ) {
					PHPUnit::assertSame(
						$this->args,
						$record,
						sprintf(
							'Failed asserting that hook [%s] argument passed %s is identical to %s.',
							$this->hook,
							$exporter->export( $record ),
							$exporter->export( $this->args )
						),
					);
				}
			}

			// Compare the return value of the hook.
			if ( isset( $this->return_value_callback ) ) {
				foreach ( $this->record_stop as $record ) {
					PHPUnit::assertTrue(
						call_user_func_array( $this->return_value_callback, $record ),
						sprintf(
							'Failed asserting that hook\'s [%s] return value %s matches the expected return value.',
							$this->hook,
							$exporter->export( $record ),
						)
					);
				}
			}

			// Remove the actions for the hook.
			remove_action( $this->hook, [ $this, 'record_start' ], PHP_INT_MIN );
			remove_action( $this->hook, [ $this, 'record_stop' ], PHP_INT_MAX );
		}

		// Asset if the action was added.
		if ( Expectation_Container::ACTION_ADDED === $this->action ) {
			PHPUnit::assertTrue(
				(bool) has_action( $this->hook, $this->args ?? false ),
				"Expected that hook [{$this->hook}] would have action added."
			);
		}
	}

	/**
	 * Assert that the action was never applied.
	 *
	 * @return static
	 */
	public function never() {
		$this->times = 0;

		return $this;
	}

	/**
	 * Assert that the action was applied once.
	 *
	 * @return static
	 */
	public function once() {
		$this->times = 1;

		return $this;
	}

	/**
	 * Assert that the action was applied twice.
	 *
	 * @return static
	 */
	public function twice() {
		$this->times = 2;

		return $this;
	}

	/**
	 * Assert that the action was applied a specific number of times.
	 *
	 * @param int $times Number of times.
	 */
	public function times( int $times ): static {
		$this->times = $times;

		return $this;
	}

	/**
	 * Specify the arguments for the expectation.
	 *
	 * @param mixed ...$args Arguments.
	 */
	public function with( ...$args ): static {
		$this->args = $args;

		return $this;
	}

	/**
	 * Remove checking the arguments for the action.
	 */
	public function withAnyArgs(): static {
		$this->args = null;

		return $this;
	}

	/**
	 * Specify that the filter returns a specific value.
	 *
	 * @param mixed ...$values Values to return.
	 */
	public function andReturn( mixed ...$values ): static {
		return $this->returnComparison(
			function ( $value ) use ( $values ) {
				foreach ( $values as $expected ) {
					if ( is_callable( $expected ) ) {
						return (bool) $expected( $value );
					}

					if ( $value === $expected ) {
						return true;
					}
				}

				return false;
			}
		);
	}

	/**
	 * Specify that the filter returns null.
	 */
	public function andReturnNull(): static {
		return $this->andReturn( null );
	}

	/**
	 * Specify that the filter returns false.
	 */
	public function andReturnFalse(): static {
		return $this->andReturn( false );
	}

	/**
	 * Specify that the filter returns true.
	 */
	public function andReturnTrue(): static {
		return $this->andReturn( true );
	}

	/**
	 * Specify that the filter returns a truthy value.
	 */
	public function andReturnTruthy(): static {
		return $this->returnComparison( fn ( $value ) => (bool) $value );
	}

	/**
	 * Specify that the filter returns a falsy value.
	 */
	public function andReturnFalsy(): static {
		return $this->returnComparison( fn ( $value ) => ! $value );
	}

	/**
	 * Specify that the filter returns a boolean value.
	 */
	public function andReturnBoolean(): static {
		return $this->andReturn( true, false );
	}

	/**
	 * Specify that the filter returns an empty value.
	 */
	public function andReturnEmpty(): static {
		return $this->returnComparison( fn ( $value ) => empty( $value ) );
	}

	/**
	 * Specify that the filter returns a non-empty value.
	 */
	public function andReturnNotEmpty(): static {
		return $this->returnComparison( fn ( $value ) => ! empty( $value ) );
	}

	/**
	 * Specify that the filter returns an array value.
	 */
	public function andReturnArray(): static {
		return $this->returnComparison( fn ( $value ) => is_array( $value ) );
	}

	/**
	 * Specify that the filter returns an instance of a class.
	 *
	 * @param string $class Class name.
	 */
	public function andReturnInstanceOf( string $class ): static {
		return $this->returnComparison( fn ( $value ) => $value instanceof $class );
	}

	/**
	 * Specify that the filter returns a string value.
	 */
	public function andReturnString(): static {
		return $this->returnComparison( fn ( $value ) => is_string( $value ) );
	}

	/**
	 * Specify that the filter returns an integer value.
	 */
	public function andReturnInteger(): static {
		return $this->returnComparison( fn ( $value ) => is_int( $value ) );
	}

	/**
	 * Specify the return comparison callback for the filter.
	 *
	 * @param callable $callback Callback.
	 */
	protected function returnComparison( callable $callback ): static {
		$this->return_value_callback = $callback;
		return $this;
	}
}
