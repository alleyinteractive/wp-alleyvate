<?php
/**
 * Expectation_Container class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Expectation;

use Mantle\Support\Collection;

/**
 * Container for the expectation checking.
 */
class Expectation_Container {
	/**
	 * Name for adding an action.
	 *
	 * @var string
	 */
	public const ACTION_ADDED = 'added';

	/**
	 * Name for applying an action.
	 *
	 * @var string
	 */
	public const ACTION_APPLIED = 'applied';

	/**
	 * Expectations
	 *
	 * @var Collection<Expectation>
	 */
	protected Collection $expectations;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->expectations = new Collection();
	}

	/**
	 * Create an expectation for checking if a hook was fired.
	 *
	 * @param string $hook Hook to check.
	 * @param array  ...$args Arguments for the hook, optional.
	 */
	public function add_applied( string $hook, ...$args ): Expectation {
		$expectation = new Expectation( static::ACTION_APPLIED, $hook, $args );
		$this->expectations->push( $expectation );
		return $expectation;
	}

	/**
	 * Create an expectation for checking if a hook was added.
	 *
	 * @param string   $hook Hook to check.
	 * @param callable $callback Callback for the hook, optional.
	 */
	public function add_added( string $hook, callable $callback = null ): Expectation {
		$expectation = new Expectation( static::ACTION_ADDED, $hook, $callback );
		$this->expectations->push( $expectation );
		return $expectation;
	}

	/**
	 * Validate the expectations in the container.
	 */
	public function tear_down(): void {
		$this->expectations->each(
			fn ( Expectation $expectation ) => $expectation->validate(),
		);
	}
}
