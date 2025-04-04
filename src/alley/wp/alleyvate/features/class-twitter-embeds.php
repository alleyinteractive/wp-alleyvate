<?php
/**
 * Class file for Twitter_Embeds
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;
use WP_Error;

/**
 * Twitter_Embeds feature.
 */
final class Twitter_Embeds implements Feature {
	/**
	 * Array of attempts to catch 404 responses from Twitter.
	 *
	 * @var int[] $attempts
	 */
	private array $attempts = [];

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		/*
		 * Use `wp_oembed_add_provider()` to avoid timing issues with the `oembed_providers` filter.
		 * See https://github.com/alleyinteractive/wp-alleyvate/issues/133.
		 */
		wp_oembed_add_provider( '#https?://(www\.)?x\.com/\w{1,15}/status(es)?/.*#i', 'https://publish.twitter.com/oembed', true );
		wp_oembed_add_provider( '#https?://(www\.)?x\.com/\w{1,15}$#i', 'https://publish.twitter.com/oembed', true );
		wp_oembed_add_provider( '#https?://(www\.)?x\.com/\w{1,15}/likes$#i', 'https://publish.twitter.com/oembed', true );
		wp_oembed_add_provider( '#https?://(www\.)?x\.com/\w{1,15}/lists/.*#i', 'https://publish.twitter.com/oembed', true );
		wp_oembed_add_provider( '#https?://(www\.)?x\.com/\w{1,15}/timelines/.*#i', 'https://publish.twitter.com/oembed', true );
		wp_oembed_add_provider( '#https?://(www\.)?x\.com/i/moments/.*#i', 'https://publish.twitter.com/oembed', true );

		add_filter( 'http_response', [ $this, 'filter_twitter_oembed_404s' ], 10, 3 );
		add_filter( 'alleyvate_twitter_embeds_404_backstop', [ $this, 'attempt_404_backstop' ], 10, 3 );
	}

	/**
	 * Attempt to catch 404 responses from Twitter.
	 *
	 * @param array<mixed>|WP_Error $response    HTTP response.
	 * @param array<mixed>          $parsed_args HTTP request arguments.
	 * @param string                $url         URL of the HTTP request.
	 * @return array<mixed>|WP_Error
	 */
	public function filter_twitter_oembed_404s( array|WP_Error $response, array $parsed_args, string $url ): array|WP_Error {
		if (
			strpos( $url, 'publish.twitter.com' ) !== false
			&& 404 === wp_remote_retrieve_response_code( $response )
		) {
			$this->attempts[ $url ] = ( $this->attempts[ $url ] ?? 0 ) + 1;

			/**
			 * Filter the response for a 404 from Twitter.
			 *
			 * @param array  $response    HTTP response.
			 * @param string $url         URL of the HTTP request.
			 * @param int    $attempts    Number of times this filter has fired for this URL during this request.
			 * @param array  $parsed_args HTTP request arguments.
			 */
			return apply_filters( /* @phpstan-ignore parameter.phpDocType */
				'alleyvate_twitter_embeds_404_backstop',
				$response,
				$url,
				$this->attempts[ $url ],
				$parsed_args
			);
		}

		return $response;
	}

	/**
	 * Attempt to catch 404 responses from Twitter.
	 *
	 * @param array<mixed> $response HTTP response.
	 * @param string       $url      URL of the HTTP request.
	 * @param int          $attempts Number of times this filter has fired for this URL during this request.
	 * @return array<mixed>
	 */
	public function attempt_404_backstop( array $response, string $url, int $attempts ): array|WP_Error {
		if ( 1 === $attempts ) {
			$env_endpoint = \function_exists( 'vip_get_env_var' )
				? vip_get_env_var( 'TWITTER_OEMBED_BACKSTOP_ENDPOINT' )
				: getenv( 'TWITTER_OEMBED_BACKSTOP_ENDPOINT' );
			if ( $env_endpoint ) {
				// If there's a backstop endpoint defined, attempt to get the oembed from there.
				$url      = str_replace( 'https://publish.twitter.com/oembed', $env_endpoint, $url );
				$backstop = wp_safe_remote_get( $url );
				if ( 200 === wp_remote_retrieve_response_code( $backstop ) ) {
					return $backstop;
				}
			}
		}
		return $response;
	}
}
