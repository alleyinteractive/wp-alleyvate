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

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testkit\Test_Case;

/**
 * Tests for the disabling of the custom fields meta box.
 */
final class DisableCustomFieldsMetaBoxTest extends Test_Case {
	use Concerns\RemoveMetaBox;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Custom_Fields_Meta_Box
	 */
	private Disable_Custom_Fields_Meta_Box $feature;

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
