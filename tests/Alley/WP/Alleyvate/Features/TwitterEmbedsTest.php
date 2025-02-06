<?php
/**
 * Class file for Test_Twitter_Embeds
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

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testkit\Test_Case;

use function getenv;
use function Mantle\Testing\mock_http_sequence;
use function putenv;

/**
 * Tests for Twitter_Embeds feature.
 */
final class TwitterEmbedsTest extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Twitter_Embeds
	 */
	private Twitter_Embeds $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Twitter_Embeds();
		if ( getenv( 'TWITTER_OEMBED_BACKSTOP_ENDPOINT' ) ) {
			throw new \Exception( 'Environment variable TWITTER_OEMBED_BACKSTOP_ENDPOINT is set and should not be.' );
		}
	}

	protected function tearDown(): void {
		putenv( 'TWITTER_OEMBED_BACKSTOP_ENDPOINT' );

		parent::tearDown();
	}

	protected function fake_oembed_request( int $response_code = 404 ) {
//		$body = '';
//		if ( 200 === $response_code ) {
//			$body = '{"url":"https:\/\/twitter.com\/WordPress\/status\/1819377181035745510","author_name":"WordPress","author_url":"https:\/\/twitter.com\/WordPress","html":"\u003Cblockquote class=\"twitter-tweet\" data-width=\"550\" data-dnt=\"true\"\u003E\u003Cp lang=\"en\" dir=\"ltr\"\u003EMeet the brand-new, reimagined Learn WordPress experience and grow your WordPress skills at your own pace. Get more details: \u003Ca href=\"https:\/\/t.co\/6bj2bRr8BW\"\u003Ehttps:\/\/t.co\/6bj2bRr8BW\u003C\/a\u003E \u003Ca href=\"https:\/\/twitter.com\/hashtag\/WordPress?src=hash&amp;ref_src=twsrc%5Etfw\"\u003E#WordPress\u003C\/a\u003E \u003Ca href=\"https:\/\/t.co\/24TkZaB6pW\"\u003Epic.twitter.com\/24TkZaB6pW\u003C\/a\u003E\u003C\/p\u003E&mdash; WordPress (@WordPress) \u003Ca href=\"https:\/\/twitter.com\/WordPress\/status\/1819377181035745510?ref_src=twsrc%5Etfw\"\u003EAugust 2, 2024\u003C\/a\u003E\u003C\/blockquote\u003E\n\u003Cscript async src=\"https:\/\/platform.twitter.com\/widgets.js\" charset=\"utf-8\"\u003E\u003C\/script\u003E\n\n","width":550,"height":null,"type":"rich","cache_age":"3153600000","provider_name":"Twitter","provider_url":"https:\/\/twitter.com","version":"1.0"}';
//		} elseif ( 404 === $response_code ) {
//			$body = '<!DOCTYPE html>\n<html lang="en" class="dog">\n<head>\n<title>X / ?</title>\n</head>\n<body>\n<h1 id="header">Nothing to see here</h1>\n</body>\n</html>';
//		}
//		$this->fake_request( 'https://publish.twitter.com/oembed' )
//			->with_response_code( $response_code )
//			->with_body( $body );
		$this->fake_request( [
			'https://publish.twitter.com/oembed' => mock_http_sequence()
				->push_status( 404 )
				->push_status( 200 )
		] );
	}

	public function test_default_backstop() {
		$this->feature->boot();
		$url = 'https://publish.twitter.com/oembed?format=json&url=https%3A%2F%2Ftwitter.com%2FWordPress%2Fstatus%2F1819377181035745510';

		// Fire the filter with a 404 response and verify that the default backstop executes.
		$this->fake_request( $url );
		apply_filters(
			'http_response',
			Mock_Http_Response::create()->with_response_code( 404 )->to_array(),
			[],
			$url
		);
		$this->assertRequestSent( $url );
	}

	public function test_backstop_through_env() {
		$this->feature->boot();
		putenv( 'TWITTER_OEMBED_BACKSTOP_ENDPOINT=https://example.com' );

		$url = 'https://example.com?format=json&url=https%3A%2F%2Ftwitter.com%2FWordPress%2Fstatus%2F1819377181035745510';
		$og_url = 'https://publish.twitter.com/oembed?format=json&url=https%3A%2F%2Ftwitter.com%2FWordPress%2Fstatus%2F1819377181035745510';

		// Fire the filter with a 404 response and verify that the default backstop executes.
		$this->fake_request( [
			$url => new Mock_Http_Response(),
			$og_url => new Mock_Http_Response(),
		] );
		apply_filters(
			'http_response',
			Mock_Http_Response::create()->with_response_code( 404 )->to_array(),
			[],
			$og_url
		);
		putenv( 'TWITTER_OEMBED_BACKSTOP_ENDPOINT' );

		$this->assertRequestSent( $url );
		$this->assertRequestNotSent( $og_url );
	}

	public function test_oembed_providers() {
		$body = '{"url":"https:\/\/twitter.com\/WordPress\/status\/1819377181035745510","author_name":"WordPress","author_url":"https:\/\/twitter.com\/WordPress","html":"\u003Cblockquote class=\"twitter-tweet\" data-width=\"550\" data-dnt=\"true\"\u003E\u003Cp lang=\"en\" dir=\"ltr\"\u003EMeet the brand-new, reimagined Learn WordPress experience and grow your WordPress skills at your own pace. Get more details: \u003Ca href=\"https:\/\/t.co\/6bj2bRr8BW\"\u003Ehttps:\/\/t.co\/6bj2bRr8BW\u003C\/a\u003E \u003Ca href=\"https:\/\/twitter.com\/hashtag\/WordPress?src=hash&amp;ref_src=twsrc%5Etfw\"\u003E#WordPress\u003C\/a\u003E \u003Ca href=\"https:\/\/t.co\/24TkZaB6pW\"\u003Epic.twitter.com\/24TkZaB6pW\u003C\/a\u003E\u003C\/p\u003E&mdash; WordPress (@WordPress) \u003Ca href=\"https:\/\/twitter.com\/WordPress\/status\/1819377181035745510?ref_src=twsrc%5Etfw\"\u003EAugust 2, 2024\u003C\/a\u003E\u003C\/blockquote\u003E\n\u003Cscript async src=\"https:\/\/platform.twitter.com\/widgets.js\" charset=\"utf-8\"\u003E\u003C\/script\u003E\n\n","width":550,"height":null,"type":"rich","cache_age":"3153600000","provider_name":"Twitter","provider_url":"https:\/\/twitter.com","version":"1.0"}';
		$this->fake_request( 'https://publish.twitter.com/oembed*' )
			->with_body( $body );
		$this->fake_request( 'https://x.com/WordPress/status/1868689630931059186' );

		$this->assertFalse( wp_oembed_get('https://x.com/WordPress/status/1868689630931059186') );

		$this->feature->boot();

		// Kids, don't try this at home. Because _wp_oembed_get_object() stores a static reference to the WP_oEmbed
		// object, we rerun the constructor to ensure that the filtered oEmbed providers are loaded.
		$wp_oembed = _wp_oembed_get_object();
		$wp_oembed->__construct();

		$response = wp_oembed_get('https://x.com/WordPress/status/1819377181035745510');

		$this->assertNotFalse($response);
		$this->assertMatchesSnapshot($response);
	}
}
