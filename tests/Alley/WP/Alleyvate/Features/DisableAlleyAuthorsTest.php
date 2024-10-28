<?php
/**
 * Class file for DisableAlleyAuthorsTest
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

use Alley\WP\Alleyvate\Feature;

use Mantle\Database\Model\User;
use Mantle\Testkit\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;

/**
 * Tests for confirming Alley usernames authors do not appear on the frontend as authors.
 */
final class DisableAlleyAuthorsTest extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Alley_Authors
	 */
	private Disable_Alley_Authors $feature;

	/**
	 * Alley User test account.
	 *
	 * @var Mantle\Database\Model\User
	 */
	private User $alley_account;

	/**
	 * Non-Alley User test account.
	 *
	 * @var Mantle\Database\Model\User
	 */
	private User $non_alley_account;

	/**
	 * Determine if Byline Manager is installed and available.
	 *
	 * @var bool
	 */
	private bool $byline_manager_installed;

	/**
	 * Determine if Co-Authors Plus is installed and available.
	 *
	 * @var bool
	 */
	private bool $co_authors_plus_installed;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Alley_Authors();

		$this->alley_account = $this->factory()->user->as_models()->create_and_get(
			[
				'user_email' => 'user@alley.com',
				'role'       => 'administrator',
			]
		);

		$this->non_alley_account = $this->factory()->user->as_models()->create_and_get(
			[
				'user_email' => 'user@example.com',
				'role'       => 'editor',
			]
		);

		$this->byline_manager_installed  = class_exists( 'Byline_Manager\Core_Author_Block' );
		$this->co_authors_plus_installed = class_exists( 'CoAuthors_Plus' );
	}

	/**
	 * Ensure Alley users (as identified by an email address at one of Alley's domains) do
	 * not have author archive pages (they should 404)
	 *
	 * @test
	 */
	public function test_ensure_alley_users_do_not_have_author_archive_pages() {
		$this->feature->boot();

		$this->factory()->post->create( [ 'post_author' => $this->alley_account->ID ] );
		$this->factory()->post->create( [ 'post_author' => $this->non_alley_account->ID ] );

		$this->get( $this->alley_account->permalink() )
			->assertStatus( 404 );

		$this->get( $this->non_alley_account->permalink() )
			->assertOk();
	}

	/**
	 * Ensure Co-Authors Plus profiles linked to Alley users do not have author archives
	 */
	public function test_ensure_co_authors_plus_profiles_linked_to_alley_users_do_not_have_author_archive() {
		$this->markTestIncomplete();
	}

	/**
	 * Ensure Byline Manager profiles linked to Alley users do not have author archives
	 */
	public function test_ensure_byline_manager_profiles_linked_to_alley_users_do_not_have_author_archive() {
		$this->markTestIncomplete();
	}

	/**
	 * Filter author names for traditional authors data so filtered users don't appear as
	 * their actual names, but rather a generic "Staff" name.
	 *
	 * @test
	 */
	public function test_alley_author_names_appear_as_generic_staff_name() {
		$this->feature->boot();

		$post = $this->factory()->post
								->as_models()
								->create_and_get( [ 'post_author' => $this->alley_account->ID ] );

		$this->get( $post->permalink() )
			->assertOk()
			->assertDontSee( '>' . $this->alley_account->name . '<' )
			->assertSee( '>Staff<' );
	}

	/**
	 * Filter author URLs for traditional authors data so filtered users don't get author
	 * links.
	 *
	 * @test
	 */
	public function test_alley_author_urls_do_not_render() {
		$this->feature->boot();

		$post = $this->factory()->post
								->as_models()
								->create_and_get( [ 'post_author' => $this->alley_account->ID ] );

		$this->get( $post->permalink() )
			->assertOk()
			->assertDontSee( '/author/' . $this->alley_account->slug );
	}

	/**
	 * Filter author names for Co-Authors Plus so filtered users appear as "Staff" instead of
	 * their display name.
	 */
	public function test_alley_author_names_appear_as_generic_staff_name_in_co_authors_plus() {
		$this->markTestIncomplete();
	}

	/**
	 * Filter author names for Byline Manager so filtered users appear as "Staff" instead of
	 * their display name.
	 */
	public function test_alley_author_names_appear_as_generic_staff_name_in_byline_manager() {
		$this->markTestIncomplete();
	}

	/**
	 * Data Provider for testing emails.
	 *
	 * @return array;
	 */
	public static function emailProvider(): array {
		return [
			[ 'user1@alley.com', true ],
			[ 'user1@alley.co', true ],
			[ 'user1@example.com', false ],
			[ 'user1@example.co', false ],
			[ 'alley.com@example.co', false ],
			[ 'alley.co@example.co', false ],
		];
	}

	/**
	 * Generate a list of user accounts by email domain, defaulting to include Alley domains.
	 *
	 * @param string $email           The email address to test.
	 * @param bool   $expected_result Whether or not the comparison should work.
	 * @test
	 * @dataProvider emailProvider
	 */
	public function test_user_array_generated_by_email_domain( string $email, bool $expected_result ) {
		$this->assertSame(
			$expected_result,
			Disable_Alley_Authors::is_staff_author( $email ),
			sprintf(
				'Email %s was expected to be %s but returned %s.',
				$email,
				( $expected_result ) ? 'true' : 'false',
				( ! $expected_result ) ? 'true' : 'false',
			),
		);
	}

	/**
	 * Allow the list of domains for this feature to be filtered. To test this, we take our
	 * list of test emails and invert the expected results.
	 *
	 * @param string $email           The email address to test.
	 * @param bool   $expected_result Whether or not the comparison should work.
	 * @test
	 * @dataProvider emailProvider
	 */
	public function test_email_domains_is_filterable( string $email, bool $expected_result ) {
		// Define the set of domains to be non-alley domains.
		$filter = fn() => [ 'example.com', 'example.co' ];
		add_filter( 'alleyvate_staff_author_domains', $filter );

		/*
		 * The filter above should provide us with the exact opposite results as defined
		 * in the dataProvider so we invert that expectation here.
		 */
		$expected_result = ! $expected_result;

		// Now that we've inverted expectations, compare against reality.
		$this->assertSame(
			$expected_result,
			Disable_Alley_Authors::is_staff_author( $email ),
			sprintf(
				'Email %s was expected to be %s but returned %s.',
				$email,
				( $expected_result ) ? 'true' : 'false',
				( ! $expected_result ) ? 'true' : 'false',
			),
		);

		remove_filter( 'alleyvate_staff_author_domains', $filter );
	}

	/**
	 * Only apply this behavior to production by default, but allow the list of environments to
	 * be filtered using values from wp_get_environment_type.
	 */
	public function test_only_filter_production_by_default() {
		$this->markTestIncomplete();
	}

	/**
	 * The environment list should be filterable, with production being the default.
	 */
	public function test_environment_list_is_filterable() {
		$this->markTestIncomplete();
	}

	/**
	 * Add a filter to conditionally enable/disable features by environment, which passes the
	 * feature and the environment name, using defaults from the feature (with the typical case
	 * of a feature being enabled on all environments) so this can be filtered a high level.
	 */
	public function test_high_level_enable_disable_filter_exists_to_allow_enabling_feature_by_environment() {

		// Temporarily enable feature loading, but disable for all environments.
		remove_filter( 'alleyvate_load_feature', '__return_false' );
		add_filter( 'alleyvate_load_in_environment', '__return_false' );

		/**
		 * A test dummy feature that increments a counter whenever booted.
		 */
		$dummy = new class() implements \Alley\WP\Types\Feature {
			/**
			 * Counter to count how many times the boot method is run.
			 *
			 * @var int
			 */
			public static $counter = 0;

			/**
			 * Boot of test dummy feature.
			 */
			public function boot(): void {
				self::$counter++;
			}

			/**
			 * Get the counter.
			 *
			 * @return int
			 */
			public function getCounter(): int {
				return self::$counter;
			}
		};

		$feature = new Feature( 'example_feature', $dummy );
		$feature->filtered_boot();

		$this->assertSame( 0, $dummy->getCounter() );

		// Remove customizations of filters.
		remove_filter( 'alleyvate_load_in_environment', '__return_false' );
		add_filter( 'alleyvate_load_feature', '__return_false' );
	}
}
