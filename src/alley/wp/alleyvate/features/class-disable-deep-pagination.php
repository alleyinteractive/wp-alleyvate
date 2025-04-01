<?php
/**
 * Class file for Disable_Deep_Pagination
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

use WP_Query;

/**
 * Disables Pagination beyond a filterable maximum. Beyond that return
 * a 400 error describing why the issue has arisen.
 */
final class Disable_Deep_Pagination implements Feature {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'posts_where', [ self::class, 'filter__posts_where' ], 10, 2 );
		add_filter( 'posts_results', [ self::class, 'filter__posts_results' ], 9999, 2 ); // High priority to ensure we can override the max_num_pages.
	}

	/**
	 * Filter the query to force no results if beyond page maximum.
	 *
	 * @param string   $where    The WHERE clause.
	 * @param WP_Query $wp_query The WP_Query object, passed by reference.
	 * @return string
	 */
	public static function filter__posts_where( string $where, WP_Query $wp_query ): string {
		// If this is an admin request, or a JSON request with a logged in user, return the posts as normal.
		$max_pages = ! empty( $wp_query->query['__dangerously_set_max_pages'] ) ? $wp_query->query['__dangerously_set_max_pages'] : 100;
		if (
			is_admin() ||
			(
				wp_is_json_request() &&
				is_user_logged_in()
			) ||
			empty( $wp_query->query['paged'] ) ||
			$wp_query->query['paged'] <= apply_filters( 'alleyvate_deep_pagination_max_pages', $max_pages )
		) {
			return $where;
		}

		// If this is a JSON request, and the user is not logged in, we need to return a 400 error.
		if ( wp_is_json_request() ) {
			wp_die(
				\sprintf(
					/* translators: The maximum number of pages. */
					esc_html__( 'Invalid Request: Pagination beyond page %d has been disabled for performance reasons.', 'alley' ),
					esc_html( $max_pages ),
				),
				esc_html__( 'Deep Pagination Disabled', 'alley' ),
				400
			);
		}

		// Set the HTTP response status code to 410.
		status_header( 410 );
		// Load the 404 template.
		include get_404_template();
		return $where . 'AND 1 = 0';
	}

	/**
	 * Filter post results to force max num of pages.
	 *
	 * @param array<\WP_Post> $posts    The posts.
	 * @param WP_Query        $wp_query The WP_Query object, passed by reference.
	 * @return array<\WP_Post>
	 */
	public static function filter__posts_results( array $posts, WP_Query $wp_query ): array {
		// If this is an admin request, or a JSON request with a logged in user, return the posts as normal.
		$max_pages = apply_filters( 'alleyvate_deep_pagination_max_pages', ! empty( $wp_query->query['__dangerously_set_max_pages'] ) ? $wp_query->query['__dangerously_set_max_pages'] : 100 );
		if (
			! is_admin() &&
			(
				! wp_is_json_request() ||
				! is_user_logged_in()
			) &&
			$wp_query->max_num_pages > $max_pages
		) {
			$wp_query->max_num_pages = $max_pages;
		}

		return $posts;
	}
}
