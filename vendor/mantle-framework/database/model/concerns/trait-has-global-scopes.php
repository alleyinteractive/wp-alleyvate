<?php
/**
 * Has_Global_Scopes class file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
 */

namespace Mantle\Database\Model\Concerns;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Database\Scope;
use Mantle\Support\Arr;

/**
 * Query Global Scope
 */
trait Has_Global_Scopes {
	/**
	 * Register a new global scope on the model.
	 *
	 * @param Scope|\Closure|string $scope Scope instance/name.
	 * @param Closure|null          $implementation Scope callback.
	 * @return mixed
	 *
	 * @throws InvalidArgumentException Thrown on invalid global scope.
	 */
	public static function add_global_scope( $scope, Closure $implementation = null ) {
		if ( is_string( $scope ) && ! is_null( $implementation ) ) {
			static::$global_scopes[ static::class ][ $scope ] = $implementation;
			return true;
		} elseif ( $scope instanceof Closure ) {
			static::$global_scopes[ static::class ][ spl_object_hash( $scope ) ] = $scope;
			return true;
		} elseif ( $scope instanceof Scope ) {
			static::$global_scopes[ static::class ][ $scope::class ] = $scope;
			return true;
		}

		throw new InvalidArgumentException( 'Global scope must be an instance of Closure or Scope.' );
	}

	/**
	 * Determine if a model has a global scope.
	 *
	 * @param Scope|string $scope Scope name.
	 */
	public static function has_global_scope( $scope ): bool {
		return ! is_null( static::get_global_scope( $scope ) );
	}

	/**
	 * Get a global scope registered with the model.
	 *
	 * @param Scope|string $scope Scope name/instance.
	 * @return Scope|\Closure|null Scope object.
	 */
	public static function get_global_scope( $scope ) {
		if ( is_string( $scope ) ) {
			return Arr::get( static::$global_scopes, static::class . '.' . $scope );
		}

		return Arr::get(
			static::$global_scopes,
			static::class . '.' . $scope::class
		);
	}

	/**
	 * Get the global scopes for this class instance.
	 */
	public function get_global_scopes(): array {
		return Arr::get( static::$global_scopes, static::class, [] );
	}
}
