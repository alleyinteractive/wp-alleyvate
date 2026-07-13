<?php
/**
 * Class file for Disable_Script_Style_Concatenation
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
 * Disables the script and style concatenation (and CSS minification) that
 * WordPress VIP enables by default, which produces single, large, rarely
 * cacheable bundles that hurt performance on HTTP/2.
 */
final class Disable_Script_Style_Concatenation implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'js_do_concat', '__return_false' );
		add_filter( 'css_do_concat', '__return_false' );
	}
}
