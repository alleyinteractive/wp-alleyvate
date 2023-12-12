<?php
/**
 * `load()` function
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

/**
 * Get the available features to load.
 *
 * @return array<string, Feature>
 */
function available_features(): array {
	return [
		'clean_admin_bar'               => new Features\Clean_Admin_Bar(),
		'disable_comments'              => new Features\Disable_Comments(),
		'disable_dashboard_widgets'     => new Features\Disable_Dashboard_Widgets(),
		'disable_sticky_posts'          => new Features\Disable_Sticky_Posts(),
		'disable_trackbacks'            => new Features\Disable_Trackbacks(),
		'disallow_file_edit'            => new Features\Disallow_File_Edit(),
		'login_nonce'                   => new Features\Login_Nonce(),
		'redirect_guess_shortcircuit'   => new Features\Redirect_Guess_Shortcircuit(),
		'site_health'                   => new Features\Site_Health(),
		'user_enumeration_restrictions' => new Features\User_Enumeration_Restrictions(),
	];
}

/**
 * Determine whether to load a feature.
 *
 * @param string $handle Feature handle.
 * @return bool
 */
function should_load_feature( string $handle ): bool {
	$load = true;

	/**
	 * Filters whether to load an Alleyvate feature.
	 *
	 * @param bool   $load   Whether to load the feature. Default true.
	 * @param string $handle Feature handle.
	 */
	$load = apply_filters( 'alleyvate_load_feature', $load, $handle );

	/**
	 * Filters whether to load the given Alleyvate feature.
	 *
	 * The dynamic portion of the hook name, `$handle`, refers to the
	 * machine name for the feature.
	 *
	 * @param bool $load Whether to load the feature. Default true.
	 */
	$load = apply_filters( "alleyvate_load_{$handle}", $load );

	return (bool) $load;
}

/**
 * Load plugin features.
 */
function load(): void {
	// Bail if the Alleyvate feature interface isn't loaded to prevent a fatal error.
	if ( ! interface_exists( Feature::class ) ) {
		return;
	}

	foreach ( available_features() as $handle => $feature ) {
		if ( should_load_feature( $handle ) ) {
			$feature->boot();
		}
	}
}
