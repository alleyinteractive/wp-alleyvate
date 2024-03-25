<?php
/**
 * Class file for Disable_Pantheon_Constant_Overrides
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
 * Disallow the WP_SITEURL and WP_HOME constants from overriding the option
 * value on Cron or CLI runs.
 */
final class Disable_Pantheon_Constant_Overrides implements Feature {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if (
			isset( $_ENV['PANTHEON_ENVIRONMENT'] ) &&
			(
				( defined( 'WP_CLI' ) && WP_CLI ) ||
				( defined( 'DOING_CRON' ) && DOING_CRON )
			)
		) {
			remove_filter( 'option_siteurl', '_config_wp_siteurl' );
			remove_filter( 'option_home', '_config_wp_home' );
		}
	}
}
