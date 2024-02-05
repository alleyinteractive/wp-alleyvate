<?php
/**
 * Class file for Test_Site_Health
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

use function Alley\WP\Alleyvate\available_features;

/**
 * Test for site health feature.
 */
final class Test_Site_Health extends Test_Case {

	/**
	 * Feature instance.
	 *
	 * @var Site_Health
	 */
	private Site_Health $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Site_Health();
	}

	/**
	 * Test the site health feature.
	 */
	public function test_site_health_feature(): void {
		$features = available_features();

		$this->expectApplied( 'alleyvate_load_feature' )->times( \count( $features ) );

		foreach ( $features as $handle => $class ) {
			$this->expectApplied( "alleyvate_load_{$handle}" )->once();
		}

		$this->feature->boot();

		// Mock the Site Health screen.
		$data = apply_filters( 'debug_information', [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$this->assertNotEmpty( $data['wp-alleyvate'] ?? null );
		$this->assertNotEmpty( $data['wp-alleyvate']['fields'] ?? null );
		$this->assertCount( \count( $features ), $data['wp-alleyvate']['fields'] );
	}
}
