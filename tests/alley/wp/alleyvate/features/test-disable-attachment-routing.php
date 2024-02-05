<?php
/**
 * Class file for Test_Disable_Attachment_Routing
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
 * Tests for fully disabling attachment routing.
 */
final class Test_Disable_Attachment_Routing extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Attachment_Routing
	 */
	private Disable_Attachment_Routing $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Attachment_Routing();
	}

	/**
	 * Test that the feature removes rewrite rules related to attachments.
	 */
	public function test_attachment_rewrile_rules_are_removed(): void {
		$rewrite_rules = get_option( 'rewrite_rules' );

		/**
		 * Attachment paths extracted from WordPress Develop.
		 *
		 * @see https://github.com/WordPress/wordpress-develop/blob/5b46851f7c52c2548630314d456b6e058d32a645/tests/phpunit/tests/query/conditionals.php#L774-L789
		 */

		$this->assertArrayHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/?$', $rewrite_rules );
		$this->assertArrayHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', $rewrite_rules );
		$this->assertArrayHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$', $rewrite_rules );

		$this->feature->boot();

		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules

		$rewrite_rules = get_option( 'rewrite_rules' );

		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$', $rewrite_rules );
	}

	/**
	 * Test that the attachment permalink is empty.
	 */
	public function test_attachment_permalink(): void {
		$attachment_id = self::factory()->attachment->create();

		$this->assertNotEmpty( get_permalink( $attachment_id ) );

		$this->feature->boot();

		$this->assertEmpty( get_permalink( $attachment_id ) );
	}

	/**
	 * Test that the attachment page returns a 404.
	 */
	public function test_attachment_page(): void {
		$attachment_id = self::factory()->attachment->create();
		$permalink     = get_permalink( $attachment_id );

		$this
			->get( $permalink )
			->assertOk()
			->assertQueriedObjectId( $attachment_id );

		$this->feature->boot();

		$this->get( $permalink )->assertNotFound();
	}

	/**
	 * Test that the attachment pages are disabled using the new
	 * 'wp_attachment_pages_enabled' option from WordPress 6.4.
	 */
	public function test_attachment_pages_disabled_using_option(): void {
		update_option( 'wp_attachment_pages_enabled', '1' );

		$this->assertEquals( '1', get_option( 'wp_attachment_pages_enabled' ) );

		$this->feature->boot();

		$this->assertEquals( '0', get_option( 'wp_attachment_pages_enabled' ) );
	}
}
