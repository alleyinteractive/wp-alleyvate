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
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'comments_open', '__return_false', PHP_INT_MAX );
	}
}
