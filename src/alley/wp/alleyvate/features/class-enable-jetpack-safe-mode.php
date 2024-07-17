<?php
/**
 * Class file for Enable_Jetpack_Safe_Mode
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
 * Enables Jetpack safe mode for non-production environments, currently only for Pantheon.
 */
final class Enable_Jetpack_Safe_Mode implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if (
			isset( $_ENV['PANTHEON_ENVIRONMENT'] ) &&
			$_ENV['PANTHEON_ENVIRONMENT'] !== 'live'
		) {
			add_filter( 'jetpack_is_development_site', '__return_true' );
		}
	}
}
