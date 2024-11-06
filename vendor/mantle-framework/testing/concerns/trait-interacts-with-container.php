<?php
/**
 * Interacts_With_Container trait file.
 *
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Closure;
use Mockery;

/**
 * Concern for interacting with the container for helpful testing.
 */
trait Interacts_With_Container {

	/**
	 * Register an instance of an object in the container.
	 *
	 * @param  string $abstract Abstract to swap.
	 * @param  object $instance Instance to use.
	 * @return object
	 */
	protected function swap( $abstract, $instance ) {
		return $this->instance( $abstract, $instance );
	}

	/**
	 * Register an instance of an object in the container.
	 *
	 * @param  string $abstract Abstract to swap.
	 * @param  object $instance Instance to use.
	 * @return object
	 */
	protected function instance( $abstract, $instance ) {
		$this->app->instance( $abstract, $instance );

		return $instance;
	}

	/**
	 * Mock an instance of an object in the container.
	 *
	 * @param  string        $abstract Abstract to swap.
	 * @param  \Closure|null $mock Mock to use.
	 * @return \Mockery\MockInterface
	 */
	protected function mock( $abstract, Closure $mock = null ) {
		return $this->instance( $abstract, Mockery::mock( ...array_filter( func_get_args() ) ) );
	}

	/**
	 * Mock a partial instance of an object in the container.
	 *
	 * @param  string        $abstract Abstract to swap.
	 * @param  \Closure|null $mock Mock to use.
	 * @return \Mockery\MockInterface
	 */
	protected function partial_mock( $abstract, Closure $mock = null ) {
		return $this->instance( $abstract, Mockery::mock( ...array_filter( func_get_args() ) )->makePartial() );
	}

	/**
	 * Spy an instance of an object in the container.
	 *
	 * @param  string        $abstract Abstract to swap.
	 * @param  \Closure|null $mock Mock to use.
	 * @return \Mockery\MockInterface
	 */
	protected function spy( $abstract, Closure $mock = null ) {
		return $this->instance( $abstract, Mockery::spy( ...array_filter( func_get_args() ) ) );
	}
}
