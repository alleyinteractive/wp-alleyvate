<?php
/**
 * Class file for Test_Clean_Admin_Bar
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
 * Tests for the cleaning of the admin bar.
 */
final class Test_Clean_Admin_Bar extends Test_Case {
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

		$this->feature = new Clean_Admin_Bar();
	}

	/**
	 * Test that the feature disallows file editing.
	 */
	public function test_clean_admin_bar() {
		global $wp_admin_bar;

		$this->assertFalse( \defined( 'DISALLOW_FILE_EDIT' ), 'DISALLOW_FILE_EDIT should not be defined prior to boot.' );
		$this->feature->boot();
		$this->assertTrue( \defined( 'DISALLOW_FILE_EDIT' ), 'DISALLOW_FILE_EDIT should be defined after boot.' );
		$this->assertTrue( DISALLOW_FILE_EDIT );
	}
}
