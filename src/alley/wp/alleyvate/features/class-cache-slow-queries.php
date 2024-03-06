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
	 * Boot the feature.
	 */
	public function boot(): void {
		if ( apply_filters( 'alleyvate_cache_months_dropdown', true ) ) {
			add_filter( 'pre_months_dropdown_query', [ $this, 'filter__pre_months_dropdown_query' ], 10, 2 );
			add_filter( 'months_dropdown_results', [ $this, 'filter__months_dropdown_results' ], 10, 2 );
		}
	}

	/**
	 * Filter the pre months dropdown query to return the cached result.
	 *
	 * @param object|false $months 'Months' drop-down results. Default false.
	 * @param string       $post_type The post type.
	 * @return object|false
	 */
	public function filter__pre_months_dropdown_query( $months, $post_type ) {
		$cache = wp_cache_get( $post_type, 'alleyvate_months_dropdown' );

		return false !== $cache && is_object( $cache ) ? $cache : $months;
	}

	/**
	 * Filter the months dropdown results.
	 *
	 * @param object $months    Array of the months drop-down query results.
	 * @param string $post_type The post type.
	 * @return object|false
	 */
	public function filter__months_dropdown_results( $months, $post_type ) {
		wp_cache_set( $post_type, $months, 'alleyvate_months_dropdown' );

		return $months;
	}
}
