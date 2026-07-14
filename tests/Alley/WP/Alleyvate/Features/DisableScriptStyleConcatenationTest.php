<?php
/**
 * Class file for DisableScriptStyleConcatenationTest
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
 * Test Disable_Script_Style_Concatenation
 */
final class DisableScriptStyleConcatenationTest extends Test_Case {
	/**
	 * The Feature class.
	 *
	 * @var Disable_Script_Style_Concatenation
	 */
	protected $feature;

	/**
	 * Setup before test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Script_Style_Concatenation();
	}

	/**
	 * Test that script concatenation is disabled for all script handles.
	 */
	public function testScriptConcatenationIsDisabled() {
		$this->assertTrue( apply_filters( 'js_do_concat', true, 'example-script' ) );

		$this->feature->boot();

		$this->assertFalse( apply_filters( 'js_do_concat', true, 'example-script' ) );
	}

	/**
	 * Test that style concatenation is disabled for all style handles.
	 */
	public function testStyleConcatenationIsDisabled() {
		$this->assertTrue( apply_filters( 'css_do_concat', true, 'example-style' ) );

		$this->feature->boot();

		$this->assertFalse( apply_filters( 'css_do_concat', true, 'example-style' ) );
	}
}
