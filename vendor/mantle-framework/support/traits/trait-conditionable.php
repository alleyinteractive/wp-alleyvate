<?php
/**
 * Conditionable trait file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

use Closure;
use Mantle\Support\Higher_Order_When_Proxy;

/**
 * Allow a class to conditionally invoke a method fluently.
 *
 * A method can use the trait to invoke a method conditionally upon itself.
 */
trait Conditionable {
	/**
	 * Apply the callback if the given "value" is (or resolves to) truthy.
	 *
	 * @template TWhenParameter
	 * @template TWhenReturnType
	 *
	 * @param  (\Closure($this): TWhenParameter)|TWhenParameter  $value
	 * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $callback
	 * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $default
	 * @return static|TWhenReturnType
	 */
	public function when( $value, callable $callback = null, callable $default = null ) {
			$value = $value instanceof Closure ? $value( $this ) : $value;

		if ( func_num_args() === 1 ) {
				return new Higher_Order_When_Proxy( $this, $value );
		}

		if ( $value ) {
			return $callback( $this, $value ) ?? $this;
		} elseif ( $default ) {
			return $default( $this, $value ) ?? $this;
		}

		return $this;
	}

	/**
	 * Apply the callback if the given "value" is (or resolves to) falsy.
	 *
	 * @template TUnlessParameter
	 * @template TUnlessReturnType
	 *
	 * @param  (\Closure( $this): TUnlessParameter)|TUnlessParameter  $value
	 * @param  (callable( $this, TUnlessParameter): TUnlessReturnType)|null  $callback
	 * @param  (callable( $this, TUnlessParameter): TUnlessReturnType)|null  $default
	 * @return $this|TUnlessReturnType
	 */
	public function unless( $value, callable $callback = null, callable $default = null ) {
		$value = $value instanceof Closure ? $value( $this ) : $value;

		if ( func_num_args() === 1 ) {
			return new Higher_Order_When_Proxy( $this, ! $value );
		}

		if ( ! $value ) {
			return $callback( $this, $value ) ?? $this;
		} elseif ( $default ) {
			return $default( $this, $value ) ?? $this;
		}

		return $this;
	}
}
