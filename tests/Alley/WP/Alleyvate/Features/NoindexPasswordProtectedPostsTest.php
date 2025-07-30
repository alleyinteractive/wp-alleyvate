<?php
/**
 * Class file for Noindex_Password_protected_Posts
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare(strict_types=1);

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

/**
 * Tests for adding noindex to the robots meta tag content for password-protected posts.
 */
final class NoindexPasswordProtectedPostsTest extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Noindex_Password_Protected_Posts
	 */
	private Noindex_Password_Protected_Posts $feature;

	/**
	 * Test that the feature adds a noindex directive to password protected posts.
	 */
	public function test_boot(): void {
		$post = self::factory()->post->create_and_get(
			[
				'post_password' => 'password',
			]
		);

		$this->get( $post )->assertElementMissing( 'head/meta[@name="robots" and contains(@content, "noindex")]' );

		$this->feature->boot();

		$this->get( $post )->assertElementExists( 'head/meta[@name="robots" and contains(@content, "noindex")]' );
	}

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Noindex_Password_Protected_Posts();
	}
}
