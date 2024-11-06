<?php
/**
 * Has_Aliases trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

/**
 * Model Aliases
 */
trait Has_Aliases {
	/**
	 * Model aliases.
	 *
	 * @var array<string, string>
	 */
	protected static $aliases = [];

	/**
	 * Check if an alias exists for an attribute.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public static function has_attribute_alias( string $attribute ): bool {
		return ! empty( static::$aliases[ $attribute ] ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
	}

	/**
	 * Get an alias for an attribute.
	 *
	 * @param string $attribute Attribute to get alias for.
	 */
	public static function get_attribute_alias( string $attribute ): string {
		return static::$aliases[ $attribute ] ?? ''; // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
	}
}
