<?php
/**
 * Class file for Disable_Attachment_Routing
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
 * Disable attachment routing.
 */
final class Disable_Attachment_Routing implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ] );
		add_filter( 'attachment_link', [ self::class, 'filter__attachment_link' ] );
		add_action( 'pre_get_posts', [ self::class, 'action__pre_get_posts' ] );
		add_action( 'admin_bar_menu', [ self::class, 'action__admin_bar_menu' ], 100 );
	}

	/**
	 * Remove support for the attachment rewrite rule.
	 *
	 * @param array $rules Rewrite rules.
	 * @return array
	 */
	public static function filter__rewrite_rules_array( $rules ): array {
		foreach ( $rules as $regex => $query ) {
			if ( strpos( $regex, 'attachment' ) || strpos( $query, 'attachment' ) ) {
				unset( $rules[ $regex ] );
			}
		}

		return $rules;
	}

	/**
	 * Remove the attachment link.
	 *
	 * @param string $link Attachment link.
	 * @return string
	 */
	public static function filter__attachment_link( $link ): string {
		return '';
	}

	/**
	 * Ensure attachment pages return 404s.
	 *
	 * @param WP_Query $query WP_Query object.
	 */
	public static function action__pre_get_posts( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if (
			$query->queried_object instanceof \WP_Post
			&& 'attachment' === get_post_type( $query->get_queried_object_id() )
		) {
			$query->set_404();
			status_header( 404 );
		}
	}

	/**
	 * Remove attachment link from admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar class.
	 */
	public static function action__admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ) {
		if ( 'attachment' == get_post_type() ) {
			$wp_admin_bar->remove_node( 'view' );
		}
	}
}
