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

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for Full Page Cache 404 functionality.
 */
final class Test_Full_Page_Cache_404 extends Test_Case {
	use \Mantle\Testing\Concerns\Admin_Screen;

	/**
	 * Feature instance.
	 *
	 * @var Feature
	 */
	private Feature $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->feature = new Full_Page_Cache_404();
	}

	/**
	 * Test full page cache 404.
	 */
	public function test_full_page_cache_404_returns_cache() {
		$this->feature->boot();
		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'alleyvate_404_cache_single' ) > 0 );

		$this->set_404_cache();

		// Expect the cache to be returned.
		$response = $this->get( '/this-is-a-404-page' );
		$response->assertSee( $this->get_404_html() );
		$response->assertStatus( 404 );
	}

	/**
	 * Test that a post request returns the correct content.
	 */
	public function test_full_page_cache_not_returned_for_non_404() {
		$this->feature->boot();
		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$response = $this->get( get_the_permalink( $post_id ) );
		$response->assertHeaderMissing( 'X-Alleyvate-404-Cache' );
		$response->assertSee( 'Hello World' );
	}

	/**
	 * Set the cache.
	 */
	private function set_404_cache() {
		$html = $this->get_404_html();
		$this->feature->set_cache( $html );
	}

	/**
	 * Get the 404 HTML.
	 *
	 * @return string
	 */
	private function get_404_html() {
		return '<html><head>
		<title>404 Not Found</title>
</head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>';
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$this->feature->delete_cache();
		parent::tearDown();
	}
}
