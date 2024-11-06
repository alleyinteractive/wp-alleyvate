<?php
/**
 * Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Contracts\Events\Dispatcher;
use Mantle\Contracts\Http\Routing\Router as Router_Contract;
use Mantle\Http\Request;
use Mantle\Http\Routing\Events\Route_Matched;
use Mantle\Support\Pipeline;
use Mantle\Support\Traits\Macroable;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Response as Symfony_Response;

use function Mantle\Support\Helpers\collect;

/**
 * Mantle Router
 *
 * Allow registration of routes to the application. On 'parse_request', the
 * request will be dispatched to the router by the HTTP kernel.
 *
 * @mixin \Mantle\Http\Routing\Route_Registrar
 */
class Router implements Router_Contract {
	use Concerns\Route_Group;
	use Macroable {
		__call as macro_call;
	}

	/**
	 * Route Collection
	 */
	protected RouteCollection $routes;

	/**
	 * All of the short-hand keys for middlewares.
	 */
	protected array $middleware = [];

	/**
	 * All of the middleware groups.
	 */
	protected array $middleware_groups = [];

	/**
	 * The registered route value binders.
	 */
	protected array $binders = [];

	/**
	 * REST Route Registrar
	 */
	protected ?Rest_Route_Registrar $rest_registrar = null;

	/**
	 * Data Object Router
	 */
	protected Entity_Router $model_router;

	/**
	 * Flag or callback to determine if requests should pass through to WordPress.
	 *
	 * @var bool|callable
	 */
	protected mixed $pass_requests_to_wordpress = true;

