<?php
/**
 * Class file for Disable_Trackbacks
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
 * Fully disables pingbacks and trackbacks.
 */
final class Disable_Trackbacks implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', [ self::class, 'action__init' ], 9999 );
		add_filter( 'pings_open', '__return_false', 9999 );
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ], 9999 );
	}

	/**
	 * Removes post type support for trackbacks and filters REST responses for each post type to remove trackback support.
	 */
	public static function action__init(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}

			// The REST API filters don't have a generic form, so they need to be registered for each post type.
			add_filter( "rest_prepare_{$post_type}", [ self::class, 'filter__rest_prepare' ], 9999 );
		}
	}

	/**
	 * Filters REST responses for post endpoints to force ping_status to be closed.
	 *
	 * @param \WP_REST_Response $response Response to filter.
	 *
	 * @return \WP_REST_Response Filtered response.
	 */
	public static function filter__rest_prepare( \WP_REST_Response $response ): \WP_REST_Response {
		if ( \is_array( $response->data ) && isset( $response->data['ping_status'] ) ) {
			$response->data['ping_status'] = 'closed';
		}

		return $response;
	}

	/**
	 * Removes rewrite rules related to trackbacks.
	 *
	 * @param array<string, string> $rules Rewrite rules to be filtered.
	 *
	 * @return array<string, string> Filtered rewrite rules.
	 */
	public static function filter__rewrite_rules_array( array $rules ): array {
		foreach ( $rules as $regex => $rewrite ) {
			if ( str_contains( $rewrite, 'tb=1' ) ) {
				unset( $rules[ $regex ] );
			}
		}

		return $rules;
	}
}
