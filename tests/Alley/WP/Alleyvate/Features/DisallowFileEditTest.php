<?php
/**
 * Class file for Test_Disallow_File_Edit
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

use Mantle\Testkit\Test_Case;

/**
 * Tests for the disallowing of file editing.
 */
final class DisallowFileEditTest extends Test_Case {

	/**
	 * Feature instance.
	 *
	 * @var Disallow_File_Edit
	 */
	private Disallow_File_Edit $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disallow_File_Edit();
	}

	/**
	 * Test that the feature disallows file editing.
	 */
	public function test_disallow_file_editing(): void {
		$this->assertFalse( \defined( 'DISALLOW_FILE_EDIT' ), 'DISALLOW_FILE_EDIT should not be defined prior to boot.' );
		$this->feature->boot();
		$this->assertTrue( \defined( 'DISALLOW_FILE_EDIT' ), 'DISALLOW_FILE_EDIT should be defined after boot.' );
		$this->assertTrue( DISALLOW_FILE_EDIT );
	}
}
