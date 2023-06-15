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

/**
 * Test Disable_Apple_News_No_Prod_Push
 */
class Disable_Apple_News_No_Prod_Push_Test extends Test_Case {
	/**
	 * Mocks the is_production_environment method.
	 *
	 * @param bool $result The result to mock.
	 * @return void
	 */
	protected function mockResult( $result ) {
		$mock = $this->getMockBuilder( 'Disable_Apple_News_No_Prod_Push' )
			->setMethods( [ 'is_production_environment' ] )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'is_production_environment' )
			->willReturn( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns false when passed false on a production environment.
	 *
	 * @return void
	 */
	public function testFalseFilterAppleNewsSkipPushProductionEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip     = false;
		$this->mockResult( true );

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertFalse( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a production environment.
	 *
	 * @return void
	 */
	public function testTrueFilterAppleNewsSkipPushProductionEnvironment() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip     = true;
		$this->mockResult( true );

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed false on a non-production environment.
	 *
	 * @return void
	 */
	public function testFalseFilterAppleNewsSkipPushOtherEnvironments() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip     = false;
		$this->mockResult( false );

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

	/**
	 * Test that the filter_apple_news_skip_push method returns true when passed true on a non-production environment.
	 *
	 * @return void
	 */
	public function testTrueFilterAppleNewsSkipPushOtherEnvironments() {
		$instance = new Disable_Apple_News_No_Prod_Push();
		$skip     = true;
		$this->mockResult( false );

		$result = $instance->filter_apple_news_skip_push( $skip );

		$this->assertTrue( $result );
	}

}
