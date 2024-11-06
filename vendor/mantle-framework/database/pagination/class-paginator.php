<?php
/**
 * Paginator class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Pagination;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use JsonSerializable;
use Mantle\Contracts\Container;
use Mantle\Contracts\Paginator\Paginator as PaginatorContract;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Contracts\Support\Htmlable;
use Mantle\Contracts\Support\Jsonable;
use Mantle\Database\Query\Builder;
use Mantle\Http\Request;
use Mantle\Http\View\View;
use Mantle\Support\Collection;
use Mantle\Support\Str;

/**
 * Paginator for query results.
 */
class Paginator implements Arrayable, ArrayAccess, Countable, Jsonable, JsonSerializable, Htmlable, PaginatorContract {
	/**
	 * Current page number
	 *
	 * @var int
	 */
	protected $current_page;

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Query builder instance.
	 *
	 * @var Builder
	 */
	protected $builder;

	/**
	 * Items being paginate.
	 *
	 * @var \Mantle\Support\Collection
	 */
	protected $items;

	/**
	 * Number of items per page.
	 *
	 * @var int
	 */
	protected $per_page;

	/**
	 * Path for URLs.
	 *
	 * @var string
	 */
	protected $path = '/';

	/**
	 * Flag if query strings should be used.
	 *
	 * @var bool
	 */
	protected $use_query_string = true;

	/**
	 * Set of query string values to use on every URL.
	 *
	 * @var array
	 */
	protected $query = [];

	/**
	 * View name to load.
	 *
	 * @var string
	 */
	protected $view = 'simple';

	/**
	 * Flag if the paginator has more.
	 *
	 * @var bool
	 */
	protected $has_more = true;

	/**
	 * The current path resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected static $current_path_resolver;

	/**
	 * The current page resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected static $current_page_resolver;

	/**
	 * The URL generator callback.
	 *
	 * @var \Closure|null
	 */
	protected static $url_generator;

	/**
	 * Constructor.
	 *
	 * @param Container $container Application instance.
	 * @param Builder   $builder Query builder instance.
	 * @param int       $per_page Items per-page.
	 * @param int       $current_page Current page to set.
	 */
	public function __construct( Container $container, Builder $builder, int $per_page = 20, int $current_page = null ) {
		$this->container = $container;
		$this->builder   = $builder;
		$this->per_page  = $per_page;

		$this->builder->take( $per_page );

		$this->set_current_page( $current_page );
		$this->path();
		$this->set_items();
	}

	/**
	 * Set the path to use for the request.
	 *
	 * @param string $path Path to use.
	 * @return static
	 */
	public function path( string $path = null ) {
		if ( $path ) {
			$this->path = $path;
			return $this;
		}

		if ( isset( static::$current_path_resolver ) ) {
			$this->path = (string) call_user_func( static::$current_path_resolver, $this );
			return $this;
		}

		$this->path = static::strip_page_from_path( static::request()->path() );

		// Ensure the path starts with a /.
		if ( $this->path && '/' !== $this->path[0] ) {
			$this->path = "/{$this->path}";
		}

		return $this;
	}

	/**
	 * Retrieve the path to use for the paginator.
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Flag if query strings should be used for the pagination URLs.
	 *
	 * @return static
	 */
	public function use_query_string() {
		$this->use_query_string = true;
		return $this;
	}

	/**
	 * Flag if query strings shouldn't be used for the pagination URLs.
	 *
	 * @return static
	 */
	public function use_path() {
		$this->use_query_string = false;
		return $this;
	}

	/**
	 * Set the current page for the paginator.
	 *
	 * @param int $current_page Current page to set, optional.
	 * @return static
	 */
	public function set_current_page( int $current_page = null ) {
		if ( $current_page && $current_page > 0 ) {
			$this->current_page = $current_page;
		} else {
			$this->current_page = static::resolve_current_page();
		}

		$this->builder->page( $this->current_page );
		return $this;
	}

	/**
	 * Retrieve the current page.
	 */
	public function current_page(): int {
		return $this->current_page;
	}

	/**
	 * Determine if this is the first page.
	 */
	public function on_first_page(): bool {
		return 1 === $this->current_page();
	}

	/**
	 * Set the items for the paginator.
	 *
	 * @return static
	 */
	protected function set_items() {
		$this->items = $this->builder->where( 'no_found_rows', true )->get();
		return $this;
	}

	/**
	 * Retrieve the items for the paginator.
	 */
	public function items(): Collection {
		return $this->items;
	}

	/**
	 * Determine if there are more items in the data source.
	 *
	 * @param bool $has_more Flag if there should be more pages.
	 */
	public function has_more( bool $has_more = null ): bool {
		if ( null !== $has_more ) {
			$this->has_more = $has_more;
		}

		return true;
	}

	/**
	 * Determine if there are enough items to split into multiple pages.
	 */
	public function has_pages(): bool {
		if ( 1 !== $this->current_page() ) {
						return true;
		}
								return $this->has_more();
	}

	/**
	 * Count the number of items.
	 */
	public function count(): int {
		return $this->items->count();
	}

	/**
	 * Append query string value to the paginator.
	 *
	 * @param string|array $key Query string key or array of key value pairs.
	 * @param mixed        $value Query string value.
	 * @return static
	 */
	public function append( $key, $value = null ) {
		if ( is_array( $key ) && null === $value ) {
			foreach ( $key as $k => $v ) {
				$this->append( $k, $v );
			}

			return $this;
		}

		$this->query[ $key ] = $value;
		return $this;
	}

