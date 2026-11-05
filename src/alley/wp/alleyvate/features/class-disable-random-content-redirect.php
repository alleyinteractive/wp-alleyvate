<?php
/**
 * Class file for Disable_Random_Content_Redirect
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
 * Disable the `/random/` endpoint introduced in WordPress 7.2.
 */
final class Disable_Random_Content_Redirect implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'wp_enable_random_content_redirect', '__return_false' );
	}
}
