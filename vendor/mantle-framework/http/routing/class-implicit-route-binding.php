<?php
/**
 * Implicit_Route_Binding class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Mantle\Contracts\Container;
use Mantle\Contracts\Http\Routing\Url_Routable;
use Mantle\Database\Model\Model_Not_Found_Exception;
use Mantle\Http\Request;
use Mantle\Support\Reflector;
use Mantle\Support\Str;

/**
 * Implicit Route Binding
 *
 * Allow models to be auto-resolved without any additional binding needed.
 */
class Implicit_Route_Binding {

	/**
	 * Resolve the implicit route bindings for the given route.
	 *
	 * @param Container $container Container instance.
	 * @param Request   $request Request instance.
	 *
	 * @throws Model_Not_Found_Exception Thrown on missing model.
	 */
	public static function resolve_for_route( Container $container, Request $request ): void {
		$route      = $request->get_route();
		$parameters = $request->get_route_parameters()->all();

		foreach ( $route->get_signature_parameters( Url_Routable::class ) as $parameter ) {
			$parameter_name = static::get_parameter_name( $parameter->getName(), $parameters );
			if ( ! $parameter_name ) {
				continue;
			}

			$parameter_value = $parameters[ $parameter_name ];

			if ( $parameter_value instanceof Url_Routable ) {
				continue;
			}

			$instance = $container->make( Reflector::get_parameter_class_name( $parameter ) );
			$model    = $instance->resolve_route_binding( $parameter_value );

			if ( ! $model ) {
				throw new Model_Not_Found_Exception( $instance::class, [ $parameter_value ] );
			}

			$request->set_route_parameter( $parameter_name, $model );
		}
	}

	/**
	 * Return the parameter name if it exists in the given parameters.
	 *
	 * @param  string $name
	 * @param  array  $parameters
	 * @return string|null
	 */
	protected static function get_parameter_name( $name, array $parameters ) {
		if ( array_key_exists( $name, $parameters ) ) {
			return $name;
		}

		$snaked_name = Str::snake( $name );

		if ( array_key_exists( $snaked_name, $parameters ) ) {
			return $snaked_name;
		}

		return null;
	}
}
