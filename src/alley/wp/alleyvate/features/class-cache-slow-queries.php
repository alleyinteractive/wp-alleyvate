<?php
/**
 * Cache_Slow_Queries class file
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare(strict_types=1);

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;

/**
 * Cache slow queries that do not perform well for large sites.
 */
final class Cache_Slow_Queries implements Feature {
	/**
	 * Whether to cache the months dropdown.
	 *
	 * @var bool
	 */
	public bool $cache_months_dropdown = false;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		/**
		 * Filter if the months dropdown query should be cached.
		 *
		 * @param bool $cache_months_dropdown Whether to cache the months dropdown. Default true.
		 */
		if ( apply_filters( 'alleyvate_cache_months_dropdown', true ) ) {
			add_filter( 'pre_months_dropdown_query', [ $this, 'filter__pre_months_dropdown_query' ], 10, 2 );
			add_filter( 'months_dropdown_results', [ $this, 'filter__months_dropdown_results' ], 10, 2 );
			add_action( 'save_post', [ $this, 'action__save_post' ], 10, 2 );
			add_action( 'delete_post', [ $this, 'action__delete_post' ], 10, 2 );
		}

		/**
		 * Filter if the available post MIME types query should be cached.
		 *
		 * @param bool $cache_available_post_mime_types Whether to cache the available post MIME types. Default true.
		 */
		if ( apply_filters( 'alleyvate_cache_available_post_mime_types', true ) ) {
			add_filter( 'pre_get_available_post_mime_types', [ $this, 'filter__pre_get_available_post_mime_types' ], 10, 2 );
			add_action( 'add_attachment', [ $this, 'action__invalidate_available_post_mime_types_cache' ] );
			add_action( 'edit_attachment', [ $this, 'action__invalidate_available_post_mime_types_cache' ] );
			add_action( 'delete_attachment', [ $this, 'action__invalidate_available_post_mime_types_cache' ] );
		}
	}

	/**
	 * Filter the pre months dropdown query to return the cached result.
	 *
	 * @param object[]|false $months 'Months' drop-down results. Default false.
	 * @param string         $post_type The post type.
	 * @return object[]|false
	 */
	public function filter__pre_months_dropdown_query( $months, $post_type ) {
		$cache = wp_cache_get( $post_type, 'alleyvate_months_dropdown' );

		if ( \is_array( $cache ) ) {
			return $cache;
		}

		// Set the flag to cache the months dropdown.
		$this->cache_months_dropdown = true;

		return $months;
	}

	/**
	 * Filter the months dropdown results.
	 *
	 * @param object[] $months    Array of the months drop-down query results.
	 * @param string   $post_type The post type.
	 * @return object[]|false
	 */
	public function filter__months_dropdown_results( $months, $post_type ) {
		if ( $this->cache_months_dropdown && \is_array( $months ) ) {
			wp_cache_set( $post_type, $months, 'alleyvate_months_dropdown', DAY_IN_SECONDS );

			$this->cache_months_dropdown = false;
		}

		return $months;
	}

	/**
	 * Action to delete the cache when a post is published.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function action__save_post( $post_id, $post ): void {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$cache = wp_cache_get( $post->post_type, 'alleyvate_months_dropdown' );

		if ( ! \is_array( $cache ) ) {
			return;
		}

		// Check if the post's month is in the cache.
		$cache = array_map(
			fn ( $month ) => "{$month->year}-{$month->month}",
			$cache,
		);

		// Clear the month dropdown cache if the post's month is not in the cache.
		if ( ! \in_array( get_the_date( 'Y-n', $post ), $cache, true ) ) {
			wp_cache_delete( $post->post_type, 'alleyvate_months_dropdown' );
		}
	}

	/**
	 * Handle a post being deleted.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function action__delete_post( $post_id, $post ): void {
		wp_cache_delete( $post->post_type, 'alleyvate_months_dropdown' );
	}

	/**
	 * Filter the available post MIME types to return the cached result.
	 *
	 * `get_available_post_mime_types()` runs an expensive `SELECT DISTINCT
	 * post_mime_type` query over the posts table on every media library page
	 * load. WordPress core added the `pre_get_available_post_mime_types`
	 * filter in version 6.4.0, which allows short-circuiting that query.
	 * On older versions of WordPress the filter is never applied and the
	 * query runs as usual.
	 *
	 * There is no "after" filter for this function in core, so on a cache
	 * miss this runs the same query core would run, caches the result and
	 * returns it. Subsequent calls return from the object cache until the
	 * cache expires or is invalidated by an attachment being added, edited
	 * or deleted.
	 *
	 * @param string[]|null $mime_types An array of MIME types. Default null.
	 * @param string        $type       The post type name. Usually 'attachment' but can be any post type.
	 * @return string[]
	 */
	public function filter__pre_get_available_post_mime_types( $mime_types, $type ) {
		$cache = wp_cache_get( $type, 'alleyvate_available_post_mime_types' );

		if ( \is_array( $cache ) ) {
			return $cache;
		}

		global $wpdb;

		$mime_types = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_mime_type FROM $wpdb->posts WHERE post_type = %s AND post_mime_type != ''",
				$type,
			),
		);

		$mime_types = array_values( array_filter( $mime_types ) );

		wp_cache_set( $type, $mime_types, 'alleyvate_available_post_mime_types', DAY_IN_SECONDS );

		return $mime_types;
	}

	/**
	 * Invalidate the cached available post MIME types when an attachment changes.
	 *
	 * @param int $post_id The attachment ID.
	 */
	public function action__invalidate_available_post_mime_types_cache( $post_id ): void {
		wp_cache_delete( 'attachment', 'alleyvate_available_post_mime_types' );
	}
}
