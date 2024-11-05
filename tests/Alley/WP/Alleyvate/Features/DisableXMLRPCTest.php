<?php
/**
 * Class file for Test_Disable_Dashboard_Widgets
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
		// Get a list of IPs from Jetpack.
		$this->fake_request( 'https://jetpack.com/ips-v4.json' )
			 ->with_response_code( 200 )
			 ->with_body( '["192.0.80.5","192.0.80.6","192.0.80.7"]' );

		// Make XMLRPC Request.
		// Use the XML-RPC "sayHello" method.
		$request = '<?xml version="1.0"?>' .
				   '<methodCall>' .
				   '<methodName>demo.sayHello</methodName>' .
				   '</methodCall>';

		// Use Mantle's command helper to load xmlrpc.php directly via a subprocess, simulating a direct request to the server.
		$response = Utils::command(
			[
				'X_HTTP_FORWARDED_FOR="192.0.80.5"',
				WP_PHP_BINARY,
				escapeshellarg( ABSPATH . 'xmlrpc.php' ),
				// TODO: How do we pass the body in a way that the script can pick up?
				escapeshellarg( $request )
			]
		);

		// Assert that the response is valid.
		$this->assertNotWPError( $response );
		$this->assertEquals( 200, wp_remote_retrieve_response_code( $response ) );

		// Check that the response body includes the expected output from "sayHello".
		$responseBody = wp_remote_retrieve_body( $response );
		$this->assertStringContainsString( 'Hello', $responseBody );
	}
}
