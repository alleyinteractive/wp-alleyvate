<?php
/**
 * Class file for DisableRandomContentRedirectTest
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

use Mantle\Testkit\Test_Case;

/**
 * Tests for disabling the random content redirect.
 */
final class DisableRandomContentRedirectTest extends Test_Case {
	/**
	 * Test that the feature turns off the random content redirect.
	 */
	public function test_boot(): void {
		$hook = 'wp_enable_random_content_redirect';

		$this->assertTrue( apply_filters( $hook, true ) );

		( new Disable_Random_Content_Redirect() )->boot();

		$this->assertFalse( apply_filters( $hook, true ) );
	}
}
