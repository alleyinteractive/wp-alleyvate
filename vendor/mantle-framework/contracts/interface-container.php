<?php
/**
 * Container Contract
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * Container Contract
 */
interface Container extends ContainerInterface {
	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param string $abstract Abstract name.
	 * @return bool
	 */
	public function bound( $abstract );

	/**
	 * Alias a type to a different name.
	 *
	 * @param string $abstract Abstract name.
	 * @param string $alias Alias name.
	 */
	public function alias( $abstract, $alias );

	/**
	 * Register a binding with the container.
	 *
	 * @param string               $abstract Abstract name.
	 * @param \Closure|string|null $concrete Concrete to bind.
	 * @param bool                 $shared Shared flag.
	 */
	public function bind( $abstract, $concrete = null, $shared = false );

	/**
	 * Register a binding if it hasn't already been registered.
	 *
	 * @param string               $abstract Abstract name.
	 * @param \Closure|string|null $concrete Concrete to bind.
	 * @param bool                 $shared Shared flag.
	 */
	public function bind_if( $abstract, $concrete = null, $shared = false );

	/**
	 * Register a shared binding in the container.
	 *
	 * @param string               $abstract Abstract name.
	 * @param \Closure|string|null $concrete Concrete to bind.
	 */
	public function singleton( $abstract, $concrete = null );

	/**
	 * Register a shared binding if it hasn't already been registered.
	 *
	 * @param string               $abstract Abstract name.
	 * @param \Closure|string|null $concrete Concrete name.
	 */
	public function singleton_if( $abstract, $concrete = null );

	/**
	 * "Extend" an abstract type in the container.
	 *
	 * @param string   $abstract Abstract name.
	 * @param \Closure $closure Closure callback.
	 */
	public function extend( $abstract, Closure $closure );

	/**
	 * Register an existing instance as shared in the container.
	 *
	 * @param string $abstract Abstract name.
	 * @param mixed  $instance Interface instance.
	 */
	public function instance( $abstract, $instance );

	/**
	 * Get a closure to resolve the given type from the container.
	 *
	 * @param string $abstract Abstract name.
	 * @return \Closure
	 */
	public function factory( $abstract );

	/**
	 * Flush the container of all bindings and resolved instances.
	 */
	public function flush();

	/**
	 * Call the given Closure / class@method and inject its dependencies.
	 *
	 * @param  callable|string $callback
	 * @param  array           $parameters
	 * @param  string|null     $default_method
	 * @return mixed
	 */
	public function call( $callback, array $parameters = [], $default_method = null );

	/**
	 * Resolve the given type from the container.
	 *
	 * @param string $abstract Abstract name.
	 * @param array  $parameters Parameters to pass.
	 * @return mixed
	 */
	public function make( $abstract, array $parameters = [] );

	/**
	 * Determine if the given abstract type has been resolved.
	 *
	 * @param string $abstract Abstract name.
	 * @return bool
	 */
	public function resolved( $abstract );

	/**
	 * Register a new resolving callback.
	 *
	 * @param \Closure|string $abstract Abstract name.
	 * @param \Closure|null   $callback Callback.
	 */
	public function resolving( $abstract, Closure $callback = null );

	/**
	 * Register a new after resolving callback.
	 *
	 * @param \Closure|string $abstract Abstract name.
	 * @param \Closure|null   $callback Callback.
	 */
	public function after_resolving( $abstract, Closure $callback = null );
}
