<?php
/**
 * Class file for Test_Login_Nonce
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
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;
use WP_Error;
use WP_User;

/**
 * Tests for the login nonce.
 */
final class Test_Login_Nonce extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Login_Nonce
	 */
	private Login_Nonce $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Login_Nonce();
	}

	/**
	 * Test that login nonces are required to login successfully.
	 */
	public function test_logins_require_nonce() {
		$this->feature->boot();

		$this->feature->initialize_nonce_fields();

		static::factory()->user->create([
			'user_login' => 'nonce_user',
			'user_pass' => 'password',
		]);

		$_POST = [
			'log' => 'nonce_user',
			'pwd' => 'password',
		];

		$user = wp_signon();

		$this->assertInstanceOf( WP_Error::class, $user );
		$this->assertSame( 'nonce_failure', $user->get_error_code() );
	}

	/**
	 * Test that using nonces allow successful logins.
	 */
	public function test_logins_work_with_nonce() {
		$this->feature->boot();

		$this->feature->initialize_nonce_fields();

		static::factory()->user->create([
			'user_login' => 'nonce_user',
			'user_pass' => 'password',
		]);

		$_POST = [
			'log' => 'nonce_user',
			'pwd' => 'password',
			$this->feature->generate_random_nonce_name( 'alleyvate_login_nonce' ) => wp_create_nonce( 'alleyvate_login_action' )
		];

		add_filter( 'send_auth_cookies', '__return_false' );

		$user = wp_signon();

		$this->assertInstanceOf( WP_User::class, $user );
	}
}
