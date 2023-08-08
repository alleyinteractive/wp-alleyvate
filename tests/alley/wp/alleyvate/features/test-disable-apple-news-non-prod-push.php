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
	 * Production instance of Disable_Apple_News_Non_Prod_Push
	 *
	 * @var Disable_Apple_News_Non_Prod_Push
	 */
	protected $production_instance;

	/**
	 * Non-production_ instance of Disable_Apple_News_Non_Prod_Push
	 *
	 * @var Disable_Apple_News_Non_Prod_Push
	 */
	protected $non_production_instance;

	/**
	 * Sets up our mocks.
	 */
	protected function setUp(): void {
		parent::setUp();

		$reflection_class = new \ReflectionClass( 'Alley\WP\Alleyvate\Features\Disable_Apple_News_Non_Prod_Push' );

		$production_property = $reflection_class->getProperty( 'is_production' );
		$production_property->setAccessible( true );
		$this->production_instance = new Disable_Apple_News_Non_Prod_Push();
		$production_property->setValue( $this->production_instance, true );

		$non_production_property = $reflection_class->getProperty( 'is_production' );
		$non_production_property->setAccessible( true );
		$this->non_production_instance = new Disable_Apple_News_Non_Prod_Push();
		$non_production_property->setValue( $this->non_production_instance, false );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns false when passed false on a production_ environment.
	 */
	public function testFalseFilterAppleNewsSkipPushProductionEnvironment() {
		$skip = false;

		$result = $this->production_instance->filter_apple_news_skip_push( $skip );

		$this->assertFalse( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a production_ environment.
	 */
	public function testTrueFilterAppleNewsSkipPushProductionEnvironment() {
		$skip = true;

		$result = $this->production_instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed false on a non-production_ environment.
	 */
	public function testFalseFilterAppleNewsSkipPushOtherEnvironments() {
		$skip = false;

		$result = $this->non_production_instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a non-production_ environment.
	 */
	public function testTrueFilterAppleNewsSkipPushOtherEnvironments() {
		$skip = true;

		$result = $this->non_production_instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

}
