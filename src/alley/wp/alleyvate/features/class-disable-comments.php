<?php
/**
 * Class file for Disable_Comments
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

/**
 * Fully disables comments.
 */
final class Disable_Comments implements Feature {
	/**
	 * A callback for the action_add_meta_boxes action hook.
	 *
	 * @param string $post_type The post type that metaboxes are being registered for.
	 */
	public static function action__add_meta_boxes( string $post_type ): void {
		remove_meta_box( 'commentsdiv', $post_type, 'normal' );
		remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
	}

	/**
	 * A callback for the admin_bar_menu action hook.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar An instance of the WP_Admin_Bar class.
	 */
	public static function action__admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ): void {
		$wp_admin_bar->remove_node( 'comments' );
	}

	/**
	 * A callback for the admin_init action hook.
	 */
	public static function action__admin_init(): void {
		global $pagenow;

		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * A callback for the admin_menu action hook.
	 */
	public static function action__admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * A callback for the init action hook.
	 */
	public static function action__init(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}

			// The REST API filters don't have a generic form, so they need to be registered for each post type.
			add_filter( "rest_prepare_{$post_type}", [ self::class, 'filter__rest_prepare' ], \PHP_INT_MAX );
		}
	}

	/**
	 * A callback for the comments_pre_query filter hook.
	 *
	 * @param array|int|null    $comment_data  Not used.
	 * @param \WP_Comment_Query $comment_query The comment query object to filter results for.
	 */
	public static function filter__comments_pre_query( $comment_data, \WP_Comment_Query $comment_query ) {
		return $comment_query->query_vars['count'] ? 0 : [];
	}

	/**
	 * A callback for the rest_endpoints filter hook.
	 *
	 * @param array $endpoints REST endpoints to be filtered.
	 *
	 * @return array Filtered endpoints.
	 */
	public static function filter__rest_endpoints( array $endpoints ): array {
		unset( $endpoints['/wp/v2/comments'] );
		unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );

		return $endpoints;
	}

	/**
	 * A callback for the rest_prepare_{$post_type} filter hook.
	 *
	 * @param \WP_REST_Response $response Response to filter.
	 *
	 * @return \WP_REST_Response Filtered response.
	 */
	public static function filter__rest_prepare( \WP_REST_Response $response ): \WP_REST_Response {
		$response->remove_link( 'replies' );
		if ( isset( $response->data['comment_status'] ) ) {
			$response->data['comment_status'] = 'closed';
		}

		return $response;
	}

	/**
	 * A callback for the comments_pre_query filter hook.
	 *
	 * @param array $rules Rewrite rules to be filtered.
	 *
	 * @return array Filtered rewrite rules.
	 */
	public static function filter__rewrite_rules_array( array $rules ): array {
		foreach ( array_keys( $rules ) as $regex ) {
			if ( str_contains( $regex, 'comment-page-' ) ) {
				unset( $rules[ $regex ] );
			}
		}

		return $rules;
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'add_meta_boxes', [ self::class, 'action__add_meta_boxes' ], \PHP_INT_MAX );
		add_action( 'admin_bar_menu', [ self::class, 'action__admin_bar_menu' ], \PHP_INT_MAX );
		add_action( 'admin_init', [ self::class, 'action__admin_init' ], \PHP_INT_MIN );
		add_action( 'admin_menu', [ self::class, 'action__admin_menu' ], \PHP_INT_MAX );
		add_action( 'init', [ self::class, 'action__init' ], \PHP_INT_MAX );
		add_filter( 'comments_open', '__return_false', \PHP_INT_MAX );
		add_filter( 'comments_pre_query', [ self::class, 'filter__comments_pre_query' ], \PHP_INT_MAX, 2 );
		add_filter( 'comments_rewrite_rules', '__return_empty_array', \PHP_INT_MAX );
		add_filter( 'get_comments_number', '__return_zero', \PHP_INT_MAX );
		add_filter( 'rest_endpoints', [ self::class, 'filter__rest_endpoints' ], \PHP_INT_MAX );
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ], \PHP_INT_MAX );
	}
}
