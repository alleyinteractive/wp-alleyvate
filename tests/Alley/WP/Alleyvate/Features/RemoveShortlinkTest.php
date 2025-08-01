<?php
/**
 * Class file for Test_Remove_Shortlink
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
 * Tests for removing the shortlink link tag from the head of pages.
 */
final class RemoveShortlinkTest extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Remove_Shortlink
	 */
	private Remove_Shortlink $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Remove_Shortlink();
	}

	/**
	 * Test that the feature removes the shortlink link tag from the head of pages.
	 */
	public function test_boot(): void {
		$post = self::factory()->post->create_and_get();

		$this->get( $post )->assertElementExists( 'head/link[@rel="shortlink"]' );

		$this->feature->boot();

		$this->get( $post )->assertElementMissing( 'head/link[@rel="shortlink"]' );
	}
}
