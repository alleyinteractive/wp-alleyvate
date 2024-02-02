<?php
/**
 * Class file for Test_User_Enumeration_Restrictions
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

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

/**
 * Tests for the user enumeration restrictions feature.
 */
final class Test_User_Enumeration_Restrictions extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var User_Enumeration_Restrictions
	 */
	private User_Enumeration_Restrictions $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new User_Enumeration_Restrictions();
	}

	/**
	 * User should receive the given response code when accessing user routes.
	 *
	 * @dataProvider data_rest_enumeration_by_user
	 *
	 * @param bool $logged_in       Whether a user is logged in.
	 * @param int  $expected_status Expected response code.
	 */
	public function test_rest_enumeration_by_user( bool $logged_in, int $expected_status ): void {
		/*
		 * Individual users can be read anonymously over the REST API only
		 * if they're the author of a post that is itself shown in REST.
		 */
		$post = self::factory()->post->create_and_get(
			[
				'post_author' => self::factory()->user->create(),
			]
		);

		$reqs = [
			new \WP_REST_Request( 'GET', '/wp/v2/users' ), // Collection.
			new \WP_REST_Request( 'GET', "/wp/v2/users/{$post->post_author}" ), // Item.
		];

		wp_set_current_user( 0 );

		foreach ( $reqs as $req ) {
			$res = rest_do_request( $req );

			if ( $res->get_status() !== 200 ) {
				$this->markTestSkipped(
					"Could not test feature because anonymous request for route {$req->get_route()} returned"
					. " HTTP {$res->get_status()} even though feature is not booted",
				);
			}
		}

		$this->feature->boot();

		if ( $logged_in ) {
			wp_set_current_user( self::factory()->user->create() );
		}

		foreach ( $reqs as $req ) {
			$res = rest_do_request( $req );

			$this->assertSame(
				$expected_status,
				$res->get_status(),
				"Route: {$req->get_route()}",
			);
		}
	}

	/**
	 * Data provider for the test_rest_enumeration_by_user test.
	 *
	 * @return array
	 */
	public function data_rest_enumeration_by_user(): array {
		return [
			'logged-out user' => [ false, 401 ],
			'logged-in user'  => [ true, 200 ],
		];
	}
}
