<?php
/**
 * Higher_Order_When_Proxy class file
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Higher Order When Proxy
 *
 * Allow a higher-order proxy that can be used conditionally.
 */
class Higher_Order_When_Proxy {

	/**
	 * Create a new proxy instance.
	 *
	 * @param  mixed $target The target being conditionally operated on.
	 * @param  bool  $condition The condition for proxying.
	 * @return void
	 */
	public function __construct( protected mixed $target, protected bool $condition ) {}

	/**
	 * Proxy accessing an attribute onto the target.
	 *
	 * @param  string $key The attribute key.
	 */
	public function __get( string $key ): mixed {
		return $this->condition
			? $this->target->{$key}
			: $this->target;
	}

	/**
	 * Proxy a method call on the target.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 */
	public function __call( string $method, array $parameters ): mixed {
		return $this->condition
			? $this->target->{$method}( ...$parameters )
			: $this->target;
	}
}
