<?php
/**
 * Class file for Test_Disable_Deep_Pagination
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

use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testkit\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Exceptions\WP_Die_Exception;
use WP_Query;
use WP_REST_Request;

/**
 * Tests for fully disabling comment functionality.
 */
final class Test_Disable_Deep_Pagination extends Test_Case {
	use Refresh_Database;
	use Admin_Screen;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Deep_Pagination
	 */
	private Disable_Deep_Pagination $feature;

	/**
	 * Filter function for max number of pages.
	 *
	 * @var callable|null
	 */
	private $filter = null;

	/**
	 * The wp_die_handler callable.
	 *
	 * @var callable|null
	 */
	private $handler = null;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Generate 101 posts to use in testing.
		self::factory()->post->create_ordered_set( 150 );

		// Disable the Admin screen for now.
		$this->admin_screen_tear_down();
		unset( $GLOBALS['current_screen'] );

		$this->handler = function () {
			return fn( $message, $title, $args ) => self::assertSame( 400, $args['response'] );
		};

		add_filter( 'wp_die_handler', $this->handler, PHP_INT_MAX );
		add_filter( 'wp_die_json_handler', $this->handler, PHP_INT_MAX );

		$this->feature = new Disable_Deep_Pagination();
	}

	/**
	 * Tear Down.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['current_screen'] );
		if ( ! empty( $this->filter ) ) {
			remove_filter( 'alleyvate_deep_pagination_max_pages', $this->filter );
		}

		if ( ! empty( $this->handler ) ) {
			remove_filter( 'wp_die_handler', $this->handler );
			remove_filter( 'wp_die_json_handler', $this->handler );
		}
	}

	/**
	 * Verify the maximum pages are listed at 100.
	 *
	 * @test
	 */
	public function test_maximum_posts_restricted() {
		$this->feature->boot();

		// Make sure we are actually filtering where.
		self::assertFalse( is_admin() );

		new WP_Query(
			[
				'posts_per_page' => 1,
				'paged'          => 101,
			]
		);
	}

	/**
	 * Do not filter WP Admin queries.
	 *
	 * @test
	 */
	public function test_admin_queries_are_unaffected() {
		// Enable the Admin screen.
		$this->admin_screen_set_up();
		$this->feature->boot();

		$this->acting_as( 'administrator' );

		self::assertTrue( is_admin() );

		$query = new WP_Query(
			[
				'posts_per_page' => 1,
				'paged'          => 101,
			]
		);

		self::assertTrue( $query->have_posts() );
	}

	/**
	 * Allow filtering of the maximum number of posts.
	 *
	 * @test
	 */
	public function test_maximum_number_of_posts_can_be_filtered() {
		// Enable the Admin screen.
		$this->feature->boot();

		// Make sure we are actually filtering where.
		self::assertFalse( is_admin() );

		new WP_Query(
			[
				'posts_per_page' => 1,
				'paged'          => 101,
			]
		);

		$this->filter_max_pages( 101 );

		$query = new WP_Query(
			[
				'posts_per_page' => 1,
				'paged'          => 101,
			]
		);

		self::assertTrue( $query->have_posts() );
	}

	/**
	 * Validate that all expected pages can be accessed.
	 *
	 * @test
	 */
	public function test_all_expected_available_pages_can_be_accessed() {
		// Enable the Admin screen.
		$this->feature->boot();

		// Make sure we are actually filtering where.
		self::assertFalse( is_admin() );

		$this->filter_max_pages( 5 );

		for ( $paged = 1; $paged <= 5; $paged++ ) {
			$query = new WP_Query(
				[
					'posts_per_page' => 1,
					'paged'          => $paged,
				]
			);

			self::assertTrue( $query->have_posts() );
		}
	}

	/**
	 * We should allow developers to dangerously override this filter in code, when necessary, as a
	 * one-time override of the filter. This will allow us to not have to litter our code with hundreds
	 * of one-time-use filters.
	 *
	 * @test
	 */
	public function test_can_dangerously_override_page_limit() {
		$this->feature->boot();

		// Make sure we are actually filtering where.
		self::assertFalse( is_admin() );

		$query = new WP_Query(
			[
				'posts_per_page'              => 1,
				'paged'                       => 101,
				'__dangerously_set_max_pages' => 101,
			]
		);

		self::assertTrue( $query->have_posts() );
	}

	/**
	 * Unauthenticated REST queries should be filtered.
	 *
	 * @test
	 */
	public function test_unauthenticated_rest_queries_are_filtered() {
		$this->feature->boot();

		$this->get_json( rest_url( '/wp/v2/posts?per_page=1&page=101' ) )
			->assertExactJson( [] );

		$body = $this->get_json( rest_url( '/wp/v2/posts?per_page=1&page=100' ) )
			->get_content();

		$this->assertCount( 1, json_decode( $body ) );
	}

	/**
	 * Authenticated REST queries should NOT be filtered.
	 *
	 * @test
	 */
	public function test_authenticated_rest_queries_are_not_filtered() {
		$this->feature->boot();

		$this->acting_as( 'administrator' );

		$body = $this->get_json( rest_url( '/wp/v2/posts?per_page=1&page=101' ) )
			->get_content();

		$this->assertCount( 1, json_decode( $body ) );
	}

	/**
	 * Helper function for swapping out the max pages filter.
	 *
	 * @param int $max The max pages to return.
	 */
	private function filter_max_pages( int $max ): void {
		if ( ! empty( $this->filter ) ) {
			remove_filter( 'alleyvate_deep_pagination_max_pages', $this->filter );
		}

		if ( 0 >= $max ) {
			$this->filter = null;
			return;
		}

		$this->filter = function () use ( $max ) {
			return $max;
		};

		add_filter( 'alleyvate_deep_pagination_max_pages', $this->filter );
	}
}
