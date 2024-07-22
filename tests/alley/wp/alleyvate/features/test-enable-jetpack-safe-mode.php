<?php
/**
 * Class file for Test_Enable_Jetpack_Safe_Mode
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
 * Tests for the enabling of Jetpack safe mode.
 */
final class Test_Enabled_Jetpack_Safe_Mode extends Test_Case {

	/**
	 * Feature instance.
	 *
	 * @var Enable_Jetpack_Safe_Mode
	 */
	private Enable_Jetpack_Safe_Mode $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Enable_Jetpack_Safe_Mode();
	}

	/**
	 * Test that the feature enabled Jetpack safe mode.
	 */
	public function test_enable_jetpack_safe_mode(): void {
		$this->assertFalse( apply_filters( 'jetpack_is_development_site', false ), 'jetpack_is_development_site should not be true prior to boot.' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$this->setEnvironment( 'test' );
		$this->feature->boot();
		$this->assertTrue( apply_filters( 'jetpack_is_development_site', false ), 'jetpack_is_development_site should be true after boot.' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Set the current environment value.
	 *
	 * @param string $environment The environment name to use.
	 */
	protected function setEnvironment( string $environment ): void {
		putenv( 'PANTHEON_ENVIRONMENT=' . $environment ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
		$_ENV['PANTHEON_ENVIRONMENT'] = $environment;
	}
}
