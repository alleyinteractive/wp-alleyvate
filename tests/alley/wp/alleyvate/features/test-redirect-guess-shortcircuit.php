<?php
/**
 * Class file for Test_Redirect_Guess_Shortcircuit
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
use Mantle\Testkit\Test_Case;

/**
 * Tests for short-circuiting the redirect URL guessing for 404 requests.
 */
final class Test_Redirect_Guess_Shortcircuit extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Redirect_Guess_Shortcircuit
	 */
	private Redirect_Guess_Shortcircuit $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Redirect_Guess_Shortcircuit();
	}

	/**
	 * Test that the feature short-circuits a redirect that would otherwise occur.
	 */
	public function test_no_redirect_guess(): void {
		$post_name = 'foo';
		$actual    = self::factory()->post->create(
			[
				'post_name' => $post_name,
				'post_type' => 'post',
			],
		);

		// Correct post name, incorrect post type.
		$this->get(
			add_query_arg(
				[
					'name'      => $post_name,
					'post_type' => 'page',
				],
				'/',
			),
		);

		// Redirect should be guessed before the feature is booted, but not after.
		$this->assertSame( get_permalink( $actual ), redirect_guess_404_permalink() );
		$this->feature->boot();
		$this->assertFalse( redirect_guess_404_permalink() );
	}
}
