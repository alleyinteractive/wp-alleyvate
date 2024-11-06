<?php
/**
 * Unit_Test_Case class file
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use PHPUnit\Framework\TestCase as Testing_Test_Case;

/**
 * Unit Test Case.
 *
 * Sets some required defaults for us, such as not preserving global state,
 * and running each class in a separate process. This is required to not
 * have global state mixed with Integration test global state.
 */
abstract class Unit_Test_Case extends Testing_Test_Case {
	/**
	 * We want to run our unit tests in isolation, allowing us to separate them
	 * from the WordPress installation cluttered global state.
	 *
	 * @param array ...$args The array of arguments passed to the class.
	 */
	public function __construct( ...$args ) {
		parent::__construct( ...$args ); // @phpstan-ignore-line expects string|null

		// Discard all of the WordPress global state.
		$this->setPreserveGlobalState( false );

		// Load a new process to allow us to redefine functions.
		$this->setRunClassInSeparateProcess( true );
	}
}
