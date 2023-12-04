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
 *
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Generic.CodeAnalysis.EmptyStatement.DetectedCatch
 */

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Exceptions\WP_Die_Exception;
use Mantle\Testkit\Test_Case;

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

		/*
		 * Prime the response code to 200 before running nonce validations.
		 */
		http_response_code( 200 );
	}

	/**
	 * Tear Down.
	 */
	protected function tearDown(): void {
		$_POST = [];
		http_response_code( 200 );
		parent::tearDown();
	}

	/**
	 * Test that login nonces are required to login successfully.
	 */
	public function test_logins_require_nonce() {
		global $pagenow;

		$this->feature->boot();

		$_POST = [
			'pwd' => 'password',
		];

		$pagenow = 'wp-login.php';

		try {
			Login_Nonce::action__pre_validate_login_nonce();
		} catch ( WP_Die_Exception $e ) {
			// Do nothing.
		}

		$this->assertSame( 403, http_response_code() );
	}

	/**
	 * Test that using nonces allow successful logins.
	 */
	public function test_logins_work_with_nonce() {
		global $pagenow;

		$this->feature->boot();

		$nonce_life_filter = fn() => Login_Nonce::NONCE_TIMEOUT;

		/*
		 * Nonce life is used to generate the nonce value. If this differs from the form,
		 * the nonce will not validate.
		 */
		add_filter( 'nonce_life', $nonce_life_filter );
		$_POST = [
			'pwd'                   => 'password',
			Login_Nonce::NONCE_NAME => wp_create_nonce( Login_Nonce::NONCE_ACTION ),
		];

		remove_filter( 'nonce_life', $nonce_life_filter );

		$pagenow = 'wp-login.php';

		try {
			Login_Nonce::action__pre_validate_login_nonce();
		} catch ( WP_Die_Exception $e ) {
			// Do nothing.
		}

		$this->assertSame( 200, http_response_code() );
	}
}
