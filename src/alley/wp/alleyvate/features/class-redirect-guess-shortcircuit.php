<?php
/**
 * Class file for Redirect_Guess_Shortcircuit
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
 * Disable `redirect_guess_404_permalink()`, whose behavior often confuses clients
 * and is non-performant on larger sites.
 */
final class Redirect_Guess_Shortcircuit extends Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
	}
}
