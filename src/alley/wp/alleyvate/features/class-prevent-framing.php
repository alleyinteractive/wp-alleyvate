<?php
/**
 * Class file for Prevent_Framing
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

/**
 * Headers to prevent iframing of the site.
 */
final class Prevent_Framing implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'wp_headers', [ self::class, 'filter__wp_headers' ] );
	}

	/**
	 * Output the X-Frame-Options header to prevent sites from being able to be iframe'd.
	 *
	 * @param array $headers The headers to be sent.
	 * @return array The headers to be sent.
	 */
	public static function filter__wp_headers( $headers ): array {
		if ( ! \is_array( $headers ) ) {
			$headers = [];
		}

		if ( isset( $headers['X-Frame-Options'] ) ) {
			return $headers;
		}

		/**
		 * Allow the X-Frame-Options header to be disabled.
		 *
		 * @param bool $prevent_framing Whether to prevent framing. Default false.
		 */
		if ( apply_filters( 'alleyvate_prevent_framing_disable', false ) ) {
			return $headers;
		}

		/**
		 * Filter the X-Frame-Options header value.
		 *
		 * @param string $value The value of the X-Frame-Options header.
		 */
		$headers['X-Frame-Options'] = apply_filters( 'alleyvate_prevent_framing_x_frame_options', 'SAMEORIGIN' );

		return $headers;
	}
}
