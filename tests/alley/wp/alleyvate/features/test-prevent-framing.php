<?php
/**
 * Class file for Test_Prevent_Framing
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
 * Tests for the preventing the iframing of a site.
 */
final class Test_Prevent_Framing extends Test_Case {
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

		$this->feature = new Prevent_Framing();
	}

	/**
	 * Test that the X-Frame-Options header is output.
	 */
	public function test_x_frame_options_header(): void {
		$this->expectApplied( 'alleyvate_prevent_framing_x_frame_options' )->andReturnString();

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'X-Frame-Options', 'SAMEORIGIN' );
	}

	/**
	 * Test that the X-Frame-Options header can be filtered.
	 */
	public function test_filter_x_frame_options_header(): void {
		add_filter( 'alleyvate_prevent_framing_x_frame_options', fn () => 'DENY' );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'X-Frame-Options', 'DENY' );
	}

	/**
	 * Test that the X-Frame-Options header is not output if it already exists.
	 */
	public function test_x_frame_options_header_already_exists(): void {
		add_filter( 'wp_headers', fn () => [ 'X-Frame-Options' => 'CUSTOM' ] );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'X-Frame-Options', 'CUSTOM' );
	}
}
