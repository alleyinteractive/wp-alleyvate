<?php
/**
 * Class file for Test_Disable_Site_Health_Directories
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
 * Tests for Disable_Site_Health_Directories feature.
 */
final class DisableSiteHealthDirectoriesTest extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Site_Health_Directories
	 */
	private Disable_Site_Health_Directories $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Site_Health_Directories();
	}

	/**
	 * Test that the REST API endpoint is disabled.
	 */
	public function test_rest_api_disabled(): void {
		$this->feature->boot();

		$this->acting_as( 'administrator' );

		$this->get( rest_url( 'wp-site-health/v1/directory-sizes' ) )->assertStatus( 403 );
	}
}
