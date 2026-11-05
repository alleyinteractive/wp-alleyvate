<?php
/**
 * Class file for Test_Cache_Slow_Queries
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare(strict_types=1);

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testkit\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;

/**
 * Tests for caching slow queries.
 */
#[PreserveGlobalState( false )]
#[RunClassInSeparateProcess]
final class CacheSlowQueriesTest extends Test_Case {
	use Admin_Screen;
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Cache_Slow_Queries
	 */
	private Cache_Slow_Queries $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Cache_Slow_Queries();
	}

	/**
	 * Test optimizing the date query for the months dropdown.
	 */
	public function test_optimize_months_dropdown(): void {
		$this->expectApplied( 'pre_months_dropdown_query' )->once();
		$this->expectApplied( 'months_dropdown_results' )->once();

		self::factory()->post->create_many( 10 );

		set_current_screen( 'edit-post' );

		$this->acting_as( 'administrator' );

		// Boot feature.
		$this->feature->boot();

		// Require the WP_List_Table class and boot it.
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';

		ob_start();

		// Get the months dropdown.
		$table = new \WP_Posts_List_Table(
			[
				'screen' => get_current_screen(),
			],
		);

		$table->display();

		ob_end_clean();

		$this->assertNotEmpty( wp_cache_get( 'post', 'alleyvate_months_dropdown' ) );
	}

	/**
	 * Test that the cache is cleared for the months dropdown query when the post is published.
	 */
	public function test_optimize_months_dropdown_cache_cleared_on_publish(): void {
		self::factory()->post->create( [ 'post_date' => '2020-01-05 00:00:00' ] );

		// Prime the cache with some old months.
		wp_cache_set(
			'post',
			[
				(object) [
					'year'  => 2020,
					'month' => 1,
				],
			],
			'alleyvate_months_dropdown'
		);

		// Boot feature.
		$this->feature->boot();

		// Publish a post which should clear the cache.
		self::factory()->post->create( [ 'post_date' => '2022-02-05 00:00:00' ] );

		$this->assertEmpty( wp_cache_get( 'post', 'alleyvate_months_dropdown' ) );
	}

	/**
	 * Test that the available post MIME types are returned from the cache.
	 */
	public function test_available_post_mime_types_are_cached(): void {
		$this->expectApplied( 'pre_get_available_post_mime_types' )->once();

		// Boot feature.
		$this->feature->boot();

		// Prime the cache with a known value.
		wp_cache_set( 'attachment', [ 'image/png' ], 'alleyvate_available_post_mime_types' );

		$this->assertSame( [ 'image/png' ], get_available_post_mime_types( 'attachment' ) );
	}

	/**
	 * Test that the available post MIME types query result is cached on a cache miss.
	 */
	public function test_available_post_mime_types_cache_is_populated(): void {
		self::factory()->post->create(
			[
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			],
		);
		self::factory()->post->create(
			[
				'post_type'      => 'attachment',
				'post_mime_type' => 'application/pdf',
			],
		);

		// Boot feature.
		$this->feature->boot();

		$mime_types = get_available_post_mime_types( 'attachment' );

		sort( $mime_types );

		$this->assertSame( [ 'application/pdf', 'image/jpeg' ], $mime_types );

		$cached = wp_cache_get( 'attachment', 'alleyvate_available_post_mime_types' );

		sort( $cached );

		$this->assertSame( [ 'application/pdf', 'image/jpeg' ], $cached );
	}

	/**
	 * Test that the available post MIME types cache is cleared when an attachment is added.
	 */
	public function test_available_post_mime_types_cache_cleared_on_add_attachment(): void {
		// Boot feature.
		$this->feature->boot();

		// Prime the cache with a stale value.
		wp_cache_set( 'attachment', [ 'image/png' ], 'alleyvate_available_post_mime_types' );

		self::factory()->post->create(
			[
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			],
		);

		$this->assertFalse( wp_cache_get( 'attachment', 'alleyvate_available_post_mime_types' ) );
	}

	/**
	 * Test that the available post MIME types cache is cleared when an attachment is deleted.
	 */
	public function test_available_post_mime_types_cache_cleared_on_delete_attachment(): void {
		$attachment_id = self::factory()->post->create(
			[
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			],
		);

		// Boot feature.
		$this->feature->boot();

		// Prime the cache with a stale value.
		wp_cache_set( 'attachment', [ 'image/png' ], 'alleyvate_available_post_mime_types' );

		wp_delete_attachment( $attachment_id, true );

		$this->assertFalse( wp_cache_get( 'attachment', 'alleyvate_available_post_mime_types' ) );
	}
}
