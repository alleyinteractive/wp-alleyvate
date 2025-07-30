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

declare(strict_types=1);

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

use Byline_Manager\Models\Profile;

use Mantle\Database\Model\User;
use Mantle\Testkit\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

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

		require_once trailingslashit( \ABSPATH ) . 'wp-admin/includes/plugin.php';

		if (
			in_array( 'byline-manager', $this->groups(), true ) &&
			! $this->byline_manager_installed
		) {
			// Enforce Byline Manager plugin activation here.
			if ( file_exists( \WP_PLUGIN_DIR . '/byline-manager/byline-manager.php' ) ) {
				\activate_plugin( 'byline-manager/byline-manager.php' );
				$this->byline_manager_installed = true;
			}
		}

		if (
			in_array( 'coauthors-plus', $this->groups(), true ) &&
			! $this->co_authors_plus_installed
		) {
			// Enforce Byline Manager plugin activation here.
			if ( file_exists( \WP_PLUGIN_DIR . '/co-authors-plus/co-authors-plus.php' ) ) {
				\activate_plugin( 'co-authors-plus/co-authors-plus.php' );
				$this->co_authors_plus_installed = true;
			}
		}
	}

	/**
	 * Set the current environment value.
	 *
	 * @param string $environment The environment name to use.
	 */
	protected function setEnvironment( string $environment ): void {
		// Required because `wp_get_environment_type` uses `getenv` to retrieve the value.
		putenv( 'WP_ENVIRONMENT_TYPE=' . $environment ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
		$_ENV['WP_ENVIRONMENT_TYPE'] = $environment;
	}

	/**
	 * Ensure Alley users (as identified by an email address at one of Alley's domains) do
	 * not have author archive pages (they should 404)
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
	#[Group( 'coauthors-plus' )]
	public function test_ensure_co_authors_plus_profiles_linked_to_alley_users_do_not_have_author_archive() {
		if ( ! $this->co_authors_plus_installed ) {
			$this->markTestSkipped( 'Co-Authors Plus is not available.' );
		}

		$this->markTestIncomplete();
	}

	/**
	 * Ensure Byline Manager profiles linked to Alley users do not have author archives
	 */
	#[Group( 'byline-manager' )]
	public function test_ensure_byline_manager_profiles_linked_to_alley_users_do_not_have_author_archive() {
		if ( ! $this->byline_manager_installed ) {
			$this->markTestSkipped( 'Byline Manager is not available.' );
		}
		$this->feature->boot();

		$profile = Profile::create_from_user( $this->alley_account->core_object() );

		$post = $this->factory()->post->create( [ 'post_author' => $this->alley_account->ID ] );

		\Byline_Manager\Utils::assign_bylines_to_post( $post, [ $profile->byline_id ] );

		$this->get( $profile->link )
			->assertStatus( 404 );
	}

	/**
	 * Filter author names for traditional authors data so filtered users don't appear as
	 * their actual names, but rather a generic "Staff" name.
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
	#[Group( 'coauthors-plus' )]
	public function test_alley_author_names_appear_as_generic_staff_name_in_co_authors_plus() {
		if ( ! $this->co_authors_plus_installed ) {
			$this->markTestSkipped( 'Co-Authors Plus is not available.' );
		}

		$this->markTestIncomplete();
	}

	/**
	 * Filter author names for Byline Manager so filtered users appear as "Staff" instead of
	 * their display name.
	 */
	#[Group( 'byline-manager' )]
	public function test_alley_author_names_appear_as_generic_staff_name_in_byline_manager() {
		if ( ! $this->byline_manager_installed ) {
			$this->markTestSkipped( 'Byline Manager is not available.' );
		}
		$this->feature->boot();

		$profile = Profile::create_from_user( $this->alley_account->core_object() );

		$post = $this->factory()->post->create( [ 'post_author' => $this->alley_account->ID ] );

		\Byline_Manager\Utils::assign_bylines_to_post( $post, [ $profile->byline_id ] );

		$this->get( get_permalink( $post ) )
			->assertOk()
			->assertDontSee( '>' . $profile->display_name . '<' )
			->assertSee( '>Staff<' );
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
	 * @param string $email The email address to test.
	 * @param bool   $expected_result Whether or not the comparison should work.
	 */
	#[DataProvider( 'emailProvider' )]
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
	 */
	#[DataProvider( 'emailProvider' )]
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
	 * The environment list should be filterable, with production being the default.
	 */
	public function test_environment_list_is_filterable() {
		// Temporarily enable feature loading, but disable for all environments.
		remove_filter( 'alleyvate_load_feature', '__return_false' );

		$this->setEnvironment( 'development' );

		// Allow this feature for our test environment.
		$filter = fn() => [ 'development' ];
		add_filter( 'alleyvate_disable_alley_authors_environments', $filter );

		$feature = new Feature( 'disable_alley_authors', $this->feature );
		$feature->filtered_boot();

		$debug_information = $feature->add_debug_information( [] );

		/*
		 * We can use the debug information to confirm that our feature
		 * was actually enabled during this process.
		 */
		$this->assertSame( 'Enabled', $debug_information['wp-alleyvate']['fields'][0]['value'] );

		// Clean up.
		remove_filter( 'alleyvate_disable_alley_authors_environments', $filter );
		add_filter( 'alleyvate_load_feature', '__return_false' );
	}

	/**
	 * Add a filter to conditionally enable/disable features by environment, which passes the
	 * feature and the environment name, using defaults from the feature (with the typical case
	 * of a feature being enabled on all environments) so this can be filtered a high level.
	 */
	public function test_high_level_enable_disable_filter_exists_to_allow_enabling_feature_by_environment() {

		// Temporarily enable feature loading, but disable for all environments.
		remove_filter( 'alleyvate_load_feature', '__return_false' );

		$this->setEnvironment( 'development' );

		$filter = function ( $load, $environment ): bool {
			if ( 'production' === $environment ) {
				return true;
			}

			return false;
		};

		add_filter( 'alleyvate_load_example_feature_in_environment', $filter, 10, 2 );

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

		// Remove test filter.
		remove_filter( 'alleyvate_load_example_feature_in_environment', $filter );

		// Force feature to load.
		add_filter( 'alleyvate_load_example_feature_in_environment', '__return_true' );

		$feature->filtered_boot();

		$this->assertSame( 1, $dummy->getCounter() );

		// Remove customizations of filters.
		remove_filter( 'alleyvate_load_example_feature_in_environment', '__return_true' );
		add_filter( 'alleyvate_load_feature', '__return_false' );
	}
}
