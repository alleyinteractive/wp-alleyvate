<?php
/**
 * Rest_Route_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use InvalidArgumentException;
use Mantle\Http\Routing\Events\Route_Matched;
use Mantle\Support\Pipeline;
use Mantle\Support\Str;
use WP_REST_Request;

use function Mantle\Support\Helpers\add_action;
use function Mantle\Support\Helpers\collect;

/**
 * REST API Route Registrar
 */
class Rest_Route_Registrar {
	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Queued routes to register.
	 *
	 * @var array
	 */
	protected $routes;

	/**
	 * Namespace to register to.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 * @param string $namespace Namespace to register to.
	 */
	public function __construct( Router $router, string $namespace ) {
		$this->router    = $router;
		$this->namespace = $namespace;

		add_action( 'rest_api_init', [ $this, 'register_routes' ], 20 );
	}

	/**
	 * Register a REST API Route.
	 *
	 * @param string         $route Route to register.
	 * @param array|callable $args Arguments or callback for the route.
	 */
	public function register_route( string $route, $args = [] ): void {
		$args = $this->normalize_args( $args, $route );

		if ( $this->should_register_now() ) {
			register_rest_route( $this->namespace, $route, $args );
		} else {
			$this->routes[] = [ $route, $args ];
		}
	}

	/**
	 * Normalize the arguments that are registered.
	 *
	 * @param array|callable $args Arguments for the route or callback function.
	 * @param string         $route Route name.
	 */
	protected function normalize_args( $args, string $route ): array {
		if ( ! is_array( $args ) ) {
			$args = [
				'callback' => $args,
			];
		}

		// Fill in the required argument for permission callback.
		if ( empty( $args['permission_callback'] ) ) {
			$args['permission_callback'] = '__return_true';
		}

		// Ensure the callback returns a valid REST response.
		if ( isset( $args['callback'] ) ) {
			$args['callback'] = $this->wrap_callback( $args['callback'], $route );
		}

		return $args;
	}

	/**
	 * Wrap the route callback with a valid WordPress REST response.
	 *
	 * @param mixed  $callback Callback to invoke.
	 * @param string $route Route name.
	 */
	protected function wrap_callback( mixed $callback, string $route ): callable {
		$callback = $this->parse_route_action( $callback, $route );

		return function ( WP_REST_Request $request ) use ( $callback, $route ) {
			$middleware = $request->get_attributes()['middleware'] ?? [];

			if ( empty( $middleware ) ) {
				return rest_ensure_response( $callback( $request ) );
			}

			$container = $this->router->get_container();

			$container['events']->dispatch(
				new Route_Matched(
					[
						'namespace' => $this->namespace,
						'route'     => $route,
					],
					$request,
				)
			);

			return rest_ensure_response(
				( new Pipeline( $container ) )
					->send( $request )
					->through( $this->gather_route_middleware( $middleware ) )
					->then(
						fn ( WP_REST_Request $request ) => $callback( $request ),
					)
			);
		};
	}

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @param string[] $middleware Middleware for the route.
	 */
	public function gather_route_middleware( array $middleware ): array {
		return collect( $middleware )
			->map(
				fn ( $name ) => (array) Middleware_Name_Resolver::resolve(
					$name,
					$this->router->get_middleware(),
					$this->router->get_middleware_groups()
				)
			)
			->flatten()
			->values()
			->to_array();
	}

	/**
	 * Register the queued routes.
	 */
	public function register_routes(): void {
		if ( empty( $this->routes ) ) {
			return;
		}

		foreach ( $this->routes as $route ) {
			register_rest_route( $this->namespace, ...$route );
		}

		$this->routes = [];
	}

	/**
	 * Determine if the routes should be registered now because `rest_api_init`
	 * was already fired.
	 */
	protected function should_register_now(): bool {
		return function_exists( 'did_action' ) && (bool) did_action( 'rest_api_init' );
	}

	/**
	 * Parse a route action and return the callback.
	 *
	 * Supports closures, invokable classes, and class methods.
	 *
	 * @throws InvalidArgumentException If the action is not supported.
	 *
	 * @param mixed  $action Route action.
	 * @param string $route Route path.
	 */
	protected function parse_route_action( mixed $action, string $route ): callable {
		if ( is_callable( $action ) ) {
			return $action;
		}

		if ( is_string( $action ) ) {
			// Check for Controller@method callback.
			if ( Str::contains( $action, '@' ) ) {
				[ $controller, $method ] = explode( '@', $action );

				return [ $this->router->get_container()->make( $controller ), $method ];
			}

			// Check for invokable classes.
			if ( class_exists( $action ) && method_exists( $action, '__invoke' ) ) {
				return [ $this->router->get_container()->make( $action ), '__invoke' ];
			}
		}

		if ( is_array( $action ) ) {
			[ $controller, $method ] = $action;

			return [ $this->router->get_container()->make( $controller ), $method ];
		}

		throw new InvalidArgumentException( "Invalid REST API route action for [{$route}]: " . print_r( $action, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}
}
