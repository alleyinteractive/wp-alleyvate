<?php
/**
 * Interacts_With_Hooks trait file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Expectation\Expectation;
use Mantle\Testing\Expectation\Expectation_Container;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Assertions and interactions with WordPress hooks.
 */
trait Interacts_With_Hooks {
	/**
	 * Storage of the hooks that have been fired.
	 *
	 * @var array<string, int>
	 */
	protected array $hooks_fired = [];

	/**
	 * Expectation Container
	 */
	protected ?Expectation_Container $expectation_container;

	/**
	 * Setup the trait listener.
	 */
	public function interacts_with_hooks_set_up(): void {
		$this->expectation_container = new Expectation_Container();

		\add_filter(
			'all',
			function ( $value ) {
				$filter = current_filter();

				if ( ! isset( $this->hooks_fired[ $filter ] ) ) {
					$this->hooks_fired[ $filter ] = 0;
				}

				$this->hooks_fired[ $filter ]++;

				return $value;
			}
		);
	}

	/**
	 * Tear down the trait.
	 */
	public function interacts_with_hooks_tear_down(): void {
		$this->hooks_fired = [];

		if ( isset( $this->expectation_container ) ) {
			$this->expectation_container->tear_down();
			$this->expectation_container = null;
		}
	}

	/**
	 * Assert if a hook (action/filter) was applied.
	 *
	 * @param string $hook Hook to check against.
	 * @param int    $count Count to compare.
	 */
	public function assertHookApplied( string $hook, int $count = null ): void {
		PHPUnit::assertNotEmpty(
			$this->hooks_fired[ $hook ] ?? [],
			"Asserted that [{$hook}] was not fired."
		);

		if ( $count ) {
			$times_fired = $this->hooks_fired[ $hook ] ?? 0;

			PHPUnit::assertEquals(
				$count,
				$times_fired,
				sprintf(
					'Asserted that [%s] was applied %d %s when only applied %d %s.',
					$hook,
					$count,
					1 === $count ? 'time' : 'times',
					$times_fired,
					1 === $times_fired ? 'time' : 'times',
				),
			);
		}
	}

	/**
	 * Assert if a hook (action/filter) was not applied.
	 *
	 * @param string $hook Hook to check against.
	 */
	public function assertHookNotApplied( string $hook ): void {
		PHPUnit::assertEquals(
			0,
			$this->hooks_fired[ $hook ] ?? 0,
			"Asserted that [{$hook}] was fired."
		);
	}

	/**
	 * Add expectation that an action applied.
	 *
	 * @param string $hook Action to listen to.
	 */
	public function expectApplied( string $hook ): Expectation {
		return $this->expectation_container->add_applied( $hook );
	}

	/**
	 * Add expectation that an action added.
	 *
	 * @param string   $hook Action to listen to.
	 * @param callable $callback Callback to check was added, optional.
	 * @return Expectation
	 */
	public function expectAdded( string $hook, callable $callback = null ) {
		return $this->expectation_container->add_added( $hook, $callback );
	}
}
