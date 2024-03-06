<?php
/**
 * Class file for Remove_Shortlink
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
 * Remove the shortlink link tag from the head of pages.
 */
final class Remove_Shortlink implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	}
}
