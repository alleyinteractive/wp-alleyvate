<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * Dependency_Assertions trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use Mantle\Support\Collection;

trait Dependency_Assertions {

	/**
	 * Asserts that the contents of an array are loaded prior to testing.
	 *
	 * Array can be an array of strings or an array of arrays where the first value is a string -- for instance,
	 * a data provider.
	 *
	 * @param array $dependencies Dependencies array.
	 */
	public static function assertDependenciesLoaded( array $dependencies ): void {
		if ( count( $dependencies ) === 0 ) {
			PHPUnit::markTestIncomplete( 'Asserting an empty dependency array has been loaded does not assert that no dependencies have been loaded.' );
		}

		foreach ( $dependencies as $dependency ) {
			if ( is_array( $dependency ) ) {
				$dependency = array_shift( $dependency );
			}

			if ( ! is_string( $dependency ) ) {
				PHPUnit::fail( 'Dependency type not string.' );
				continue;
			}

			self::assertDependencyLoaded( $dependency );
		}
	}

	/**
	 * Asserts that a file is loaded.
	 *
	 * @param string $dependency The dependency file name.
	 */
	public static function assertDependencyLoaded( string $dependency ): void {
		static $includes;

		if (
			! is_array( $includes ) ||
			empty( $includes )
		) {
			$includes = new Collection( get_included_files() );
		}

		PHPUnit::assertTrue(
			$includes
				->filter( fn ( $file ) => strpos( $file, $dependency ) !== false )
				->count() > 0,
			sprintf(
				'%s dependency not found in included files.',
				$dependency
			)
		);
	}
}
