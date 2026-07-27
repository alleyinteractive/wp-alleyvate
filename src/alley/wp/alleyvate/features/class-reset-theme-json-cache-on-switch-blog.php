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
 * `WP_Theme_JSON_Resolver` memoizes its merged theme.json data in static
 * properties for the life of the request, and `wp_get_global_settings()` /
 * `wp_get_global_stylesheet()` additionally cache their own return values in
 * the `theme_json` object cache group. None of this is aware of
 * `switch_to_blog()`/`restore_current_blog()`, so anything that switches into
 * another site mid-request (most notably, WordPress core's admin bar building
 * its "My Sites" menu) can prime these caches with a different site's
 * resolved data. If that other site's theme.json differs from the current
 * site's (e.g. a child theme overriding a font-family preset), the current
 * page's global styles render using the wrong site's data.
 */
final class Reset_Theme_Json_Cache_On_Switch_Blog implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'switch_blog', [ $this, 'clean_cached_data' ] );
	}

	/**
	 * Clean WordPress's cached theme.json data so it's recalculated for
	 * whichever site is current after the switch.
	 */
	public function clean_cached_data(): void {
		if ( class_exists( \WP_Theme_JSON_Resolver::class ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}

		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'theme_json' );
		}
	}
}
