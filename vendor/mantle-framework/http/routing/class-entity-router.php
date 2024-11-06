<?php
/**
 * Entity_Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Http\Routing\Entity_Router as Entity_Router_Contract;
use Mantle\Contracts\Http\Routing\Url_Routable;
use Mantle\Contracts\Http\Routing\Router as Router_Contract;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Facade\Log;
use Mantle\Http\Routing\Events\Bindings_Substituted;
use Mantle\Http\Routing\Events\Entity_Route_Added;
use Mantle\Http\Routing\Events\Route_Matched;

use function Mantle\Support\Helpers\collect;

/**
 * Provide routing to a WordPress data entity: post or term.
 */
class Entity_Router implements Entity_Router_Contract {

	/**
	 * Events instance.
	 *
	 * @var \Mantle\Contracts\Events\Dispatcher
	 */
	protected $events;

	/**
	 * Constructor.
	 *
	 * @param Container $container The application container.
	 */
	public function __construct( Container $container ) {
		$this->events = $container['events'];

		$this->events->listen(
			Route_Matched::class,
			fn ( $event ) => $this->handle_route_matched( $event ),
		);

		$this->events->listen(
			Bindings_Substituted::class,
			fn ( $event ) => $this->handle_bindings_substituted( $event ),
		);
	}

	/**
	 * Add an entity to the router.
	 *
	 * @param Router_Contract $router Router instance.
	 * @param string          $entity Entity class name.
	 * @param string          $controller Controller class name.
	 *
	 * @throws InvalidArgumentException Thrown on invalid entity.
	 */
	public function add( Router_Contract $router, string $entity, string $controller ): void {
		if ( ! is_subclass_of( $entity, Model::class ) ) {
			throw new InvalidArgumentException( "Unknown entity type: [{$entity}]" );
		}

		if ( ! in_array( Url_Routable::class, class_implements( $entity ), true ) ) {
			throw new InvalidArgumentException( "Unroutable entity: [{$entity}]" );
		}

		static::resolve_entity_endpoints( $router, $entity, $controller );

		$this->events->dispatch( new Entity_Route_Added( $entity, $controller ) );
	}

	/**
	 * Handle the route match for entity routes.
	 *
	 * @param Route_Matched $event Event instance.
	 */
	protected function handle_route_matched( Route_Matched $event ): void {
		global $wp_query;

		$route = $event->route;

		// Ignore if the route isn't an entity route.
		if ( ! $route->hasOption( 'entity_router' ) ) {
			return;
		}

		// Ignore if the entity model referenced doesn't exist.
		$entity = $route->getOption( 'entity' );
		if ( ! class_exists( $entity ) ) {
			Log::error( "Entity matched for route not found [{$entity}]" );
			return;
		}

		// Instantiate WP_Query if it isn't set already.
		if ( ! $wp_query ) {
			$wp_query = new \WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// Setup the WordPress template variables.
		if ( 'single' === $route->getOption( 'entity_router' ) ) {
			if ( is_subclass_of( $entity, Term::class ) ) {
				$wp_query->is_archive = true;
				$wp_query->is_tax     = true;

				$object_name = $entity::get_object_name();

				if ( 'post_tag' === $object_name ) {
					$wp_query->is_tag = true;
				} elseif ( 'category' === $object_name ) {
					$wp_query->is_category = true;
				}
			} elseif ( is_subclass_of( $entity, Post::class ) ) {
				$wp_query->is_single   = true;
				$wp_query->is_singular = true;
			}
		} elseif ( 'index' === $route->getOption( 'entity_router' ) ) {
			$wp_query->is_archive = true;
		}
	}

	/**
	 * Handle the bindings being substituted for a route.
	 *
	 * @todo Add support for other non-post/term queried objects such as post types.
	 *
	 * @param Bindings_Substituted $event Event instance.
	 */
	protected function handle_bindings_substituted( Bindings_Substituted $event ): void {
		global $wp_query, $post;

		$route      = $event->request->get_route();
		$parameters = collect( $event->request->get_route_parameters()->all() );

		// Set the queried object for the entity route.
		if ( 'single' === $route->getOption( 'entity_router' ) ) {
			$entity = $route->getOption( 'entity' );

			$queried_object = $parameters
				->filter( fn ( $value ) => $value instanceof $entity )
				->pop();
		} else {
			// Set the queried object for the non-entity route -- uses the last model parameter passed to the route.
			$queried_object = $parameters
				->filter( fn ( $value ) => $value instanceof Model || $value instanceof Core_Object )
				->pop();
		}

		if ( empty( $queried_object ) ) {
			return;
		}

		if ( $queried_object instanceof Core_Object ) {
			$wp_query->queried_object_id = $queried_object->id();
			$wp_query->queried_object    = $queried_object->core_object();
		} else {
			$wp_query->queried_object = $queried_object;
		}

		// Setup the global post object.
		if ( $wp_query->queried_object instanceof \WP_Post ) {
			$post = $wp_query->queried_object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			setup_postdata( $post );
		}
	}

	/**
	 * Resolve the endpoints to add for an entity.
	 *
	 * @param Router_Contract $router Router instance.
	 * @param string          $entity Entity class name.
	 * @param string          $controller Controller class name.
	 */
	protected static function resolve_entity_endpoints( Router_Contract $router, string $entity, string $controller ): void {
		// Singular endpoint.
		$single_route = $entity::get_route();

		if ( $single_route && method_exists( $controller, 'show' ) ) {
			$router
				->get( trailingslashit( $single_route ), [ $controller, 'show' ] )
				->addOptions(
					[
						'entity_router' => 'single',
						'entity'        => $entity,
					]
				);
		}

		$archive_route = $entity::get_archive_route();

		if ( $archive_route && method_exists( $controller, 'index' ) ) {
			$router
				->get( trailingslashit( $archive_route ), [ $controller, 'index' ] )
				->addOptions(
					[
						'entity_router' => 'index',
						'entity'        => $entity,
					]
				);

			// Add a paginated endpoint for the archive.
			$router
				->get( trailingslashit( $archive_route ) . 'page/{page}/', [ $controller, 'index' ] )
				->addOptions(
					[
						'entity_router' => 'index',
						'entity'        => $entity,
					]
				);
		}
	}
}
