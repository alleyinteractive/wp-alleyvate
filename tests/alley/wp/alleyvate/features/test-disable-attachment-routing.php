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

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for fully disabling attachment routing.
 */
final class Test_Disable_Attachment_Routing extends Test_Case {
	use \Mantle\Testing\Concerns\Admin_Screen;
	use \Mantle\Testing\Concerns\Refresh_Database;

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

		$this->feature = new Disable_Attachment_Routing();
	}

	/**
	 * Test that the attachment permalink is empty.
	 */
	public function test_attachment_permalink(): void {
		$attachment_id = $this->factory()->attachment->create();

		$this->assertNotEmpty( get_permalink( $attachment_id ) );

		$this->feature->boot();

		$this->assertEmpty( get_permalink( $attachment_id ) );
	}

	/**
	 * Test that the attachment page returns a 404.
	 */
	public function test_attachment_page(): void {
		$attachment_id = $this->factory()->attachment->create();
		$permalink     = get_permalink( $attachment_id );

		$this
			->get( $permalink )
			->assertOk()
			->assertQueriedObjectId( $attachment_id );

		$this->feature->boot();

		$this->get( $permalink )->assertNotFound();
	}
}
