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
		$this->get( '/wp-json/wp/v2/posts' )->assertHeader( 'X-Frame-Options', 'SAMEORIGIN' );
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
	 * Test that the X-Frame-Options header can be filtered to an invalid value
	 * while throwing a _doing_it_wrong() notice.
	 */
	public function test_filter_x_frame_options_invalid_header(): void {
		$this->setExpectedIncorrectUsage( Prevent_Framing::class . '::filter__wp_headers' );

		add_filter( 'alleyvate_prevent_framing_x_frame_options', fn () => 'INVALID' );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'X-Frame-Options', 'INVALID' );
	}

	/**
	 * Test that the X-Frame-Options header is not output if it already exists.
	 */
	public function test_x_frame_options_header_already_exists(): void {
		add_filter( 'wp_headers', fn () => [ 'X-Frame-Options' => 'CUSTOM' ] );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'X-Frame-Options', 'CUSTOM' );
	}

	/**
	 * Test that the X-Frame-Options header is not output if the feature is disabled.
	 */
	public function test_x_frame_options_header_disabled(): void {
		add_filter( 'alleyvate_prevent_framing_disable', fn () => true );

		$this->feature->boot();

		$this->get( '/' )->assertHeaderMissing( 'X-Frame-Options' );
	}

	/**
	 * Test that the CSP header is not output unless enabled.
	 */
	public function test_csp_header_default(): void {
		$this->expectApplied( 'alleyvate_prevent_framing_csp' )->andReturnFalse();

		$this->feature->boot();

		$this->get( '/' )->assertHeaderMissing( 'Content-Security-Policy' );
	}

	/**
	 * Test the default value of the CSP header when enabled.
	 */
	public function test_csp_header_enabled(): void {
		$this->expectApplied( 'alleyvate_prevent_framing_csp' )->andReturnTrue();

		add_filter( 'alleyvate_prevent_framing_csp', fn () => true );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'Content-Security-Policy', "frame-ancestors 'self'" );
	}

	/**
	 * Test that the CSP header is not output if it already exists.
	 */
	public function test_csp_header_already_exists(): void {
		add_filter( 'wp_headers', fn () => [ 'Content-Security-Policy' => 'CUSTOM' ] );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'Content-Security-Policy', 'CUSTOM' );
	}

	/**
	 * Test the CSP header with custom frame ancestors.
	 */
	public function test_csp_header_custom_frame_ancestors(): void {
		$this->expectApplied( 'alleyvate_prevent_framing_csp_frame_ancestors' )->andReturnArray();

		add_filter( 'alleyvate_prevent_framing_csp', fn () => true );

		add_filter(
			'alleyvate_prevent_framing_csp_frame_ancestors',
			fn () => [
				'example.com',
				'example.org',
			]
		);

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'Content-Security-Policy', 'frame-ancestors example.com example.org' );
	}

	/**
	 * Test the CSP header being overridden completely.
	 */
	public function test_csp_header_override(): void {
		$this->expectApplied( 'alleyvate_prevent_framing_csp_header' )->andReturnString();

		add_filter( 'alleyvate_prevent_framing_csp', fn () => true );
		add_filter( 'alleyvate_prevent_framing_csp_header', fn () => 'custom-value' );

		$this->feature->boot();

		$this->get( '/' )->assertHeader( 'Content-Security-Policy', 'custom-value' );
	}
}
