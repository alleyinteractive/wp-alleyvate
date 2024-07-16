<?php
/**
 * Class file for Disable_Apple_News_Non_Prod_Push
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
 * Test Disable_Apple_News_Non_Prod_Push
 */
final class Disable_Apple_News_Non_Prod_Push_Test extends Test_Case {
	/**
	 * The Feature class.
	 *
	 * @var Disable_Apple_News_Non_Prod_Push
	 */
	protected $feature;

	/**
	 * Setup before test.
	 */
	protected function setUp(): void {
		$this->feature = new Disable_Apple_News_Non_Prod_Push();
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns false when passed false on a production_ environment.
	 */
	public function testFalseFilterAppleNewsSkipPushProductionEnvironment() {
		$skip = false;

		putenv( 'WP_ENVIRONMENT_TYPE=production' );
		$_ENV['WP_ENVIRONMENT_TYPE'] = 'production';

		$result = $this->feature->filter_apple_news_skip_push( $skip );

		$this->assertFalse( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a production_ environment.
	 */
	public function testTrueFilterAppleNewsSkipPushProductionEnvironment() {
		$skip = true;

		putenv( 'WP_ENVIRONMENT_TYPE=production' );
		$_ENV['WP_ENVIRONMENT_TYPE'] = 'production';

		$result = $this->feature->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed false on a non-production_ environment.
	 */
	public function testFalseFilterAppleNewsSkipPushOtherEnvironments() {
		$skip = false;

		putenv( 'WP_ENVIRONMENT_TYPE=local' );
		$_ENV['WP_ENVIRONMENT_TYPE'] = 'local';

		$result = $this->feature->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a non-production_ environment.
	 */
	public function testTrueFilterAppleNewsSkipPushOtherEnvironments() {
		$skip = true;

		putenv( 'WP_ENVIRONMENT_TYPE=local' );
		$_ENV['WP_ENVIRONMENT_TYPE'] = 'local';

		$result = $this->feature->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

}
