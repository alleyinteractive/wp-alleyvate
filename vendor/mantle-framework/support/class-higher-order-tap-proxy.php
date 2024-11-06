<?php
/**
 * This file contains the Higher_Order_Tap_Proxy class
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Tap proxy.
 */
class Higher_Order_Tap_Proxy {
	/**
	 * Create a new tap proxy instance.
	 *
	 * @param mixed $target The target being tapped.
	 */
	public function __construct( public mixed $target ) {}

	/**
	 * Dynamically pass method calls to the target.
	 *
	 * @param string $method     Method to call.
	 * @param array  $parameters Params to provide to the method.
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		$this->target->{$method}( ...$parameters );

		return $this->target;
	}
}
