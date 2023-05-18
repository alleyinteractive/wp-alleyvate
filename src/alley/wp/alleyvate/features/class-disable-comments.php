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
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'admin_menu', [ self::class, 'action__admin_menu' ], \PHP_INT_MAX );
		add_action( 'init', [ self::class, 'action__init' ], \PHP_INT_MAX );
		add_filter( 'comments_open', '__return_false', \PHP_INT_MAX );
		add_filter( 'comments_pre_query', '__return_empty_array', \PHP_INT_MAX );
	}
}
