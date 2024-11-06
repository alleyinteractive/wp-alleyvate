<?php
/**
 * Has_Factory trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use Mantle\Database\Factory\Factory;
use Mantle\Database\Factory\Post_Factory;
use Mantle\Database\Factory\Term_Factory;

/**
 * Trait to add a factory to a model.
 *
 * @template TObject
 */
trait Has_Factory {
	/**
	 * Create a builder for the model.
	 *
	 * @param array|callable $state Default state array or callable that will be invoked to set state.
	 * @return \Mantle\Database\Factory\Factory<static, TObject, static>
	 */
	public static function factory( array|callable|null $state = null ): Factory {
		$factory = static::new_factory() ?: Factory::factory_for_model( static::class );

		return $factory
			->as_models()
			->with_model( static::class )
			->when(
				$factory instanceof Post_Factory,
				fn ( Post_Factory $factory ) => $factory->with_post_type( static::get_object_name() ), // @phpstan-ignore-line expects
			)
			->when(
				$factory instanceof Term_Factory,
				fn ( Term_Factory $factory ) => $factory->with_taxonomy( static::get_object_name() ), // @phpstan-ignore-line expects
			)
			->when( is_array( $state ) || is_callable( $state ), fn ( Factory $factory ) => $factory->state( $state ) );
	}

	/**
	 * Create a new factory instance for the model.
	 *
	 * Optional: allows for the model factory to be overridden by application code.
	 *
	 * @return \Mantle\Database\Factory\Factory<static, TObject, static>|null
	 */
	protected static function new_factory(): ?Factory {
		return null;
	}
}
