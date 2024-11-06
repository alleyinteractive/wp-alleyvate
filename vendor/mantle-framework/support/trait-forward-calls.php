<?php
/**
 * Forward_Calls trait file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use BadMethodCallException;
use Error;

/**
 * Trait to forward calls to a method in an object.
 */
trait Forward_Calls {
	/**
	 * Forward a method call to the given object.
	 *
	 * @param mixed  $object Object to use.
	 * @param string $method Method to call.
	 * @param array  $parameters Method parameters.
	 * @return mixed
	 *
	 * @throws \BadMethodCallException Thrown on method exception.
	 * @throws \Error Thrown on method exception.
	 */
	protected function forward_call_to( $object, $method, $parameters ) {
		try {
			return $object->{ $method }( ...$parameters );
		} catch ( Error | BadMethodCallException $e ) {
			$pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

			if ( ! preg_match( $pattern, $e->getMessage(), $matches ) ) {
				throw $e;
			}

			if (
				$object::class !== $matches['class']
				|| $matches['method'] !== $method
			) {
				throw $e;
			}

			static::throw_bad_method_call_exception( $method );
		}
	}

	/**
	 * Throw a bad method call exception for the given method.
	 *
	 * @param string $method Method name.
	 *
	 * @throws \BadMethodCallException Thrown on invalid method call.
	 */
	protected static function throw_bad_method_call_exception( string $method ): never {
		throw new BadMethodCallException(
			sprintf(
				'Call to undefined method %s::%s()',
				static::class,
				$method
			)
		);
	}
}
