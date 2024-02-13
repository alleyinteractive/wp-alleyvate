<?php
/**
 * Class file for Test_Full_Page_Cache_404
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

use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

/**
 * Tests for Full Page Cache 404 functionality.
 */
final class Test_Full_Page_Cache_404 extends Test_Case {
	use Refresh_Database;
	use Admin_Screen;

	/**
	 * Feature instance.
	 *
	 * @var Full_Page_Cache_404
	 */
	private Full_Page_Cache_404 $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Full_Page_Cache_404();

		$this->prevent_stray_requests();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->feature::delete_cache();
	}

	/**
	 * Test full page cache 404.
	 */
	public function test_full_page_cache_404_returns_cache(): void {
		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'alleyvate_404_cache_single' ) > 0 );

		add_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );

		// Expect the cache to be returned.
		$response = $this->get( '/this-is-a-404-page' );
		$response->assertSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		remove_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );
	}

	/**
	 * Test full page cache 404 does not return cache for logged in user.
	 */
	public function test_full_page_cache_404_does_not_return_cache_for_logged_in_user(): void {
		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'alleyvate_404_cache_single' ) > 0 );

		add_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );

		// Expect the cache NOT be returned for logged in user.
		$this->acting_as( self::factory()->user->create() );
		$this->assertAuthenticated();

		// Expect the cache to be returned.
		$response = $this->get( '/this-is-a-404-page' );
		$response->assertDontSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		remove_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );
	}

	/**
	 * Test full page cache 404 does not return cache for generator URI.
	 */
	public function test_full_page_cache_404_does_not_return_cache_for_generator_uri(): void {
		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertNoContent( 404 );

		// Hit the generator URI to populate the cache.
		$response = $this->get( '/wp-alleyvate/404-template-generator/?generate=1&uri=1' );
		$response->assertDontSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		// Pretend to update the cache.
		add_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		remove_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );
	}

	/**
	 * Test that the 404 cache is not returned for non-404 pages.
	 */
	public function test_full_page_cache_not_returned_for_non_404(): void {
		$this->feature->boot();

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$response = $this->get( get_the_permalink( $post_id ) );
		$response->assertStatus( 200 );
		$response->assertHeaderMissing( 'X-Alleyvate-404-Cache' );
		$response->assertSee( 'Hello World' );

		// Expect cron job is not scheduled.
		$this->assertFalse( wp_next_scheduled( 'alleyvate_404_cache_single' ) > 0 );
	}

	/**
	 * Test that the content manipulation works.
	 */
	public function test_full_page_cacge_prepare_content(): void {
		$raw_html               = $this->get_404_html();
		$_SERVER['REQUEST_URI'] = '/news/breaking_story/?_ga=2.123456789.123456789.123456789.123456789&_gl=1*123456789*123456789*123456789*1';

// phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
$expected_html = <<<HTML
    <html>
    <head>
    	<title>404 Not Found</title>
    	<script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({"pagename":"\/news\/breaking_story\/?_ga=2.123456789.123456789.123456789.123456789&_gl=1*123456789*123456789*123456789*1"});
    	</script>
    </head>
    <body>
    	<h1>404 Not Found</h1>
    	<p>The <a href="/news/breaking_story/?_ga=2.123456789.123456789.123456789.123456789&#038;_gl=1*123456789*123456789*123456789*1">requested URL</a> was not found on this server.</p>
    	<p>This test includes different ways the URI may be output in the content. Above shows the use of esc_url and
    	wp_json_encode.</p>
    	<p>So that we can do content aware replacement of the URI for security and analytics reporting.</p>
    	<p>esc_html would output: /news/breaking_story/?_ga=2.123456789.123456789.123456789.123456789&amp;_gl=1*123456789*123456789*123456789*1</p>
    </body>
    </html>
    HTML;
		$this->assertEquals( $expected_html, $this->feature::prepare_response( $raw_html ) );
	}

	/**
	 * Test full page cache 404 cron.
	 */
	public function test_full_page_cache_404_cron(): void {
		$this->fake_request( 'https://example.org/*' )
			->with_response_code( 400 );

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'alleyvate_404_cache_single' ) > 0 );

		// Run the cron job.
		do_action( 'alleyvate_404_cache' );

		// This is a hourly cron job, so we expect it to be scheduled again.
		$this->assertTrue( wp_next_scheduled( 'alleyvate_404_cache_single' ) > 0 );
	}

	/**
	 * Set the cache.
	 */
	public function set_404_cache(): void {
		$this->feature::set_cache( $this->get_404_html() );
	}

	/**
	 * Get the 404 HTML.
	 *
	 * @return string
	 */
	private function get_404_html(): string {
		// phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
return <<<HTML
    <html>
    <head>
    	<title>404 Not Found</title>
    	<script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({"pagename":"\/wp-alleyvate\/404-template-generator\/?generate=1&uri=1"});
    	</script>
    </head>
    <body>
    	<h1>404 Not Found</h1>
    	<p>The <a href="/wp-alleyvate/404-template-generator/?generate=1&#038;uri=1">requested URL</a> was not found on this server.</p>
    	<p>This test includes different ways the URI may be output in the content. Above shows the use of esc_url and
    	wp_json_encode.</p>
    	<p>So that we can do content aware replacement of the URI for security and analytics reporting.</p>
    	<p>esc_html would output: /wp-alleyvate/404-template-generator/?generate=1&amp;uri=1</p>
    </body>
    </html>
    HTML;
	}
}
