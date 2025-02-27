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
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Generic.CodeAnalysis.EmptyStatement.DetectedCatch, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 */

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Exceptions\WP_Die_Exception;
use Mantle\Testkit\Test_Case;

/**
 * Tests for the login nonce.
 */
final class LoginNonceTest extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Login_Nonce
	 */
	private Login_Nonce $feature;

	/**
	 * Setup the test case.
	 *
	 * @param array ...$args The array of arguments passed to the class.
	 */
	public function __construct( ...$args ) {
		parent::__construct( ...$args );

		// Run the test in isolation to allow us to use http_response_code().
		$this->setPreserveGlobalState( false );
		$this->setRunClassInSeparateProcess( true );
	}

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

		remove_action( 'nonce_life', [ Login_Nonce::class, 'nonce_life_filter' ] );

		parent::tearDown();
	}

	/**
	 * Test that login nonces are required to login successfully.
	 */
	public function test_logins_require_nonce(): void {
		global $pagenow;

		$_POST = [ 'pwd' => 'password' ];

		$pagenow = 'wp-login.php';

		// Prevent the redirect and exit from firing.
		add_filter( 'wp_redirect', fn( $location ) => wp_die( esc_url( $location ) ) );

		try {
			Login_Nonce::action__pre_validate_login_nonce();
		} catch ( WP_Die_Exception $e ) {
			$this->assertSame( wp_login_url() . '?action=expired', $e->getMessage() );
		}
	}

	/**
	 * Test that using nonces allow successful logins.
	 */
	public function test_logins_work_with_nonce(): void {
		global $pagenow;

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

	/**
	 * Test the login nonce doesn't affect other wp-login.php actions.
	 */
	public function test_login_nonce_validates(): void {
		$this->feature->boot();

		$token = wp_create_nonce( Login_Nonce::NONCE_ACTION );

		$this->assertTrue( wp_validate_boolean( wp_verify_nonce( $token, Login_Nonce::NONCE_ACTION ) ) );
	}

	/**
	 * Test the login nonce doesn't affect other wp-login.php actions.
	 */
	public function test_logout_nonce_validates(): void {
		$this->feature->boot();

		$token = wp_create_nonce( 'log-out' );

		do_action( 'login_init' );

		$this->assertTrue( wp_validate_boolean( wp_verify_nonce( $token, 'log-out' ) ) );
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

		$headers = wp_get_nocache_headers();

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

		$headers = wp_get_nocache_headers();

		self::assertArrayHasKey( 'Cache-Control', $headers );
		self::assertStringNotContainsString( 'no-store', $headers['Cache-Control'] );
	}
}
