<?php
/**
 * Class file for Disable_Custom_Fields_Meta_Box
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
 * Disable the custom fields meta box.
 */
final class Disable_Custom_Fields_Meta_Box implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'add_meta_boxes', [ self::class, 'action__add_meta_boxes' ], 9999 );
	}

	/**
	 * Remove the "Custom Fields" meta box.
	 *
	 * It generates an expensive query and is almost never used in practice.
	 */
	public static function action__add_meta_boxes(): void {
		remove_meta_box( 'postcustom', null, 'normal' );
	}
}
