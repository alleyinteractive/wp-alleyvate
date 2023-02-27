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

namespace Alley\WP\Alleyvate;

use Mantle\Testkit\Test_Case;

/**
 * Tests for the user enumeration restrictions feature.
 */
final class Test_User_Enumeration_Restrictions extends Test_Case {
	/**
	 * User should receive the given response code when accessing user routes.
	 *
	 * @dataProvider data_rest_enumeration_by_user
	 *
	 * @param bool $logged_in       Whether a user is logged in.
	 * @param int  $expected_status Expected response code.
	 */
	public function test_rest_enumeration_by_user( $logged_in, $expected_status ) {
		/*
		 * Individual users can be read anonymously over the REST API only
		 * if they're the author of a post that is itself shown in REST.
		 */
		$post = self::factory()->post->create_and_get(
			[
				'post_author' => self::factory()->user->create(),
			]
		);

		$req_collection = new \WP_REST_Request( 'GET', '/wp/v2/users' );
		$req_item       = new \WP_REST_Request( 'GET', "/wp/v2/users/{$post->post_author}" );

		wp_set_current_user( $logged_in ? self::factory()->user->create() : 0 );

		foreach ( [ $req_collection, $req_item ] as $req ) {
			$res = rest_do_request( $req );

			$this->assertSame(
				$expected_status,
				$res->get_status(),
				"Route: {$req->get_route()}",
			);
		}
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_rest_enumeration_by_user() {
		return [
			'logged-out user' => [ false, 401 ],
			'logged-in user'  => [ true, 200 ],
		];
	}
}
