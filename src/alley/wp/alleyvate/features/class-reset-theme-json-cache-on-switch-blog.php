<?php
/**
 * Class file for Reset_Theme_Json_Cache_On_Switch_Blog
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
 * Resets WordPress's cached theme.json data whenever the current site changes
 * in a multisite request.
 *
 * WordPress core already hooks its own `wp_clean_theme_json_cache()` (which
 * resets `WP_Theme_JSON_Resolver`'s static cache along with every known
 * `theme_json` object cache key) to the `switch_theme` and
 * `start_previewing_theme` actions, precisely because the active theme
 * context changing mid-request can otherwise leave these caches stale. It
 * isn't hooked to `switch_blog`, though, so a multisite network's admin bar
 * building its "My Sites" menu (which switches into every site the current
 * user belongs to before the current page's own global styles are
 * generated) can prime these caches with a different site's resolved data.
 * If that other site's theme.json differs from the current site's (e.g. a
 * child theme overriding a font-family preset), the current page's global
 * styles render using the wrong site's data.
 */
final class Reset_Theme_Json_Cache_On_Switch_Blog implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'switch_blog', 'wp_clean_theme_json_cache' );
	}
}
