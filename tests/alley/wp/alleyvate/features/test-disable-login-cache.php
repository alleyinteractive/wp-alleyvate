<?php
/**
 * Class file for Test_Disable_Login_Cache
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 *
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Generic.CodeAnalysis.EmptyStatement.DetectedCatch, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for the disabling the login cache.
 */
final class Test_Disable_Login_Cache extends Test_Case {
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

		$this->feature = new Disable_Login_Cache();
	}

	/**
	 * Verify that the no-store flag is added to the login page.
	 *
	 * Note: `wp_get_nocache_headers()` is used by `nocache_headers()` which
	 * in turn is called on `wp-login.php`. We call it directly here so
	 * we can assert against an array instead of trying to send headers.
	 */
	public function test_login_page_cache_is_no_stored() {
		global $pagenow;

		$pagenow = 'wp-login.php';

		$this->feature->boot();

		$headers = \wp_get_nocache_headers();

		self::assertArrayHasKey( 'Cache-Control', $headers );
		self::assertStringContainsString( 'no-store', $headers['Cache-Control'] );
	}

	/**
	 * Verify that the no-store flag isn't added to other pages.
	 */
	public function test_non_login_page_is_stored() {
		global $pagenow;

		$pagenow = 'single.php'; // Anything other than wp-login.php.

		$this->feature->boot();

		$headers = \wp_get_nocache_headers();

		self::assertArrayHasKey( 'Cache-Control', $headers );
		self::assertStringNotContainsString( 'no-store', $headers['Cache-Control'] );
	}
}