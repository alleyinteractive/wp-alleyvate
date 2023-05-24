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

use Alley\WP\Alleyvate\Feature;

/**
 * Fully disables pingbacks and trackbacks.
 */
final class Disable_Trackbacks implements Feature {
	/**
	 * A callback for the init action hook.
	 */
	public static function action__init(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}

			// The REST API filters don't have a generic form, so they need to be registered for each post type.
			add_filter( "rest_prepare_{$post_type}", [ self::class, 'filter__rest_prepare' ], \PHP_INT_MAX );
		}
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', [ self::class, 'action__init' ], \PHP_INT_MAX );
		add_filter( 'pings_open', '__return_false', \PHP_INT_MAX );
	}

	/**
	 * A callback for the rest_prepare_{$post_type} filter hook.
	 *
	 * @param \WP_REST_Response $response Response to filter.
	 *
	 * @return \WP_REST_Response Filtered response.
	 */
	public static function filter__rest_prepare( \WP_REST_Response $response ): \WP_REST_Response {
		if ( isset( $response->data['ping_status'] ) ) {
			$response->data['ping_status'] = 'closed';
		}

		return $response;
	}
}
