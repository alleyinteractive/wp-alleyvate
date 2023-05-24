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
		add_action( 'admin_bar_menu', [ self::class, 'action__admin_bar_menu' ], \PHP_INT_MAX );
		add_action( 'admin_init', [ self::class, 'action__admin_init' ], \PHP_INT_MIN );
		add_action( 'admin_menu', [ self::class, 'action__admin_menu' ], \PHP_INT_MAX );
		add_action( 'init', [ self::class, 'action__init' ], \PHP_INT_MAX );
		add_filter( 'comments_open', '__return_false', \PHP_INT_MAX );
		add_filter( 'comments_pre_query', [ self::class, 'filter__comments_pre_query' ], \PHP_INT_MAX, 2 );
		add_filter( 'comments_rewrite_rules', '__return_empty_array', \PHP_INT_MAX );
		add_filter( 'get_comments_number', '__return_zero', \PHP_INT_MAX );
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ], \PHP_INT_MAX );
	}
}
