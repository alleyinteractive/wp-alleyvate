<?php
/**
 * Class file for ResetThemeJsonCacheOnSwitchBlogTest
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

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testkit\Test_Case;

/**
 * Test Reset_Theme_Json_Cache_On_Switch_Blog
 */
final class ResetThemeJsonCacheOnSwitchBlogTest extends Test_Case {
	/**
	 * The Feature class.
	 *
	 * @var Reset_Theme_Json_Cache_On_Switch_Blog
	 */
	protected $feature;

	/**
	 * Setup before test.
	 */
	protected function setUp(): void {
		parent::setUp();

		\WP_Theme_JSON_Resolver::clean_cached_data();

		$this->feature = new Reset_Theme_Json_Cache_On_Switch_Blog();
	}

	/**
	 * Clean up after test.
	 */
	protected function tearDown(): void {
		\WP_Theme_JSON_Resolver::clean_cached_data();

		parent::tearDown();
	}

	/**
	 * Test that firing `switch_blog` after the feature boots forces theme.json
	 * to be recalculated, rather than reusing a memoized, stale result.
	 */
	public function test_switch_blog_resets_the_cache(): void {
		// Prime the resolver's cache with the real theme.json data.
		wp_get_global_settings();

		$inject_test_font_family = fn ( $theme_json ) => $theme_json->update_with(
			[
				'version'  => 3,
				'settings' => [
					'typography' => [
						'fontFamilies' => [
							[
								'slug'       => 'alleyvate-test',
								'name'       => 'Alleyvate Test Font',
								'fontFamily' => 'AlleyvateTestFont, sans-serif',
							],
						],
					],
				],
			],
		);

		add_filter( 'wp_theme_json_data_theme', $inject_test_font_family );

		// Without a cache reset, the memoized data is returned as-is, and the
		// newly-added filter has no effect yet.
		$stale_families = wp_list_pluck( $this->get_theme_font_families(), 'fontFamily', 'slug' );

		$this->assertArrayNotHasKey( 'alleyvate-test', $stale_families );

		$this->feature->boot();

		do_action( 'switch_blog', get_current_blog_id(), get_current_blog_id() );

		$fresh_families = wp_list_pluck( $this->get_theme_font_families(), 'fontFamily', 'slug' );

		$this->assertArrayHasKey( 'alleyvate-test', $fresh_families );
		$this->assertSame( 'AlleyvateTestFont, sans-serif', $fresh_families['alleyvate-test'] );

		remove_filter( 'wp_theme_json_data_theme', $inject_test_font_family );
	}

	/**
	 * Get the theme-origin font family definitions from global settings.
	 *
	 * @return mixed[]
	 */
	private function get_theme_font_families(): array {
		$settings   = (array) wp_get_global_settings();
		$typography = (array) ( $settings['typography'] ?? [] );
		$families   = (array) ( $typography['fontFamilies'] ?? [] );

		return (array) ( $families['theme'] ?? [] );
	}
}
