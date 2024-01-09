<?php
/**
 * Class file for Test_Disable_Custom_Fields_Meta_Box
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
 * Tests for the disabling of the custom fields meta box.
 */
final class Test_Disable_Custom_Fields_Meta_Box extends Test_Case {
	use Concerns\Remove_Meta_Box;

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

		$this->feature = new Disable_Custom_Fields_Meta_Box();
	}

	/**
	 * Test that the custom fields metaboxes is removed.
	 */
	public function test_remove_metaboxes(): void {
		$this->assertMetaBoxRemoval(
			feature: $this->feature,
			id: 'postcustom',
			priority: 'core',
		);
	}
}
