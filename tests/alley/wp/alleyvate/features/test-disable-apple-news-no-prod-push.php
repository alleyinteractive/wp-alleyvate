<?php
/**
 * Class file for Disable_Apple_News_No_Prod_Push
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

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Test double for Disable_Apple_News_No_Prod_Push. Mocks production environment.
 */
class TestDoubleProduction extends Disable_Apple_News_No_Prod_Push {
	/**
	 * Override of the is_production_environment method to always return true.
	 *
	 * @return boolean
	 */
	protected function is_production_environment(): bool {
		return true;
	}
}

/**
 * Test double for Disable_Apple_News_No_Prod_Push. Mocks non-production environment.
 */
class TestDoubleNonProduction extends Disable_Apple_News_No_Prod_Push {
	/**
	 * Override of the is_production_environment method to always return false.
	 *
	 * @return boolean
	 */
	protected function is_production_environment(): bool {
		return false;
	}
}

/**
 * Test Disable_Apple_News_No_Prod_Push
 */
class Disable_Apple_News_No_Prod_Push_Test extends Test_Case {
	/**
	 * Test that the filter_apple_news_skip_push method returns false when passed false on a production environment.
	 *
	 * @return void
	 */
	public function testFalseFilterAppleNewsSkipPushProductionEnvironment() {
		$instance = new TestDoubleProduction();
		$skip     = false;

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertFalse( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a production environment.
	 *
	 * @return void
	 */
	public function testTrueFilterAppleNewsSkipPushProductionEnvironment() {
		$instance = new TestDoubleProduction();
		$skip     = true;

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed false on a non-production environment.
	 *
	 * @return void
	 */
	public function testFalseFilterAppleNewsSkipPushOtherEnvironments() {
		$instance = new TestDoubleNonProduction();
		$skip     = false;

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a non-production environment.
	 *
	 * @return void
	 */
	public function testTrueFilterAppleNewsSkipPushOtherEnvironments() {
		$instance = new TestDoubleNonProduction();
		$skip     = true;

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

}