	/**
	 * Constructor.
	 *
	 * @param Dispatcher $events Events dispatcher.
	 * @param Container  $container Container instance.
	 */
	public function __construct( protected Dispatcher $events, protected Container $container ) {
		$this->routes = new RouteCollection();
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function get( string $uri, $action = '' ) {
		return $this->add_route( [ 'GET', 'HEAD' ], $uri, $action );
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function post( string $uri, $action = '' ) {
		return $this->add_route( [ 'POST' ], $uri, $action );
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function put( string $uri, $action = '' ) {
		return $this->add_route( [ 'PUT' ], $uri, $action );
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function delete( string $uri, $action = '' ) {
		return $this->add_route( [ 'DELETE' ], $uri, $action );
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function patch( string $uri, $action = '' ) {
		return $this->add_route( [ 'PATCH' ], $uri, $action );
	}

	/**
	 * Register a OPTIONS route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function options( string $uri, $action = '' ) {
		return $this->add_route( [ 'OPTIONS' ], $uri, $action );
	}

	/**
	 * Register a route for any HTTP method.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function any( string $uri, $action = '' ): ?Route {
		return $this->add_route( [ 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS' ], $uri, $action );
	}

	/**
	 * Load the provided routes.
	 *
	 * @param  \Closure|string $routes
	 * @return void
	 */
	protected function load_routes( \Closure|string $routes ) {
		if ( $routes instanceof \Closure ) {
			$routes( $this );
		} else {
			( new Route_File_Registrar( $this ) )->register( $routes );
		}
	}

	/**
	 * Register a route.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 * @return Route|null Route instance for web routes, null for REST routes.
	 */
	public function add_route( array $methods, string $uri, $action ): ?Route {
		// Send the route to the REST Registrar if set.
		if ( isset( $this->rest_registrar ) ) {
			$this->create_rest_api_route( $methods, $uri, $action );

			return null;
		}

		$route = $this->create_route( $methods, $uri, $action );

		$this->routes->add( $route->get_name(), $route );

		return $route;
	}

	/**
	 * Create a new route instance.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 */
	protected function create_route( array $methods, string $uri, $action ): Route {
		$route = new Route( $methods, $this->prefix( $uri ), $action );

		if ( $this->has_group_stack() ) {
			$this->merge_group_attributes_into_route( $route );
		}

		$route->set_router( $this );

		return $route;
	}

	/**
	 * Create a REST API route.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 */
	protected function create_rest_api_route( array $methods, string $uri, $action ): void {
		$args = [
			'callback' => $action,
			'methods'  => $methods,
		];

		if ( $this->has_group_stack() ) {
			$args = $this->merge_with_last_group( $args );
		}

		$this->rest_registrar->register_route( $this->prefix( $uri ), $args );
	}

	/**
	 * Prefix the given URI with the last prefix.
	 *
	 * @param string $uri Uri to prefix.
	 * @return string
	 */
	protected function prefix( string $uri ) {
		return trim( trim( $this->get_last_group_prefix(), '/' ) . '/' . trim( $uri, '/' ), '/' ) ?: '/';
	}

	/**
	 * Get registered routes.
	 */
	public function get_routes(): RouteCollection {
		return $this->routes;
	}

	/**
	 * Retrieve the container/application instance.
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Dispatch a request to the registered routes.
	 *
	 * @param Request $request Request object.
	 */
	public function dispatch( Request $request ): ?Symfony_Response {
		return $this->execute_route_match(
			$this->match_route( $request ),
			$request
		);
	}

	/**
	 * Match a request to a registered route.
	 *
	 * @param Request $request Request object.
	 */
	protected function match_route( Request $request ): ?array {
		$context = ( new RequestContext() )->fromRequest( $request );

		return ( new UrlMatcher( $this->get_routes(), $context ) )->matchRequest( $request );
	}

	/**
	 * Execute a route match and retrieve the response.
	 *
	 * @param array   $match Route match.
	 * @param Request $request Request object.
	 *
	 * @throws HttpException Thrown on unknown route callback.
	 */
	protected function execute_route_match( $match, Request $request ): ?Symfony_Response {
		// Store the request parameters.
		$request->set_route_parameters( $match );
		$this->container->instance( 'request', $request );

		$route = Route::get_route_from_match( $match );

		if ( ! $route ) {
			throw new HttpException( 500, 'Unknown route method: ' . \wp_json_encode( $match ) );
		}

		// Store the route match in the request object.
		$this->container['request']->set_route( $route );

		$this->events->dispatch( new Route_Matched( $route, $request ) );

		$middleware = $this->gather_route_middleware( $route );

		$response = ( new Pipeline( $this->container ) )
			->send( $this->container['request'] )
			->through( $middleware )
			->then(
				function ( Request $request ) use ( $route ) {
					// Refresh the request object in the container with modifications from the middleware.
					$this->container['request'] = $request;

					return $route->run( $this->container );
				}
			);

		// Ensure the response is valid since the middleware can modify it after it is run through Route.
		return static::to_response( $request, $response );
	}

	/**
	 * Prepare a response for sending.
	 *
	 * @param Request $request
	 * @param mixed   $response
	 */
	public static function to_response( Request $request, mixed $response ): \Symfony\Component\HttpFoundation\Response {
		$response = Route::ensure_response( $response );

		return $response->prepare( $request );
	}

	/**
	 * Get all of the defined middleware short-hand names.
	 *
	 * @return array
	 */
	public function get_middleware() {
		return $this->middleware;
	}

	/**
	 * Register a short-hand name for a middleware.
	 *
	 * @param  string $name
	 * @param  string $class
	 */
	public function alias_middleware( string $name, string $class ): static {
		$this->middleware[ $name ] = $class;

		return $this;
	}

	/**
	 * Get all of the defined middleware groups.
	 *
	 * @return array
	 */
	public function get_middleware_groups() {
		return $this->middleware_groups;
	}

	/**
	 * Register a group of middleware.
	 *
	 * @param  string $name
	 * @param  array  $middleware
	 */
	public function middleware_group( string $name, array $middleware ): static {
		$this->middleware_groups[ $name ] = $middleware;

		return $this;
	}

	/**
	 * Add a middleware to the beginning of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string $group
	 * @param  string $middleware
	 * @return static
	 */
	public function prepend_middleware_to_group( $group, $middleware ) {
		if ( isset( $this->middleware_groups[ $group ] ) && ! in_array( $middleware, $this->middleware_groups[ $group ], true ) ) {
			array_unshift( $this->middleware_groups[ $group ], $middleware );
		}

		return $this;
	}

	/**
	 * Add a middleware to the end of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string $group
	 * @param  string $middleware
	 * @return static
	 */
	public function push_middleware_to_group( $group, $middleware ) {
		if ( ! array_key_exists( $group, $this->middleware_groups ) ) {
				$this->middleware_groups[ $group ] = [];
		}

		if ( ! in_array( $middleware, $this->middleware_groups[ $group ], true ) ) {
				$this->middleware_groups[ $group ][] = $middleware;
		}

			return $this;
	}

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @param Route $route Route instance.
	 */
	public function gather_route_middleware( Route $route ): array {
		$middleware = $route->excluded_middleware();

		// If the route has a wildcard, we will just skip the middleware gathering.
		if ( in_array( '*', $middleware, true ) ) {
			return [];
		}

		$excluded = collect( $route->excluded_middleware() )
			->map(
				fn ( $name ) => Middleware_Name_Resolver::resolve( $name, $this->middleware, $this->middleware_groups ),
			)
			->flatten()
			->values()
			->all();

		return collect( $route->middleware() )
			->map(
				fn ( $name ) => (array) Middleware_Name_Resolver::resolve( $name, $this->middleware, $this->middleware_groups ),
			)
			->flatten()
			->reject(
				function ( $name ) use ( $excluded ) {
					if ( empty( $excluded ) ) {
						return false;
					}

					if ( $name instanceof Closure ) {
						return false;
					}

					if ( in_array( $name, $excluded, true ) ) {
						return true;
					}

					$reflection = new ReflectionClass( $name );

					return collect( $excluded )->contains(
						fn ( $exclude ) => class_exists( $exclude ) && $reflection->isSubclassOf( $exclude )
					);
				}
			)
			->values()
			->all();
	}

	/**
	 * Add a new route parameter binder.
	 *
	 * @param string          $key
	 * @param string|callable $binder
	 */
	public function bind( string $key, $binder ): void {
		$this->binders[ str_replace( '-', '_', $key ) ] = Route_Binding::for_callback(
			$this->container,
			$binder
		);
	}

	/**
	 * Register a model binder for a wildcard.
	 *
	 * @param string        $key
	 * @param string        $class
	 * @param \Closure|null $callback
	 */
	public function bind_model( $key, $class, Closure $callback = null ): void {
		$this->bind( $key, Route_Binding::for_model( $this->container, $class, $callback ) );
	}

	/**
	 * Substitute Explicit Bindings
	 *
	 * @param Request $request Request object.
	 */
	public function substitute_bindings( Request $request ): void {
		foreach ( $request->get_route_parameters() as $key => $value ) {
			if ( ! isset( $this->binders[ $key ] ) ) {
				continue;
			}

			$request->set_route_parameter( $key, $this->perform_binding( $key, $value, $request ) );
		}
	}

	/**
	 * Call the binding callback for the given key.
	 *
	 * @param  string  $key Route key.
	 * @param  string  $value Value.
	 * @param  Request $request Request object.
	 * @return mixed
	 */
	protected function perform_binding( string $key, $value, Request $request ) {
		return call_user_func( $this->binders[ $key ], $value, $request );
	}

	/**
	 * Substitute the implicit Eloquent model bindings for the route.
	 *
	 * @param Request $request Request instance.
	 */
	public function substitute_implicit_bindings( Request $request ): void {
		Implicit_Route_Binding::resolve_for_route( $this->container, $request );
	}

	/**
	 * Register a REST API route
	 *
	 * @param string                $namespace Namespace for the REST API route.
	 * @param callable|string       $callback  Callback that will be invoked to register
	 *                                         routes OR a string route.
	 * @param callable|array|string $args      Callback for the route if $callback is a
	 *                                         string route OR arguments to pass to
	 *                                         the register_rest_route() call. Not used if $callback
	 *                                         is a closure.
	 */
	public function rest_api( string $namespace, callable|string $callback, callable|array|string $args = [] ) {
		$registrar = new Rest_Route_Registrar( $this, $namespace );

		if ( is_callable( $callback ) ) {
			$this->rest_registrar = $registrar;

			$callback();

			$this->rest_registrar = null;
		} else {
			if ( is_callable( $args ) ) {
				$args = [
					'callback' => $args,
				];
			}
			// Include the group attributes.
			if ( $this->has_group_stack() ) {
				$args = $this->merge_with_last_group( $args );
			}

			$registrar->register_route( $this->prefix( $callback ), $args );
		}

		return $registrar;
	}

	/**
	 * Register routing for a WordPress model.
	 *
	 * @param string $model Model class name.
	 * @param string $controller Controller class name.
	 */
	public function model( string $model, string $controller ): void {
		$this->container->make( Entity_Router::class )->add( $this, $model, $controller );
	}

	/**
	 * Dynamically handle calls into the router instance.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Parameters for the method.
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		if ( static::has_macro( $method ) ) {
			return $this->macro_call( $method, $parameters );
		}

		if ( 'middleware' === $method ) {
			return ( new Route_Registrar( $this ) )
				->attribute( $method, is_array( $parameters[0] ) ? $parameters[0] : $parameters );
		}

		return ( new Route_Registrar( $this ) )->attribute( $method, $parameters[0] );
	}

	/**
	 * Sync the routes to the URL generator.
	 */
	public function sync_routes_to_url_generator(): void {
		$this->container['url']->set_routes( $this->routes );
	}

	/**
	 * Rename a route.
	 *
	 * @param string $old_name Old route name.
	 * @param string $new_name New route name.
	 *
	 * @throws \InvalidArgumentException Thrown when attempting to rename a route
	 *                                  a name that is already taken.
	 */
	public function rename_route( string $old_name, string $new_name ): static {
		$old = $this->routes->get( $old_name );

		if ( ! $old ) {
			return $this;
		}

		$new = $this->routes->get( $new_name );

		if ( $new ) {
			throw new InvalidArgumentException( "Unable to rename route, name already taken. [{$old_name} => {$new_name}]" );
		}

		$this->routes->add( $new_name, $old );
		$this->routes->remove( $old_name );

		return $this;
	}

	/**
	 * Determine if the request should pass through to WordPress.
	 *
	 * @param (callable(\Mantle\Http\Request): bool)|bool $callback Callback to determine if the request should pass through to WordPress.
	 */
	public function pass_requests_to_wordpress( $callback ): static {
		$this->pass_requests_to_wordpress = $callback;

		return $this;
	}

	/**
	 * Determine if the request should pass through to WordPress.
	 *
	 * @param Request $request Request object.
	 */
	public function should_pass_through_request( Request $request ): bool {
		// Early checks to always allow the REST API and prevent routing when not using themes.
		if ( str_starts_with( $request->path(), 'wp-json' ) ) {
			return true;
		}

		if ( ! wp_using_themes() ) {
			return true;
		}

		$status = $this->pass_requests_to_wordpress;

		return is_callable( $status ) ? (bool) $status( $request ) : $status;
	}
}
