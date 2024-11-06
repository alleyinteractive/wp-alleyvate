<?php
/**
 * PHPUnit_Upgrade_Warning trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Runner\Version;
use PHPUnit\TextUI\Configuration\Registry;
use PHPUnit\TextUI\Configuration\TestSuite;

use function Mantle\Support\Helpers\collect;
use function Termwind\render;

/**
 * Provide a warning to users who are attempting to run PHPUnit 10+ against an
 * incompatible codebase.
 *
 * PHPUnit 10+ requires code bases to use PSR-4 standards for test classes. We
 * also need to be careful of any possible changes to the PHPUnit classes we're
 * referencing because they are not covered by the backward compatibility promise
 * for PHPUnit.
 *
 * @mixin \Mantle\Testing\Installation_Manager
 */
trait PHPUnit_Upgrade_Warning {
	/**
	 * Whether to silence the PHPUnit 10+ warning.
	 */
	public bool $silence_phpunit_warning = false;

	/**
	 * Check if the current codebase is running PHPUnit 10 or higher.
	 */
	protected function is_running_phpunit_10_or_higher(): bool {
		// Prevent conflicts if the internal class API changes.
		if ( ! class_exists( Version::class ) || ! method_exists( Version::class, 'id' ) ) {
			return false;
		}

		return version_compare( '10.0.0', Version::id(), '<=' );
	}

	/**
	 * Warn the user if they're running PHPUnit 10+ against an incompatible test suite.
	 *
	 * @param TestSuite $test_suite Test suite configuration.
	 */
	protected function warn_if_test_suite_contains_legacy_test_cases( TestSuite $test_suite ): void {
		if ( $this->silence_phpunit_warning ) {
			return;
		}

		// Prevent conflicts if the internal class API changes.
		if ( ! method_exists( $test_suite, 'directories' ) ) {
			return;
		}

		// Check if the test suite contains directories with a 'test-' prefix. That
		// is a clear sign of a legacy test suite.
		$legacy_test_cases = collect( $test_suite->directories() )
			->filter(
				fn ( $directory ) => method_exists( $directory, 'prefix' ) && 'test-' === $directory->prefix(),
			);

		if ( $legacy_test_cases->is_empty() ) {
			return;
		}

		render(
			<<<HTML
			<div class="space-y-1">
				<div class="bg-red-300 text-red-700 pt-2 py-2">
					<strong>🚨 Warning:</strong> You are running PHPUnit 10+ against a test suite that contains legacy test cases.
				</div>
				<div>
					<span class="text-blue-300">Mantle Testing Framework 1.1</span> includes <span class="text-yellow-500 font-bold">✨ PHPUnit 11 ✨</span> which requires test cases to follow PSR-4 standards.
					<br />
					For example, that would be <span class="italic">tests/Feature/MyExampleTest.php</span> instead of <span class="italic">tests/feature/test-my-example.php</span>.
					<br />
					Fear not, you don't have to upgrade to PHPUnit 11 right away. You can still keep your test suite as-is and use PHPUnit 9 by running the following code:

					<div class="ml-2 mt-1 italic">composer require --dev phpunit/phpunit:^9 nunomaduro/collision:^6 -W</div>
				</div>
				<div>
					For more information and tips on how to upgrade your codebase to PHPUnit 10, please refer to the 1.0 Release Changelog:

					<div class="ml-2 my-1 italic">
						https://github.com/alleyinteractive/mantle-framework/blob/1.x/CHANGELOG.md#phpunit-10-migration
					</div>
				</div>
			</div>
			HTML,
		);

		$this->silence_phpunit_warning();
	}

	/**
	 * Warn the user if they're running PHPUnit 10+ against an incompatible codebase.
	 */
	protected function warn_if_phpunit_10_or_higher(): void {
		if ( $this->silence_phpunit_warning || ! $this->is_running_phpunit_10_or_higher() ) {
			return;
		}

		if ( ! class_exists( Registry::class ) || ! method_exists( Registry::class, 'get' ) ) {
			return;
		}

		$registry = Registry::get();

		foreach ( $registry->testSuite()->asArray() as $test_suite ) {
			if ( $test_suite instanceof TestSuite ) {
				$this->warn_if_test_suite_contains_legacy_test_cases( $test_suite );
			}
		}
	}

	/**
	 * Silence the PHPUnit 10+ warning.
	 */
	public function silence_phpunit_warning(): static {
		$this->silence_phpunit_warning = true;

		return $this;
	}
}
