<?php
/**
 * Bound_Method class file.
 *
 * @package Mantle
 */

namespace Mantle\Container;

use Closure;
use InvalidArgumentException;
use Mantle\Support\Reflector;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Container Bound Method
 */
class Bound_Method {

	/**
	 * Call the given Closure / class@method and inject its dependencies.
	 *
	 * @param  Container       $container Container instance.
	 * @param  callable|string $callback Callback.
	 * @param  array           $parameters Parameters.
	 * @param  string|null     $default_method Default method.
	 * @return mixed
	 *
	 * @throws \ReflectionException Throw on invalid arguments.
	 * @throws \InvalidArgumentException Throw on invalid arguments.
	 */
	public static function call( $container, $callback, array $parameters = [], $default_method = null ) {
		if ( static::is_callable_with_at_sign( $callback ) || $default_method ) {
			return static::call_class( $container, $callback, $parameters, $default_method );
		}

		return static::call_bound_method(
			$container,
			$callback,
			fn () => $callback( ...array_values( static::get_method_dependencies( $container, $callback, $parameters ) ) )
		);
	}

	/**
	 * Call a string reference to a class using Class@method syntax.
	 *
	 * @param  Container   $container Container instance.
	 * @param  string      $target Target to call.
	 * @param  array       $parameters Parameters for the class.
	 * @param  string|null $default_method Default method to call.
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException Throw on invalid arguments.
	 */
	protected static function call_class( $container, $target, array $parameters = [], $default_method = null ) {
			$segments = explode( '@', $target );

			// We will assume an @ sign is used to delimit the class name from the method
			// name. We will split on this @ sign and then build a callable array that
			// we can pass right back into the "call" method for dependency binding.
			$method = count( $segments ) === 2
											? $segments[1] : $default_method;

		if ( is_null( $method ) ) {
				throw new \InvalidArgumentException( 'Method not provided.' );
		}

			return static::call(
				$container,
				[ $container->make( $segments[0] ), $method ],
				$parameters
			);
	}

	/**
	 * Call a method that has been bound to the container.
	 *
	 * @param  Container $container Container instance.
	 * @param  callable  $callback Callback function.
	 * @param  mixed     $default Default value.
	 * @return mixed
	 */
	protected static function call_bound_method( $container, $callback, $default ) {
		if ( ! is_array( $callback ) ) {
			return Util::unwrap_if_closure( $default );
		}

		// Here we need to turn the array callable into a Class@method string we can use to
		// examine the container and see if there are any method bindings for this given
		// method. If there are, we can call this method binding callback immediately.
		$method = static::normalize_method( $callback );

		if ( $container->has_method_binding( $method ) ) {
			return $container->call_method_binding( $method, $callback[0] );
		}

		return Util::unwrap_if_closure( $default );
	}

	/**
	 * Normalize the given callback into a Class@method string.
	 *
	 * @param callable $callback Callback function.
	 * @return string
	 */
	protected static function normalize_method( $callback ) {
		$class = is_string( $callback[0] ) ? $callback[0] : $callback[0]::class;
		return "{$class}@{$callback[1]}";
	}

	/**
	 * Get all dependencies for a given method.
	 *
	 * @param  Container       $container Container instance.
	 * @param  callable|string $callback Callback function.
	 * @param  array           $parameters Parameters to pass.
	 * @return array
	 *
	 * @throws \ReflectionException Throw on invalid arguments.
	 */
	protected static function get_method_dependencies( $container, $callback, array $parameters = [] ) {
		$dependencies = [];

		foreach ( static::get_call_reflector( $callback )->getParameters() as $parameter ) {
			static::add_dependency_for_call_parameter( $container, $parameter, $parameters, $dependencies );
		}

		return array_merge( $dependencies, $parameters );
	}

	/**
	 * Get the proper reflection instance for the given callback.
	 *
	 * @param  callable|string $callback Callback to get from.
	 * @return \ReflectionFunctionAbstract
	 *
	 * @throws \ReflectionException Throw on invalid arguments.
	 */
	protected static function get_call_reflector( $callback ) {
		if ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
			$callback = explode( '::', $callback );
		} elseif ( is_object( $callback ) && ! $callback instanceof Closure ) {
			$callback = [ $callback, '__invoke' ];
		}

		return is_array( $callback )
			? new ReflectionMethod( $callback[0], $callback[1] )
			: new ReflectionFunction( $callback );
	}

	/**
	 * Get the dependency for the given call parameter.
	 *
	 * @param  Container            $container Container instance.
	 * @param  \ReflectionParameter $parameter Reflect Parameter.
	 * @param  array                $parameters Parameters to pass.
	 * @param  array                $dependencies Class dependencies.
	 * @return void
	 *
	 * @throws Binding_Resolution_Exception Thrown for invalid binding resolution.
	 */
	protected static function add_dependency_for_call_parameter(
		$container,
		$parameter,
		array &$parameters,
		&$dependencies
	) {
		if ( array_key_exists( $parameter->name, $parameters ) ) {
			$dependencies[] = $parameters[ $parameter->name ];

			unset( $parameters[ $parameter->name ] );
		} elseif ( ! is_null( $class_name = Reflector::get_parameter_class_name( $parameter ) ) ) {
			if ( array_key_exists( $class_name, $parameters ) ) {
					$dependencies[] = $parameters[ $class_name ];

					unset( $parameters[ $class_name ] );
			} else {
					$dependencies[] = $container->make( $class_name );
			}
		} elseif ( $parameter->isDefaultValueAvailable() ) {
			$dependencies[] = $parameter->getDefaultValue();
		} elseif ( ! $parameter->isOptional() && ! array_key_exists( $parameter->name, $parameters ) ) {
			$message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";

			throw new Binding_Resolution_Exception( $message );
		}
	}

	/**
	 * Determine if the given string is in Class@method syntax.
	 *
	 * @param  mixed $callback Callback.
	 */
	protected static function is_callable_with_at_sign( $callback ): bool {
		return is_string( $callback ) && strpos( $callback, '@' ) !== false;
	}
}
