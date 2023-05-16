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
 * Load plugin features.
 */
function load(): void {
	// Bail if the Alleyvate feature interface isn't loaded to prevent a fatal error.
	if ( ! interface_exists( Feature::class ) ) {
		return;
	}

	/**
	 * Features to load.
	 *
	 * @var Feature[] $features
	 */
	$features = [
		'redirect_guess_shortcircuit'   => new Features\Redirect_Guess_Shortcircuit(),
		'user_enumeration_restrictions' => new Features\User_Enumeration_Restrictions(),
	];

	foreach ( $features as $handle => $feature ) {
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

		if ( $load ) {
			$feature->boot();
		}
	}
}