	/**
	 * Append the current query string parameters.
	 *
	 * @return static
	 */
	public function with_query_string() {
		$this->append( static::request()->query() );
		return $this;
	}

	/**
	 * Retrieve the query strings for the paginator.
	 */
	public function query(): array {
		return $this->query;
	}

	/**
	 * Get the current request object.
	 */
	protected static function request(): Request {
		return app( Request::class );
	}

	/**
	 * Resolve the current page.
	 */
	protected static function resolve_current_page(): int {
		if ( static::$current_page_resolver ) {
			return call_user_func( static::$current_page_resolver );
		}

		$request = static::request();
		$query   = $request->query( 'page' );
		if ( $query ) {
			return (int) $query;
		}

		$path = $request->path();

		if ( ! Str::is( 'page/*', $path ) ) {
			return 1;
		}

		return static::get_page_from_path( $path );
	}

	/**
	 * Find the current page from the path.
	 *
	 * @param string $path URL path.
	 */
	protected static function get_page_from_path( string $path ): ?int {
		if ( ! Str::is( 'page/*', $path ) ) {
			return 1;
		}
		preg_match_all( '/page\/(\d*)\/?/', $path, $matches );
		return (int) ( $matches[1][0] ?? 1 );
	}

	/**
	 * Find the current page from the path.
	 *
	 * @param string $path URL path.
	 */
	protected static function strip_page_from_path( string $path ): string {
		if ( ! Str::is( 'page/*', $path ) ) {
			return $path;
		}
		preg_match_all( '/page\/(\d*)\/?/', $path, $matches );

		if ( ! empty( $matches[0][0] ) ) {
			return str_replace( $matches[0][0], '', $path );
		}

		return $path;
	}

	/**
	 * Set the current page resolver.
	 *
	 * @param callable $callback Callback for the resolver.
	 */
	public static function current_page_resolver( callable $callback ): void {
		static::$current_page_resolver = $callback;
	}

	/**
	 * Set the current path resolver.
	 *
	 * @param callable $callback Callback for the resolver.
	 */
	public static function current_path_resolver( callable $callback ): void {
		static::$current_path_resolver = $callback;
	}

	/**
	 * Generate a URL for a given page.
	 *
	 * @param int $page Page number.
	 */
	public function url( int $page ): string {
		if ( isset( static::$url_generator ) ) {
			return call_user_func( static::$url_generator, $page, $this );
		}

		if ( $this->use_query_string ) {
			if ( $page <= 1 ) {
				return add_query_arg( $this->query, $this->path );
			}

			return add_query_arg(
				[
					'page' => $page,
				] + $this->query,
				$this->path
			);
		}

		if ( $page < 1 ) {
			return add_query_arg( $this->query, $this->path );
		}

		return add_query_arg( $this->query, trailingslashit( $this->path ) . 'page/' . $page . '/' );
	}

	/**
	 * Retrieve the URL for the next page.
	 */
	public function next_url(): ?string {
		if ( $this->has_more() ) {
			return $this->url( $this->current_page() + 1 );
		}

		return null;
	}

	/**
	 * Retrieve the URL for the previous page.
	 */
	public function previous_url(): ?string {
		if ( $this->current_page() > 1 ) {
			return $this->url( $this->current_page() - 1 );
		}

		return null;
	}

	/**
	 * Convert the paginator to an array.
	 */
	public function to_array(): array {
		return [
			'current_page'   => $this->current_page(),
			'data'           => $this->items->to_array(),
			'first_page_url' => $this->url( 1 ),
			'next_url'       => $this->next_url(),
			'path'           => $this->get_path(),
			'previous_url'   => $this->previous_url(),
		];
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize(): mixed {
		return $this->to_array();
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param int $options Options for json_encode().
	 * @return string
	 */
	public function to_json( $options = 0 ) {
		return wp_json_encode( $this->jsonSerialize(), $options );
	}

	/**
	 * Check if an item exists at an offset.
	 *
	 * @param mixed $offset Array offset.
	 */
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->items[ $offset ] );
	}

	/**
	 * Retrieve an item by its offset.
	 *
	 * @param mixed $offset Offset to get.
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->items[ $offset ];
	}

	/**
	 * Set an item by its offset.
	 *
	 * @param mixed $offset Offset to get.
	 * @param mixed $value  Value to set.
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->items[ $offset ] = $value;
	}

	/**
	 * Delete by an offset.
	 *
	 * @param mixed $offset Offset to delete.
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->items[ $offset ] );
	}

	/**
	 * View name to load.
	 *
	 * @param string $view View name.
	 * @return static
	 */
	public function view( string $view ) {
		$this->view = $view;

		return $this;
	}

	/**
	 * Render the paginator
	 *
	 * @param string $view View name to load, optional.
	 * @param array  $data View data.
	 */
	public function render( string $view = null, array $data = [] ): ?View {
		try {
			return $this->container['view']->make(
				$view ?: $this->view,
				array_merge(
					$data,
					[
						'paginator' => $this,
					]
				)
			);
		} catch ( InvalidArgumentException $e ) {
			unset( $e );
			return null;
		}
	}

	/**
	 * Render the links
	 *
	 * @param string $view View name to load, optional.
	 * @param array  $data View data.
	 */
	public function links( string $view = null, array $data = [] ): string {
		return (string) $this->render( $view, $data );
	}

	/**
	 * Convert the paginator to HTML.
	 *
	 * @param string $view View name to load, optional.
	 * @param array  $data View data.
	 */
	public function to_html( string $view = null, array $data = [] ): string {
		return (string) $this->render( $view, $data );
	}
}
