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

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;
use WP_Error;
use WpOrg\Requests\Transport\Fsockopen;

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
		add_filter( 'oembed_providers', [ $this, 'add_twitter_oembed_provider' ] );
		add_filter( 'http_response', [ $this, 'filter_twitter_oembed_404s' ], 10, 3 );
		add_filter( 'alleyvate_twitter_embeds_404_backstop', [ $this, 'attempt_404_backstop' ], 10, 3 );
	}

	/**
	 * Add Twitter oEmbed provider.
	 *
	 * @param array{string, boolean}[] $providers Array of oEmbed providers.
	 * @return array{string, boolean}[]
	 */
	public function add_twitter_oembed_provider( array $providers ): array {
		/* phpcs:disable WordPress.Arrays.MultipleStatementAlignment */
		return array_merge(
			$providers,
			[
				'#https?://(www\.)?x\.com/\w{1,15}/status(es)?/.*#i' => [ 'https://publish.twitter.com/oembed', true ],
				'#https?://(www\.)?x\.com/\w{1,15}$#i'               => [ 'https://publish.twitter.com/oembed', true ],
				'#https?://(www\.)?x\.com/\w{1,15}/likes$#i'         => [ 'https://publish.twitter.com/oembed', true ],
				'#https?://(www\.)?x\.com/\w{1,15}/lists/.*#i'       => [ 'https://publish.twitter.com/oembed', true ],
				'#https?://(www\.)?x\.com/\w{1,15}/timelines/.*#i'   => [ 'https://publish.twitter.com/oembed', true ],
				'#https?://(www\.)?x\.com/i/moments/.*#i'            => [ 'https://publish.twitter.com/oembed', true ],
			],
		);
		/* phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned */
	}

	/**
	 * Attempt to catch 404 responses from Twitter.
	 *
	 * @param array<mixed> $response    HTTP response.
	 * @param array<mixed> $parsed_args HTTP request arguments.
	 * @param string       $url         URL of the HTTP request.
	 * @return array<mixed>
	 */
	public function filter_twitter_oembed_404s( array $response, array $parsed_args, string $url ): array {
		if (
			strpos( $url, 'publish.twitter.com' ) !== false
			&& 404 === $response['response']['code'] /* @phpstan-ignore offsetAccess.nonOffsetAccessible */
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
			return apply_filters(
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
				// If there's a backstop endpoint defined, use it.
				$url      = str_replace( 'https://publish.twitter.com/oembed', $env_endpoint, $url );
				$response = wp_safe_remote_get( $url );
			} else {
				// Attempt the request again using the fsockopen transport, which might get a different outcome.
				add_action( 'requests-requests.before_request', [ $this, 'attempt_fsockopen_for_twitter_oembeds' ], 10, 5 );
				$response = wp_safe_remote_get( $url );
				remove_action( 'requests-requests.before_request', [ $this, 'attempt_fsockopen_for_twitter_oembeds' ] );
			}
		}
		return $response;
	}

	/**
	 * Attempt to use fsockopen transport for Twitter oEmbeds.
	 *
	 * @param string        $url     URL of the HTTP request.
	 * @param array<string> $headers HTTP request headers. Ignored.
	 * @param array<mixed>  $data    HTTP request data. Ignored.
	 * @param string        $type    HTTP request type. Ignored.
	 * @param array<mixed>  $options HTTP request options. Passed by reference.
	 * @return void
	 */
	public function attempt_fsockopen_for_twitter_oembeds( &$url, &$headers, &$data, &$type, &$options ) {
		if ( class_exists( Fsockopen::class ) && str_starts_with( $url, 'https://publish.twitter.com/oembed' ) ) {
			$options['transport'] = Fsockopen::class;
		}
	}
}
