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
		}
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', [ self::class, 'action__init' ], \PHP_INT_MAX );
		add_filter( 'pings_open', '__return_false', \PHP_INT_MAX );
	}
}
