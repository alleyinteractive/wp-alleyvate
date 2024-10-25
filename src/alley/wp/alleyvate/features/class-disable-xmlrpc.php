<?php
/**
 * Class file for Disable_XMLRPC.
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Symfony\Component\HttpFoundation\IpUtils;
use Alley\WP\Types\Feature;

/**
 * Disables XMLRPC requests and methods for all requests except those coming from known Jetpack IPs.
 */
final class Disable_XMLRPC implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {

		// Disable XML-RPC for non-Jetpack requests.
		add_filter(
			'xmlrpc_enabled',
			fn( $enabled ) => self::is_jetpack_enabled() && self::is_jetpack_xmlrpc_request() ? $enabled : false,
			PHP_INT_MAX,
		);

		// Remove all XML-RPC methods for non-Jetpack requests.
		add_filter(
			'xmlrpc_methods',
			fn( $methods ) => self::is_jetpack_enabled() && self::is_jetpack_xmlrpc_request() ? $methods : [],
			PHP_INT_MAX,
		);
	}

	/**
	 * Determine if Jetpack is enabled.
	 *
	 * @return bool
	 */
	public static function is_jetpack_enabled(): bool {
		return defined( 'JETPACK__VERSION' );
	}

	/**
	 * Determine if the current request is a Jetpack XML-RPC request.
	 *
	 * @return bool
	 */
	public static function is_jetpack_xmlrpc_request(): bool {
		// Bail if there's no remote address.
		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) { // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders
			return false;
		}

		// Get Jetpack IPs.
		$jetpack_ips = self::get_jetpack_ips();

		// Bail if we don't have any Jetpack IPs.
		if ( empty( $jetpack_ips ) ) {
			return false;
		}

		// Check if the request is from a Jetpack IP.
		// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		if ( IpUtils::checkIp( $_SERVER['REMOTE_ADDR'], $jetpack_ips ) ) {
			return true;
		}

		// Check if the request is from a forwarded Jetpack IP.
		// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		return isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && IpUtils::checkIp( $_SERVER['HTTP_X_FORWARDED_FOR'], $jetpack_ips );
	}

	/**
	 * Get the Jetpack IPs.
	 *
	 * @return array
	 */
	public static function get_jetpack_ips(): array {
		// Look for cache.
		$jetpack_ips = wp_cache_get( 'jetpack_ips', 'alleyvate_disable_xmlrpc' );

		// If there's no cache, fetch the Jetpack IPs.
		if ( empty( $jetpack_ips ) ) {
			$response = wp_safe_remote_get( 'https://jetpack.com/ips-v4.json' );
			if ( ! is_wp_error( $response ) ) {
				// Ensure good response.
				if ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
					$body        = wp_remote_retrieve_body( $response );
					$jetpack_ips = json_decode( $body, true );

					// Update cache.
					wp_cache_set(
						'jetpack_ips',
						$jetpack_ips,
						'alleyvate_disable_xmlrpc',
						is_array( $jetpack_ips ) ? WEEK_IN_SECONDS : HOUR_IN_SECONDS // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
					);

					return ( is_array( $jetpack_ips ) && ! empty( $jetpack_ips ) ) ? $jetpack_ips : [];
				}
			} else {
				// cache the "bad result" for a short time to avoid hammering the jetpack endpoint.
				wp_cache_set( 'jetpack_ips', [], 'alleyvate_disable_xmlrpc', HOUR_IN_SECONDS );
			}
		}

		return [];
	}
}
