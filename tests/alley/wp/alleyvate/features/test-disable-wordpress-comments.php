<?php
/**
 * Class file for Disable_WordPress_Comments
 *
 * (c) Alley <info@alley.com>
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Disable WordPress comments.
 */
final class Test_Disable_WordPress_Comments extends Test_Case {

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

		$this->feature = new Disable_WordPress_Comments();
	}

	/**
	 * Test that the feature disables comments.
	 *
	 * @return void
	 */
	public function test_disable_wordpress_comments() {
		$this->feature->boot();
		$this->assertFalse( comments_open() );
		$this->assertFalse( pings_open() );
		// test that comments are disabled on posts.
		$post_id = self::factory()->post->create();
		$this->assertFalse( comments_open( $post_id ) );
		$this->assertFalse( pings_open( $post_id ) );
	}
}
