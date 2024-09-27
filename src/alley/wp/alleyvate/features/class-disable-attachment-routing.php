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

use WP_Admin_Bar;
use WP_Query;
use Alley\WP\Types\Feature;

/**
 * Disable attachment routing.
 */
final class Disable_Attachment_Routing implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'pre_option_wp_attachment_pages_enabled', '__return_zero', 100 );
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ] );
		add_filter( 'attachment_link', [ self::class, 'filter__attachment_link' ] );
		add_filter( 'pre_handle_404', [ self::class, 'filter__pre_handle_404' ], 10, 2 );
		add_action( 'admin_bar_menu', [ self::class, 'action__admin_bar_menu' ], 100 );
	}

	/**
	 * Remove support for the attachment rewrite rule.
	 *
	 * @param array<string, string> $rules Rewrite rules.
	 * @return array<string, string>
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
	 * @return string
	 */
	public static function filter__attachment_link(): string {
		return '';
	}

	/**
	 * Filters whether to short-circuit default header status handling.
	 *
	 * @param bool     $preempt  Whether to short-circuit default header status handling.
	 * @param WP_Query $wp_query Query object.
	 * @return bool
	 */
	public static function filter__pre_handle_404( $preempt, $wp_query ) {
		if ( $wp_query->is_attachment() ) {
			$preempt = true;

			// This wipes out `is_attachment`, so the redirect to the attachment file in `canonical_redirect()` is bypassed.
			$wp_query->set_404();

			// `WP::handle_404()` normally calls these, but we're preempting that.
			status_header( 404 );
			nocache_headers();
		}

		return $preempt;
	}

	/**
	 * Remove attachment link from admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar class.
	 */
	public static function action__admin_bar_menu( $wp_admin_bar ): void {
		if ( 'attachment' === get_post_type() ) {
			$wp_admin_bar->remove_node( 'view' );
		}
	}
}
