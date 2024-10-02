<?php
/**
 * Class file for Limit_Xmlrpc_access
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

final class Limit_Xmlrpc_Access implements Feature {
	public function boot(): void {
		// Init whatever we want to do here.

		// Prior art Copypasta'd from https://github.com/alleyinteractive/national-review/blob/production/mu-plugins/xmlrpc.php.
		// Probably need to pull this into its own function.
		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return false;
		}

		$jetpack_ips = get_transient( 'jetpack_ips' );

		if ( false === $jetpack_ips ) {
			$jetpack_ips = \Mantle\Http_Client\Factory::get( 'https://jetpack.com/ips-v4.json' )->json();

			set_transient(
				'jetpack_ips',
				$jetpack_ips,
				is_array( $jetpack_ips ) ? WEEK_IN_SECONDS : HOUR_IN_SECONDS, // Lower TTL for error in response.
			);
		}

		if ( empty( $jetpack_ips ) || ! is_array( $jetpack_ips ) ) {
			return false;
		}

		if ( IpUtils::checkIp( $_SERVER['REMOTE_ADDR'], $jetpack_ips ) ) {
			return true;
		}

		return isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? IpUtils::checkIp( $_SERVER['HTTP_X_FORWARDED_FOR'], $jetpack_ips ) : false;
	}
}
