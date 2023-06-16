<?php
/**
 * Class file for Test_Disable_Attachment_Routes
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for disabling attachment (media file) routes.
 */
final class Test_Disable_Attachment_Routes extends Test_Case {
	/**
	 * Feature instance.
	 *
	 * @var Feature
	 */
	private Feature $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Attachment_Routes();
	}

	/**
	 * A data provider for the remove rewrite rules test.
	 *
	 * @return array[] An array of arrays representing function arguments.
	 */
	public function data_remove_rewrite_rules(): array {
		return [
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/embed/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' ],
			[ '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/embed/?$' ],
			[ '.?.+?/attachment/([^/]+)/?$' ],
			[ '.?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' ],
			[ '.?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' ],
			[ '.?.+?/attachment/([^/]+)/embed/?$' ],
		];
	}

	/**
	 * Test that the feature removes rewrite rules related to attachment detail pages.
	 *
	 * @dataProvider data_remove_rewrite_rules
	 *
	 * @param string $pattern The URL rewrite pattern to test.
	 */
	public function test_remove_rewrite_rules( string $pattern ): void {
		// Ensure attachment rewrite rules exist before removing them.
		$rewrite_rules = get_option( 'rewrite_rules' );
		$this->assertArrayHasKey( $pattern, $rewrite_rules );

		// Activate feature.
		$this->feature->boot();

		// Flush the rewrite rules and load in our changes.
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		$rewrite_rules = get_option( 'rewrite_rules' );

		// Ensure rewrite rules have been removed.
		$this->assertArrayNotHasKey( $pattern, $rewrite_rules );
	}

	/**
	 * Test that the feature returns a 404 for a request to an unattached media item's detail page.
	 */
	public function test_unattached_404(): void {
		// Create image and test that the route is available.
		$attachment = self::factory()->attachment->create_and_get(
			[
				'post_title' => 'lorem-ipsum',
			]
		);
		$this->get( $attachment )->assertOk();

		// Activate feature.
		$this->feature->boot();

		// Test 404 after boot.
		$this->get( $attachment )->assertNotFound();
	}
}
