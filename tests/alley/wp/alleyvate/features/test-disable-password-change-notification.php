<?php
/**
 * Class file for Test_Disable_Password_Change_Notification
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
 * Tests for disabling the password change notification.
 */
final class Test_Disable_Password_Change_Notification extends Test_Case {
	use \Mantle\Testing\Concerns\Refresh_Database;

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

		$this->feature = new Disable_Password_Change_Notification();
	}

	/**
	 * Test that the feature.
	 */
	public function test_disable_password_change_notification_hook() {
		$this->acting_as( 'administrator' );

		$this->assertTrue(
			false !== has_action( 'after_password_reset', 'wp_password_change_notification' ),
		);

		$this->feature->boot();

		$this->assertFalse(
			has_action( 'after_password_reset', 'wp_password_change_notification' ),
			'wp_password_change_notification was not removed.'
		);
	}
}
