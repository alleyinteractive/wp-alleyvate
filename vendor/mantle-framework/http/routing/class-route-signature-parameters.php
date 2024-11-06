<?php
/**
 * Route_Signature_Parameters class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Mantle\Support\Reflector;
use Mantle\Support\Str;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Route Signature Parameters
 *
 * Extract route action parameters for binding resolution.
 */
class Route_Signature_Parameters {

	/**
	 * Extract the route action's signature parameters.
	 *
	 * @param  array       $action Route action.
	 * @param  string|null $sub_class Route subclass to compare against.
	 * @return array
	 *
	 * @throws HttpException Thrown on missing callback.
	 */
	public static function from_action( array $action, $sub_class = null ) {
		if ( ! isset( $action['callback'] ) ) {
			throw new HttpException( 500, 'Unknown route callback.' );
		}

		if ( is_array( $action['callback'] ) ) {
			$class      = new ReflectionClass( $action['callback'][0] );
			$parameters = $class->getMethod( $action['callback'][1] )->getParameters();
		} else {
			$parameters = is_string( $action['callback'] )
				? static::from_class_method_string( $action['callback'] )
				: ( new ReflectionFunction( $action['callback'] ) )->getParameters();
		}

		return is_null( $sub_class ) ? $parameters : array_filter(
			$parameters,
			fn ( $p ) => Reflector::is_parameter_subclass_of( $p, $sub_class ),
		);
	}

	/**
	 * Get the parameters for the given class / method by string.
	 *
	 * @param  string $uses Route callback.
	 */
	protected static function from_class_method_string( $uses ): array {
		[ $class, $method ] = Str::parse_callback( $uses );

		// Use the invoke method if it found.
		if ( empty( $method ) && class_exists( $class ) && method_exists( $class, '__invoke' ) ) {
			$method = '__invoke';
		}

		if ( ! method_exists( $class, $method ) && is_callable( [ $class, $method ] ) ) {
			return [];
		}

		return ( new ReflectionMethod( $class, $method ) )->getParameters();
	}
}
