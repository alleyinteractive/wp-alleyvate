<?php
/**
 * Class file for Test_Disable_Block_Editor_Rest_Api_Preload_Paths
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

use Mantle\Testkit\Test_Case;

/**
 * Tests for disabling block editor REST API preload paths.
 */
final class DisableBlockEditorRestApiPreloadPathsTest extends Test_Case {

	/**
	 * Feature instance.
	 *
	 * @var Disable_Block_Editor_Rest_Api_Preload_Paths
	 */
	private Disable_Block_Editor_Rest_Api_Preload_Paths $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Block_Editor_Rest_Api_Preload_Paths();
	}

	/**
	 * Test that the feature short-circuits a redirect that would otherwise occur.
	 */
	public function test_disable_block_editor_rest_api_preload_paths(): void { // phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle

		$post = self::factory()->post->create_and_get(
			[
				'post_title' => 'Testing REST API Preload Paths',
			]
		);

		/**
		 * This code mimics the logic in wp-admin/edit-form-blocks.php to generate the preload paths.
		 */
		$rest_path     = rest_get_route_for_post( $post );
		$post_type     = get_post_type( $post );
		$preload_paths = [
			'/wp/v2/types?context=view',
			'/wp/v2/taxonomies?context=view',
			add_query_arg(
				[
					'context'  => 'edit',
					'per_page' => - 1,
				],
				rest_get_route_for_post_type_items( 'wp_block' )
			),
			add_query_arg( 'context', 'edit', $rest_path ),
			sprintf( '/wp/v2/types/%s?context=edit', $post_type ),
			'/wp/v2/users/me',
			[ rest_get_route_for_post_type_items( 'attachment' ), 'OPTIONS' ],
			[ rest_get_route_for_post_type_items( 'page' ), 'OPTIONS' ],
			[ rest_get_route_for_post_type_items( 'wp_block' ), 'OPTIONS' ],
			[ rest_get_route_for_post_type_items( 'wp_template' ), 'OPTIONS' ],
			sprintf( '%s/autosaves?context=edit', $rest_path ),
			'/wp/v2/settings',
			[ '/wp/v2/settings', 'OPTIONS' ],
		];

		// Apply the filter that modifies the preload paths before the feature is activated.
		$preload_paths = apply_filters( 'block_editor_rest_api_preload_paths', $preload_paths ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Assert that the blocks rest path is in the preload paths.
		$this->assertContains( '/wp/v2/blocks?context=edit&per_page=-1', $preload_paths );

		// Check the other preload paths to ensure they are present.
		$this->check_preloads_paths( $preload_paths, $rest_path, $post_type );

		// Activate feature.
		$this->feature->boot();

		// Apply the filter that modifies the preload paths.
		$preload_paths = apply_filters( 'block_editor_rest_api_preload_paths', $preload_paths ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// The blocks rest path should no longer be in the preload paths.
		$this->assertNotContains( '/wp/v2/blocks?context=edit&per_page=-1', $preload_paths );

		// Check the other preload paths to ensure they are present.
		$this->check_preloads_paths( $preload_paths, $rest_path, $post_type );
	}

	/**
	 * Check the preload paths.
	 *
	 * @param mixed  $preload_paths The preload paths.
	 * @param string $rest_path The rest path.
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function check_preloads_paths( mixed $preload_paths, string $rest_path, string $post_type ): void {
		// The other preload paths should still be present.
		$this->assertContains( '/wp/v2/types?context=view', $preload_paths );
		$this->assertContains( '/wp/v2/taxonomies?context=view', $preload_paths );
		$this->assertContains( add_query_arg( 'context', 'edit', $rest_path ), $preload_paths );
		$this->assertContains( sprintf( '/wp/v2/types/%s?context=edit', $post_type ), $preload_paths );
		$this->assertContains( '/wp/v2/users/me', $preload_paths );
		$this->assertContains( [ rest_get_route_for_post_type_items( 'attachment' ), 'OPTIONS' ], $preload_paths );
		$this->assertContains( [ rest_get_route_for_post_type_items( 'page' ), 'OPTIONS' ], $preload_paths );
		$this->assertContains( [ rest_get_route_for_post_type_items( 'wp_block' ), 'OPTIONS' ], $preload_paths );
		$this->assertContains( [ rest_get_route_for_post_type_items( 'wp_template' ), 'OPTIONS' ], $preload_paths );
		$this->assertContains( sprintf( '%s/autosaves?context=edit', $rest_path ), $preload_paths );
		$this->assertContains( '/wp/v2/settings', $preload_paths );
		$this->assertContains( [ '/wp/v2/settings', 'OPTIONS' ], $preload_paths );
	}
}
