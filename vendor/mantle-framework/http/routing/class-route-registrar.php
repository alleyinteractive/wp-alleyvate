<?php
/**
 * Route_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Mantle\Support\Arr;

/**
 * Router Registrar
 *
 * @method \Mantle\Http\Routing\Route_Registrar as(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar domain(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar middleware(array|string|null $middleware)
 * @method \Mantle\Http\Routing\Route_Registrar name(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar namespace(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar prefix(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar where(array $where)
 */
class Route_Registrar {
	/**
	 * The attributes to pass on to the router.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * The methods to dynamically pass through to the router.
	 *
	 * @var array
	 */
	protected $passthru = [
		'get',
		'post',
		'put',
		'patch',
		'delete',
		'options',
		'any',
	];

	/**
	 * The attributes that can be set through this class.
	 *
	 * @var array
	 */
	protected $allowed_attributes = [
		'as_prefix',
		'as',
		'domain',
		'middleware',
		'name',
		'namespace',
		'prefix',
		'where',
	];

	/**
	 * The attributes that are aliased.
	 *
	 * @var array
	 */
	protected $aliases = [
		'as'   => 'as_prefix',
		'name' => 'as_prefix',
	];

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( protected ?Router $router ) {
	}

	/**
	 * Set the value for a given attribute.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return $this
	 *
	 * @throws InvalidArgumentException Thrown on unknown attribute.
	 */
	public function attribute( $key, $value ) {
		if ( ! in_array( $key, $this->allowed_attributes, true ) ) {
			throw new InvalidArgumentException( "Attribute [{$key}] does not exist." );
		}

		$this->attributes[ Arr::get( $this->aliases, $key, $key ) ] = $value;

		return $this;
	}

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  \Closure|string $callback
	 */
	public function group( $callback ): static {
		$this->router->group( $this->attributes, $callback );

		return $this;
	}

	/**
	 * Register a new route with the router.
	 *
	 * @param  string                     $method
	 * @param  string                     $uri
	 * @param  \Closure|array|string|null $action
	 * @return \Mantle\Http\Routing\Route
	 */
	protected function register_route( $method, $uri, $action = null ) {
		if ( ! is_array( $action ) ) {
			$action = array_merge( $this->attributes, $action ? [ 'callback' => $action ] : [] );
		}

		return $this->router->{$method}( $uri, $this->compile_action( $action ) );
	}

	/**
	 * Compile the action into an array including the attributes.
	 *
	 * @param  \Closure|array|string|null $action
	 * @return array
	 */
	protected function compile_action( $action ) {
		if ( is_null( $action ) ) {
			return $this->attributes;
		}

		if ( is_string( $action ) || $action instanceof Closure ) {
			$action = [ 'callback' => $action ];
		}

		return array_merge( $this->attributes, $action );
	}

	/**
	 * Pass the REST API method back to the REST API registrar.
	 *
	 * @param string         $namespace Route namespace.
	 * @param Closure|string $route Route name or callback to register more routes.
	 * @param array|Closure  $args Route arguments.
	 */
	public function rest_api( string $namespace, $route, $args = [] ): Rest_Route_Registrar {
		if ( $args instanceof Closure ) {
			$args = [
				'callback' => $args,
			];
		}

		if ( is_array( $args ) ) {
			$args = array_merge( $this->attributes, $args );
		}

		return $this->router->rest_api( $namespace, $route, $args );
	}

	/**
	 * Dynamically handle calls into the route registrar.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return \Mantle\Http\Routing\Route|$this
	 *
	 * @throws BadMethodCallException Thrown on missing method.
	 */
	public function __call( $method, $parameters ) {
		if ( in_array( $method, $this->passthru, true ) ) {
			return $this->register_route( $method, ...$parameters );
		}

		if ( in_array( $method, $this->allowed_attributes, true ) ) {
			if ( 'middleware' === $method ) {
				return $this->attribute( $method, is_array( $parameters[0] ) ? $parameters[0] : $parameters );
			}

			return $this->attribute( $method, $parameters[0] );
		}

		throw new BadMethodCallException(
			sprintf(
				'Method %s::%s does not exist.',
				static::class,
				$method
			)
		);
	}
}
