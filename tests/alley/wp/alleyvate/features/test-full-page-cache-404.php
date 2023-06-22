<?php
/**
 * Class file for Test_Disable_Comments
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
 * Tests for fully disabling comment functionality.
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
	 *
	 */
	public function test_full_page_cache_404_returns_cache() {
		$this->feature->boot();
		$response = $this->get( '/this-is-a-404-page' );
		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );
		$html = '<html>404</html>';
		$this->feature->set_cache( $html );
		$response = $this->get( '/this-is-a-404-page' );
		$response->assertSee( $html );
		$response->assertStatus( 404 );
	}


	/**
	 * Test
	 *
	 */
	public function test_full_page_cache_not_returned_for_non_404() {
		$this->feature->boot();
		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$response = $this->get( get_the_permalink( $post_id ) );
		$response->assertHeaderMissing( 'X-Alleyvate-404-Cache' );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$this->feature->delete_cache();
		add_action( 'send_headers', [ $this->feature, 'action__send_headers' ] );
		parent::tearDown();
	}

}
