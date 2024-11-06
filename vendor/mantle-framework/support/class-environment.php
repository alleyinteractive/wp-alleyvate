<?php
/**
 * Environment class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use PhpOption\Option;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use PhpOption\Some;

use function Mantle\Support\Helpers\value;

/**
 * Storage of environment variables for the application.
 */
class Environment {
	/**
	 * Variable repository.
	 */
	protected static ?RepositoryInterface $repository = null;

	/**
	 * Get the environment repository instance.
	 */
	public static function get_repository(): RepositoryInterface {
		if ( ! isset( static::$repository ) ) {
			$builder = RepositoryBuilder::createWithDefaultAdapters();

			static::$repository = $builder->immutable()->make();
		}

		return static::$repository;
	}

	/**
	 * Clear the environment repository instance.
	 */
	public static function clear(): void {
		static::$repository = null;
	}

	/**
	 * Get the value of an environment variable.
	 *
	 * @param string $key Variable to retrieve.
	 * @param mixed  $default Default value. Supports a closure callback.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$value = Option::fromValue( static::get_repository()->get( $key ) );

		// Fallback to the VIP environment variable if the key is not found.
		if ( $value instanceof \PhpOption\None ) {
			$constant     = strtoupper( $key );
			$vip_constant = "VIP_ENV_VAR_{$key}";

			if ( defined( $vip_constant ) ) {
				$value = new Some( constant( $vip_constant ) );
			} elseif ( defined( $constant ) ) {
				$value = new Some( constant( $constant ) );
			}
		}

		return $value
			->map(
				function ( $value ) {
					switch ( strtolower( (string) $value ) ) {
						case 'true':
						case '(true)':
							return true;
						case 'false':
						case '(false)':
							return false;
						case 'empty':
						case '(empty)':
							return '';
						case 'null':
						case '(null)':
							return;
					}

					if ( preg_match( '/\A([\'"])(.*)\1\z/', (string) $value, $matches ) ) {
						return $matches[2];
					}

					return $value;
				}
			)
			->getOrCall(
				fn () => value( $default )
			);
	}
}
