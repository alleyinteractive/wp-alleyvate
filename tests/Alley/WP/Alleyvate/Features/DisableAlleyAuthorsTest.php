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

use Mantle\Testkit\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;
use WP_User;

use function Mantle\Support\Helpers\collect;

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
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Alley_Authors();
	}

	/**
	 * Ensure Alley users (as identified by an email address at one of Alley's domains) do not have author archive pages (they should 404)
	 */
	public function test_ensure_alley_users_do_not_have_author_archive_pages() {
		$this->markTestIncomplete();
	}

	/**
	 * Ensure Byline Manager and Co-Authors Plus profiles linked to Alley users do not have author archives
	 */
	public function test_ensure_byline_manager_profiles_linked_to_alley_users_do_not_have_author_archive() {
		$this->markTestIncomplete();
	}

	/**
	 * Filter author names for traditional authors, Byline Manager, and Co-Authors Plus so that Alley users don't appear as their actual names, but rather a generic "Staff" name
	 */
	public function test_alley_author_names_appear_as_generic_staff_name() {
		$this->markTestIncomplete();
	}

	/**
	 * Generate a list of user accounts by email domain, defaulting to include Alley domains.
	 *
	 * @test
	 */
	public function test_user_array_generated_by_email_domain() {
		// Generate test accounts.
		$alley_emails = [
			'user1@alley.com',
			'user2@alley.com',
			'user3@alley.co',
			'user4@alley.co',
		];

		$non_alley_emails = [
			'user1@example.com',
			'user2@example.com',
			'user3@example.co',
			'user4@example.co',
		];

		foreach ( array_merge( $alley_emails, $non_alley_emails ) as $email ) {
			$this->factory()->user->create( [ 'user_email' => $email ] );
		}

		$alley_authors = collect( Disable_Alley_Authors::get_staff_authors() );

		// Verify the correct number of accounts was found.
		$this->assertCount(
			4,
			$alley_authors,
			'Incorrect number of accounts found.'
		);

		// Verify that the correct emails were found.
		$this->assertCount(
			4,
			$alley_authors
				->map( fn( $user ) => ( is_object( $user ) && isset( $user->user_email ) ) ? $user->user_email : $user )
				->filter( fn( $email ) => in_array( $email, $alley_emails, true ) ),
			'Not all filterable accounts located, or incorrect data returned.'
		);
	}

	/**
	 * Allow the list of domains for this feature to be filtered
	 */
	public function test_email_domains_is_filterable() {
		$this->markTestIncomplete();
	}

	/**
	 * Only apply this behavior to production by default, but allow the list of environments to be filtered using values from wp_get_environment_type.
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
	 * Add a filter to conditionally enable/disable features by environment, which passes the feature and the environment name, using defaults from the feature (with the typical case of a feature being enabled on all environments) so this can be filtered a high level.
	 */
	public function test_high_level_enable_disable_filter_exists_to_allow_enabling_feature_by_environment() {
		$this->markTestIncomplete();
	}
}
