<?php
/**
 * Class file for DisableXMLRPCTest
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 *
 * @package wp-alleyvate
 */

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Utils;
use Mantle\Testkit\Test_Case;

/**
 * Tests for disabling selected unpopular dashboard widgets.
 */
final class DisableXMLRPCTest extends Test_Case {

	/**
	 * Feature instance.
	 *
	 * @var Disable_XMLRPC
	 */
	private Disable_XMLRPC $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_XMLRPC();
	}

	/**
	 * Test that widgets have been removed.
	 */
	public function test_disable_xmlrpc(): void {
		// XMLRPC should be available normally.
		$this->assertTrue( apply_filters( 'xmlrpc_enabled', true ) );
		$this->assertNotEmpty( apply_filters( 'xmlrpc_methods', [ 'testMethod' ] ) );

		// Boot the feature and ensure XMLRPC is turned off.
		$this->feature->boot();
		$this->assertFalse( apply_filters( 'xmlrpc_enabled', true ) );
		$this->assertEmpty( apply_filters( 'xmlrpc_methods', [ 'testMethod' ] ) );

		// Fake a request from a Jetpack IP and ensure XMLRPC is allowed for Jetpack origins.
		\define( 'JETPACK__VERSION', 'x.y.z' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		$_SERVER['REMOTE_ADDR'] = '192.0.80.5'; // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders,WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		$this->fake_request( 'https://jetpack.com/ips-v4.json' )
			->with_response_code( 200 )
			->with_body( '["192.0.80.5","192.0.80.6","192.0.80.7"]' );
		$this->assertTrue( apply_filters( 'xmlrpc_enabled', true ) );
		$this->assertNotEmpty( apply_filters( 'xmlrpc_methods', [ 'testMethod' ] ) );
	}
}
