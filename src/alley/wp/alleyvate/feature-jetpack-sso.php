<?php
/**
 * Jetpack SSO feature
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

add_filter( 'jetpack_active_modules', __NAMESPACE__ . '\\filter__jetpack_active_modules' );
add_filter( 'jetpack_sso_match_by_email', '__return_true' );
add_filter( 'jetpack_sso_require_two_step', '__return_true' );

/**
 * A callback function for the jetpack_active_modules filter.
 *
 * @param string[] $modules An array of module slugs.
 * @return string[] The modified array of module slugs.
 */
function filter__jetpack_active_modules( $modules ) {
	if ( is_array( $modules ) && ! in_array( 'sso', $modules, true ) ) {
		$modules[] = 'sso';
	}

	return $modules;
}
