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

use Alley\WP\Types\Feature;

/**
 * Fully disables comments.
 */
final class Disable_Comments implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'add_meta_boxes', [ self::class, 'action__add_meta_boxes' ], 9999 );
		add_action( 'admin_bar_menu', [ self::class, 'action__admin_bar_menu' ], 9999 );
		add_action( 'admin_init', [ self::class, 'action__admin_init' ], 0 );
		add_action( 'admin_menu', [ self::class, 'action__admin_menu' ], 9999 );
		add_action( 'init', [ self::class, 'action__init' ], 9999 );
		add_filter( 'comments_open', '__return_false', 9999 );
		add_filter( 'comments_pre_query', [ self::class, 'filter__comments_pre_query' ], 9999, 2 );
		add_filter( 'comments_rewrite_rules', '__return_empty_array', 9999 );
		add_filter( 'get_comments_number', '__return_zero', 9999 );
		add_filter( 'rest_endpoints', [ self::class, 'filter__rest_endpoints' ], 9999 );
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ], 9999 );
	}

	/**
	 * Removes the comments metabox from the classic editor.
	 *
	 * @param string $post_type The post type that metaboxes are being registered for.
	 */
	public static function action__add_meta_boxes( string $post_type ): void {
		remove_meta_box( 'commentsdiv', $post_type, 'normal' );
		remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
	}

	/**
	 * Removes the comments node from the admin bar menu.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar An instance of the WP_Admin_Bar class.
	 */
	public static function action__admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ): void {
		$wp_admin_bar->remove_node( 'comments' );
	}

	/**
	 * Redirects direct requests for the comments list and discussion settings page to the admin dashboard.
	 */
	public static function action__admin_init(): void {
		global $pagenow;

		if ( \in_array( $pagenow, [ 'edit-comments.php', 'options-discussion.php' ], true ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Removes the Comments primary menu item and the Discussion submenu item (under Settings) from admin menus.
	 */
	public static function action__admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	/**
	 * Add actions and filters to run on the init hook.
	 */
	public static function action__init(): void {

		// Removes post type support for comments and filters REST responses for each post type to remove comment support.
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}

			// The REST API filters don't have a generic form, so they need to be registered for each post type.
			add_filter( "rest_prepare_{$post_type}", [ self::class, 'filter__rest_prepare' ], 9999 );
		}

		// Removes the Akismet comments section from the dashboard.
		remove_action( 'rightnow_end', [ 'Akismet_Admin', 'rightnow_stats' ] );
	}

	/**
	 * Short-circuits the comments query to return an empty array or 0 (if count was requested).
	 *
	 * @param array<mixed>|int|null $comment_data  Not used.
	 * @param \WP_Comment_Query     $comment_query The comment query object to filter results for.
	 * @return int|array<mixed>
	 */
	public static function filter__comments_pre_query( $comment_data, \WP_Comment_Query $comment_query ) {
		return $comment_query->query_vars['count'] ? 0 : [];
	}

	/**
	 * Removes REST endpoints related to comments.
	 *
	 * @param array<string> $endpoints REST endpoints to be filtered.
	 *
	 * @return array<string> Filtered endpoints.
	 */
	public static function filter__rest_endpoints( array $endpoints ): array {
		unset( $endpoints['/wp/v2/comments'] );
		unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );

		return $endpoints;
	}

	/**
	 * Filters REST responses for post endpoints to force comment_status to be closed.
	 *
	 * @param \WP_REST_Response $response Response to filter.
	 *
	 * @return \WP_REST_Response Filtered response.
	 */
	public static function filter__rest_prepare( \WP_REST_Response $response ): \WP_REST_Response {
		$response->remove_link( 'replies' );

		if ( \is_array( $response->data ) && isset( $response->data['comment_status'] ) ) {
			$response->data['comment_status'] = 'closed';
		}

		return $response;
	}

	/**
	 * Removes rewrite rules related to comments.
	 *
	 * @param array<string> $rules Rewrite rules to be filtered.
	 *
	 * @return array<string> Filtered rewrite rules.
	 */
	public static function filter__rewrite_rules_array( array $rules ): array {
		foreach ( $rules as $regex => $rewrite ) {
			if ( str_contains( $rewrite, 'cpage=$' ) ) {
				unset( $rules[ $regex ] );
			}
		}

		return $rules;
	}
}
