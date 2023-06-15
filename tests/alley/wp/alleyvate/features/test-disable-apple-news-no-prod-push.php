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

class Disable_Apple_News_No_Prod_Push_Test extends Test_Case {
	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Need to remove the current definition of WP_ENV.
	}

	// Test that the filter_apple_news_skip_push method returns false when passed false on a production environment.
	public function testFalseFilterAppleNewsSkipPushProductionEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = false;
		define('WP_ENV', 'production');

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertFalse($result);
	}

	// Test that the filter_apple_news_skip_push method returns false when passed false on a Pantheon live environment.
	public function testFalseFilterAppleNewsSkipPushPantheonLiveEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = false;
		$_ENV['PANTHEON_ENVIRONMENT'] = 'live';

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertFalse($result);
	}

	// Test that the filter_apple_news_skip_push method returns false when passed false on a VIP production environment.
	public function testFalseFilterAppleNewsSkipPushVipProductionEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = false;
		define('VIP_GO_ENV', 'production');

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertFalse($result);
	}

	// Test that the filter_apple_news_skip_push method returns true when passed true on a non-production environment.
	public function testTrueFilterAppleNewsSkipPushProductionEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = true;
		define('WP_ENV', 'production');

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertTrue($result);
	}

	// Test that the filter_apple_news_skip_push method returns true when passed true on a Pantheon live environment.
	public function testTrueFilterAppleNewsSkipPushPantheonLiveEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = true;
		$_ENV['PANTHEON_ENVIRONMENT'] = 'live';

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertTrue($result);
	}

	// Test that the filter_apple_news_skip_push method returns true when passed true on a VIP production environment.
	public function testTrueFilterAppleNewsSkipPushVipProductionEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = true;
		define('VIP_GO_ENV', 'production');

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertTrue($result);
	}

	public function testFilterAppleNewsSkipPushOtherEnvironments() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip = false;

		$result = $instance->filter_apple_news_skip_push($skip);

		$this->assertTrue($result);
	}
}
