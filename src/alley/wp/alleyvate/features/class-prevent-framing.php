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

use Alley\WP\Types\Feature;

/**
 * Headers to prevent iframe-ing of the site.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
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
	 * @param array<string, string> $headers The headers to be sent.
	 * @return array<string, string> The headers to be sent.
	 */
	public static function filter__wp_headers( $headers ): array {
		if ( ! \is_array( $headers ) ) {
			$headers = [];
		}

		/**
		 * Optionally allow the Content-Security-Policy header to be used
		 * instead of X-Frame-Options.
		 *
		 * The Content-Security-Policy header obsoletes the X-Frame-Options
		 * header when used.
		 */
		if ( apply_filters( 'alleyvate_prevent_framing_csp', false ) ) {
			if ( isset( $headers['Content-Security-Policy'] ) ) {
				return $headers;
			}

			$headers['Content-Security-Policy'] = self::get_content_security_policy_header();

			return $headers;
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
		 * The header can return DENY, SAMEORIGIN, or ALLOW-FROM uri.
		 *
		 * @param string $value The value of the X-Frame-Options header. Default SAMEORIGIN.
		 */
		$headers['X-Frame-Options'] = apply_filters( 'alleyvate_prevent_framing_x_frame_options', 'SAMEORIGIN' );

		if ( ! \in_array( $headers['X-Frame-Options'], [ 'DENY', 'SAMEORIGIN' ], true ) && 0 !== strpos( $headers['X-Frame-Options'], 'ALLOW-FROM' ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: The value of the X-Frame-Options header. */
					esc_html__( 'Invalid value for %s. Must be DENY, SAMEORIGIN, or ALLOW-FROM uri.', 'alley' ),
					'X-Frame-Options'
				),
				'2.4.0'
			);
		}

		return $headers;
	}

	/**
	 * Get the Content-Security-Policy header value.
	 *
	 * @return string
	 */
	protected static function get_content_security_policy_header(): string {
		/**
		 * Filter the Content-Security-Policy header ancestors.
		 *
		 * @param array<string> $frame_ancestors The frame ancestors. Default ['\'self\''].
		 */
		$frame_ancestors = apply_filters(
			'alleyvate_prevent_framing_csp_frame_ancestors',
			[
				'\'self\'',
			]
		);

		/**
		 * Filter the value of the Content-Security-Policy header.
		 *
		 * @param string $value The value of the Content-Security-Policy header. Defaults to 'frame-ancestors \'self\''
		 */
		return apply_filters(
			'alleyvate_prevent_framing_csp_header',
			'frame-ancestors ' . implode( ' ', $frame_ancestors )
		);
	}
}
