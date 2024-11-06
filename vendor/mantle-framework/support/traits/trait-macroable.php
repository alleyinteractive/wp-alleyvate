<?php
/**
 * Macroable class file.
 *
 * @package Mantle
 */

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
// phpcs:ignoreFile: WordPressVIPMinimum.Variables.VariableAnalysis.StaticInsideClosure

namespace Mantle\Support\Traits;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

trait Macroable {
	/**
	 * The registered string macros.
	 *
	 * @var array
	 */
	protected static $macros = [];

	/**
	 * Register a custom macro.
	 *
	 * @param string          $name
	 * @param object|callable $macro
	 */
	public static function macro( $name, $macro ): void {
		static::$macros[ $name ] = $macro;
	}

	/**
	 * Mix another object into the class.
	 *
	 * @param object $mixin
	 * @param bool   $replace
	 *
	 *
	 * @throws \ReflectionException
	 */
	public static function mixin( $mixin, $replace = true ): void {
		$methods = ( new ReflectionClass( $mixin ) )->getMethods(
			ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
		);

		foreach ( $methods as $method ) {
			if ( $replace || ! static::has_macro( $method->name ) ) {
				$method->setAccessible( true );
				static::macro( $method->name, $method->invoke( $mixin ) );
			}
		}
	}

	/**
	 * Checks if macro is registered.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function has_macro( $name ) {
		return isset( static::$macros[ $name ] );
	}

	/**
	 * Dynamically handle calls to the class.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public static function __callStatic( $method, $parameters ) {
		if ( ! static::has_macro( $method ) ) {
			throw new BadMethodCallException( sprintf(
				'Method %s::%s does not exist.', static::class, $method
			) );
		}

		$macro = static::$macros[ $method ];

		if ( $macro instanceof Closure ) {
			return call_user_func_array( Closure::bind( $macro, null, static::class ), $parameters );
		}

		return $macro( ...$parameters );
	}

	/**
	 * Dynamically handle calls to the class.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call( $method, $parameters ) {
		if ( ! static::has_macro( $method ) ) {
			throw new BadMethodCallException( sprintf(
				'Method %s::%s does not exist.', static::class, $method
			) );
		}

		$macro = static::$macros[ $method ];

		if ( $macro instanceof Closure ) {
			return call_user_func_array( $macro->bindTo( $this, static::class ), $parameters );
		}

		return $macro( ...$parameters );
	}
}
