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
		add_filter( 'render_block_context', [ self::class, 'render_block_context_query_pagination' ], 10, 2 );
	}

	/**
	 * Filter the query to force no results if beyond page maximum.
	 *
	 * @param string   $where    The WHERE clause.
	 * @param WP_Query $wp_query The WP_Query object, passed by reference.
	 * @return string
	 */
	public static function filter__posts_where( string $where, WP_Query $wp_query ): string {
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

		wp_die(
			\sprintf(
				/* translators: The maximum number of pages. */
				esc_html__( 'Invalid Request: Pagination beyond page %d has been disabled for performance reasons.', 'alley' ),
				esc_html( $max_pages ),
			),
			esc_html__( 'Deep Pagination Disabled', 'alley' ),
			410
		);

		return $where . 'AND 1 = 0';
	}

	/**
	 * Filter the context for the query-pagination blocks.
	 *
	 * @param array<string, mixed> $context The block data.
	 * @param array<string, mixed> $block   The context data.
	 * @return array<string, mixed> The pagination data.
	 */
	public static function render_block_context_query_pagination( array $context, array $block ): array {
		global $wp_query;

		// Check if the block is one of the query-pagination blocks.
		if ( str_starts_with( $block['blockName'], 'core/query-pagination' ) ) {
			// Set the max pages to the value from the query or a default value.
			$max_pages = ! empty( $wp_query->query['__dangerously_set_max_pages'] ) ? $wp_query->query['__dangerously_set_max_pages'] : 100;
			// Set the max pages in the context.
			$context['query']['pages'] = apply_filters( 'alleyvate_deep_pagination_max_pages', $max_pages );
		}

		return $context;
	}
}
