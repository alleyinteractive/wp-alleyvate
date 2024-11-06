<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\View;

use Illuminate\View\Concerns\ManagesLayouts;
use Illuminate\View\Concerns\ManagesLoops;
use Illuminate\View\Concerns\ManagesStacks;
use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Contracts\Http\View\Factory as ViewFactory;
use Mantle\Contracts\View\Engine;
use Mantle\Support\Arr;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\View\Engines\Engine_Resolver;
use WP_Query;

/**
 * View Factory
 */
class Factory implements ViewFactory {
	use ManagesLayouts;
	use ManagesLoops;
	use ManagesStacks;

	/**
	 * The IoC container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * The view engine resolver.
	 *
	 * @var Engine_Resolver
	 */
	protected $engines;

	/**
	 * The view finder.
	 *
	 * @var View_Finder
	 */
	protected $finder;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = [];

	/**
	 * Stack of views being rendered.
	 *
	 * @var array
	 */
	protected $stack;

	/**
	 * Current view being rendered.
	 *
	 * @var View|null
	 */
	protected $current;

	/**
	 * The extension to engine bindings.
	 *
	 * @var string[]
	 */
	protected $extensions = [
		'blade.php' => 'blade',
		'php'       => 'php',
		'css'       => 'file',
		'html'      => 'file',
	];

	/**
	 * Constructor.
	 *
	 * @param Container       $container Container to set.
	 * @param Engine_Resolver $engines Engine Resolver.
	 * @param View_Finder     $finder View Finder.
	 */
	public function __construct( Container $container, Engine_Resolver $engines, View_Finder $finder ) {
		$this->engines = $engines;
		$this->finder  = $finder;

		$this->set_container( $container );
		$this->share( '__env', $this );
	}

	/**
	 * Set the container to use.
	 *
	 * @param Container $container Container instance.
	 */
	public function set_container( Container $container ) {
		$this->container = $container;
		return $this;
	}

	/**
	 * Get the container to use.
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get the current view.
	 */
	public function get_current(): ?View {
		return $this->current;
	}

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array|string $key Key to share.
	 * @param mixed|null   $value Value to share.
	 * @return mixed
	 */
	public function share( $key, $value = null ) {
		$keys = is_array( $key ) ? $key : [ $key => $value ];

		foreach ( $keys as $key => $value ) {
			$this->shared[ $key ] = $value;
		}

		return $value;
	}

	/**
	 * Get an item from the shared data.
	 *
	 * @param string $key Key to get item by.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function shared( $key, $default = null ) {
		return Arr::get( $this->shared, $key, $default );
	}

	/**
	 * Get all of the shared data for the environment.
	 */
	public function get_shared(): array {
		return $this->shared;
	}

	/**
	 * Push a view onto the stack and set it as the current view.
	 *
	 * @param View $view View being loaded.
	 * @return static
	 */
	public function push( View $view ) {
		$this->stack[] = $view;
		$this->current = $view;

		return $this;
	}

	/**
	 * Pop a partial off the top of the stack and set the current partial to the
	 * next one down.
	 *
	 * @return static
	 */
	public function pop() {
		array_pop( $this->stack );

		$this->current = end( $this->stack );

		if ( ! $this->current ) {
			$this->current = null;
		}

		return $this;
	}

	/**
	 * Get a variable from the current view.
	 *
	 * @param string $key Variable to get.
	 * @param mixed  $default Default value if unset.
	 * @return mixed
	 */
	public function get_var( string $key, $default = null ) {
		if ( empty( $this->current ) ) {
			return $default;
		}

		return $this->current->get_variable( $key, $default );
	}

	/**
	 * Get the rendered contents of a view.
	 *
	 * @param string       $slug View slug.
	 * @param array|string $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @param array        $variables Variables for the view, optional.
	 */
	public function make( string $slug, $name = null, array $variables = [] ): View {
		if ( is_array( $name ) ) {
			$variables = array_merge( $name, $variables );
			$name      = null;
		}

		$variables = array_merge( $this->get_shared(), $variables );
		$path      = $this->resolve_view_path( $slug, $name );
		$engine    = $this->get_engine_from_path( $path );

		return new View( $this, $engine, $path, $variables );
	}

	/**
	 * Resolve the view path for a given template slug and name.
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template name.
	 * @return string|null File path, null otherwise.
	 */
	protected function resolve_view_path( string $slug, string $name = null ): ?string {
		// Prepend the current view if the requested slug is a child template.
		if ( Str::starts_with( $slug, '_' ) && $this->current ) {
			return $this->resolve_child_view_path_from_parent( $slug );
		}

		return $this->finder->find( $slug, $name );
	}

	/**
	 * Resolve a child view path from the current parent.
	 *
	 * @param string $slug Slug of the view to load.
	 * @return string
	 * @throws InvalidArgumentException Thrown if child view not found.
	 */
	protected function resolve_child_view_path_from_parent( string $slug ) {
		$path = Str::before( $this->current->get_path(), '.' ) . '-' . Str::substr( $slug, 1 );

		foreach ( $this->finder->get_possible_view_files( $path ) as $file ) {
			if ( file_exists( $file ) ) {
				return $file;
			}
		}

		throw new InvalidArgumentException( "Child view not found: [{$path}]" );
	}

	/**
	 * Create a collection of views that loop over a collection of WordPress objects.
	 *
	 * While iterating over the data, the proper post data is setup for each item.
	 *
	 * @param array|\ArrayAccess $data Array of WordPress data to loop over.
	 * @param string             $slug View slug.
	 * @param array|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array              $variables Variables for the view, optional.
	 */
	public function loop( $data, string $slug, $name = null, array $variables = [] ): Collection {
		$results = new Collection();

		// Extract the posts from the query.
		if ( $data instanceof WP_Query ) {
			$data = $data->posts;
		}

		// Loop through an array of posts.
		foreach ( $data as $i => $item ) {
			// Append the current index as a dynamic variable.
			$variables['index'] = $i;

			$results[] = $this->make( $slug, $name, $variables )->set_post( $item );
		}

		return $results;
	}

	/**
	 * Iterate over an array, loading a given template part for each item in the
	 * array.
	 *
	 * @param array|\ArrayAccess $data Array of data to iterate over over.
	 * @param string             $slug View slug.
	 * @param array|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array              $variables Variables for the view, optional.
	 */
	public function iterate( $data, string $slug, $name = null, array $variables = [] ): Collection {
		if ( is_array( $name ) ) {
			$variables = array_merge( $name, $variables );
			$name      = null;
		}

		$results = new Collection();

		foreach ( $data as $index => $item ) {
			$variables['item']  = $item;
			$variables['index'] = $index;

			$results[] = $this->make( $slug, $name, $variables );
		}

		return $results;
	}

	/**
	 * Resolve the engine for a given path.
	 *
	 * @param string $path Path to resolve.
	 * @return Engine|\Illuminate\View\Engines\CompilerEngine
	 *
	 * @throws InvalidArgumentException Thrown on unknown extension from file.
	 */
	public function get_engine_from_path( string $path ) {
		$extension = $this->get_extension( $path );
		if ( ! $extension ) {
			throw new InvalidArgumentException( "Unknown extension in file: {$path}" );
		}

		return $this->engines->resolve( $this->extensions[ $extension ] );
	}

	/**
	 * Get the extension used by the view file.
	 *
	 * @param  string $path Path to check against.
	 */
	protected function get_extension( string $path ): ?string {
		$extensions = array_keys( $this->extensions );

		return Arr::first(
			$extensions,
			fn ( $value ) => Str::ends_with( $path, '.' . $value )
		);
	}
}
