<?php
/**
 * Loads_Classes trait file.
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

use Mantle\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function Mantle\Support\Helpers\collect;

/**
 * Trait for loading classes
 */
trait Loads_Classes {
	/**
	 * Retrieves expected classes from a folder in a respective namespace.
	 *
	 * @param string $path Path to load.
	 * @param string $root_namespace Root namespace for the files.
	 * @return string[]
	 */
	public static function classes_from_path( string $path, string $root_namespace ): array {
		$classes = [];

		foreach ( ( new Finder() )->name( '*.php' )->in( $path ) as $file ) {
			$class = static::classname_from_path( $file, $root_namespace );

			if ( $class ) {
				$classes[] = $class;
			}
		}

		return $classes;
	}

	/**
	 * Retrieve the class name from a file.
	 *
	 * @param SplFileInfo $file File instance.
	 * @param string      $root_namespace Root namespace.
	 */
	public static function classname_from_path( SplFileInfo $file, string $root_namespace ): ?string {
		// Append the relative path as a namespace to the root namespace.
		if ( $relative_path = $file->getRelativePath() ) {
			$root_namespace .= '\\' . str_replace( '/', '\\', $relative_path );
		}

		$class_name = $root_namespace . '\\' . ( new Filesystem() )->guess_class_name( $file->getRealPath() );

		if ( class_exists( $class_name ) ) {
			return $class_name;
		}

		return null;
	}
}
