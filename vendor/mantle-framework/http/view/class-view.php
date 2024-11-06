<?php
/**
 * View class file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

namespace Mantle\Http\View;

use Illuminate\View\Engines\CompilerEngine;
use Mantle\Contracts\Http\View\Factory as Factory_Contract;
use Mantle\Contracts\View\Engine;
use Mantle\Database\Model\Post;
use Mantle\Support\Arr;

/**
 * View Class
 */
class View implements \Stringable {
	/**
	 * Post object to set for the post.
	 *
	 * @var Post|\WP_Post|int|null
	 */
	protected $post;

	/**
	 * The original post to restore after rendering the view.
	 *
	 * @var \WP_Post
	 */
	protected $original_post;

	/**
	 * Cache key to use.
	 *
	 * @var string
	 */
	protected $cache_key;

	/**
	 * Cache TTL for the view.
	 *
	 * @var int|null
	 */
	protected $cache_ttl;

	/**
	 * Constructor.
	 *
	 * @param Factory_Contract                               $factory View Factory.
	 * @param Engine|\Illuminate\View\Engines\CompilerEngine $engine View Engine.
	 * @param string                                         $path View path.
	 * @param array                                          $data Variables for the view, optional.
	 */
	public function __construct(
		protected Factory_Contract $factory,
		protected Engine|CompilerEngine $engine,
		protected string $path,
		protected array $data = [],
	) {
	}

	/**
	 * Get the view path.
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Set the post for the view.
	 *
	 * Allows the global WordPress post object to be adjusted when rendering the view.
	 *
	 * @param Post|\WP_Post|int $post Post object.
	 * @return static
	 */
	public function set_post( $post ) {
		$this->post = $post;
		return $this;
	}

	/**
	 * Add a piece of data to the view.
	 *
	 * @param string|array $key Key to set.
	 * @param mixed        $value Value to set.
	 * @return static
	 */
	public function with( $key, $value = null ) {
		if ( is_array( $key ) ) {
			$this->data = array_merge( $this->data, $key );
		} else {
			Arr::set( $this->data, $key, $value );
		}

		return $this;
	}

	/**
	 * Get the data for the view.
	 */
	public function get_variables(): array {
		return $this->data;
	}

	/**
	 * Get a specific variable for the view.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default Default value, optional.
	 * @return mixed
	 */
	public function get_variable( string $key, $default = null ) {
		return Arr::get( $this->data, $key, $default );
	}

	/**
	 * Set the cache TTL for the view.
	 *
	 * @param int|bool $cache_ttl Cache TTL or false to disable. Defaults to 15 minutes.
	 * @param string   $cache_key Cache key to use, optional.
	 * @return static
	 */
	public function cache( $cache_ttl = 900, string $cache_key = null ) {
		if ( false === $cache_ttl ) {
			$cache_ttl = -1;
		}

		$this->cache_ttl = $cache_ttl;
		$this->cache_key = $cache_key;
		return $this;
	}

	/**
	 * Retrieve the cache key to use for the view.
	 */
	public function get_cache_key(): string {
		if ( ! empty( $this->cache_key ) ) {
			return $this->cache_key;
		}

		$filtered_data = array_map(
			function ( $value, $key ) {
				// Internal class references do not serialize well.
				if ( '__env' === $key ) {
					return 'app';
				}

				if ( is_object( $value ) ) {
					return spl_object_hash( $value );
				}

				return $value;
			},
			$this->data,
			array_keys( $this->data )
		);

		return 'partial_' . md5( $this->path . serialize( $filtered_data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	}

	/**
	 * Set the global post object for the view.
	 */
	protected function setup_post_object() {
		global $post;

		if ( ! isset( $this->post ) ) {
			return;
		}

		$this->preserve_post();

		if ( $this->post instanceof Post ) {
			$post = \get_post( $this->post->id() );
		} else {
			$post = \get_post( $this->post );
		}

		\setup_postdata( $post );
	}

	/**
	 * Backup the current global `$post`.
	 */
	protected function preserve_post() {
		$this->original_post = $GLOBALS['post'] ?? null;
	}

	/**
	 * Restore the backup of the global $post.
	 *
	 * If our template part changed the global post, we reset it to what it was
	 * before loading the template part. Note that we're not calling
	 * `wp_reset_postdata()` because `$post` may not have been the current post
	 * from the global query.
	 *
	 * @access protected
	 */
	protected function restore_post() {
		global $post;

		$post = $this->original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		\setup_postdata( $post );
	}

	/**
	 * Get the string contents of the view.
	 */
	public function render(): string {
		// Check the cache for the view.
		if ( isset( $this->cache_ttl ) ) {
			$cache_key = $this->get_cache_key();
			$contents  = \get_transient( $cache_key );

			if ( false !== $contents ) {
				return (string) $contents;
			}
		}

		// Setup the post object if needed.
		if ( isset( $this->post ) ) {
			$this->setup_post_object();
		}

		$this->factory->push( $this );

		// Invoke the engine to render the view.
		$contents = $this->engine->get( $this->path, $this->data );

		$this->factory->pop();

		if ( isset( $this->post ) ) {
			$this->restore_post();
		}

		if ( isset( $this->cache_ttl ) ) {
			\set_transient( $cache_key, $contents, $this->cache_ttl );
		}

		return $contents;
	}

	/**
	 * Get the string contents of the view.
	 */
	public function __toString(): string {
		return $this->render();
	}
}
