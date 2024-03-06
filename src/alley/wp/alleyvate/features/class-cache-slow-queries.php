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

		if ( is_array( $cache ) ) {
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
		if ( $this->cache_months_dropdown ) {
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

		if ( ! is_array( $cache ) ) {
			return;
		}

		// Check if the post's month is in the cache.
		$cache = array_map(
			fn ( $month ) => "{$month->year}-{$month->month}",
			$cache,
		);

		// Clear the month dropdown cache if the post's month is not in the cache.
		if ( ! in_array( get_the_date( 'Y-n', $post ), $cache, true ) ) {
			wp_cache_delete( $post->post_type, 'alleyvate_months_dropdown' );
		}
	}
}
