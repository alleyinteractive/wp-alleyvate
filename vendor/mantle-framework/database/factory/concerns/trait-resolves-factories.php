<?php
/**
 * Resolves_Factories trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Concerns;

use InvalidArgumentException;
use Mantle\Contracts\Application;
use Mantle\Container\Container;
use Mantle\Database\Factory;
use Mantle\Database\Model;
use Mantle\Support\Str;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\str;

/**
 * Trait to resolve model factories.
 *
 * @mixin \Mantle\Database\Factory\Factory
 */
trait Resolves_Factories {
	/**
	 * The callback that should be invoked to resolve model factories.
	 *
	 * @var callable(class-string<\Mantle\Database\Model\Model>): class-string<\Mantle\Database\Factory\Factory>
	 */
	protected static $factory_name_resolver;

	/**
	 * Get a new factory instance for the given model.
	 *
	 * @param class-string<\Mantle\Database\Model\Model> $model Model class name.
	 */
	public static function factory_for_model( string $model ): Factory\Factory {
		$factory = static::resolve_factory_name( $model );

		return Container::get_instance()->make( $factory );
	}

	/**
	 * Specify the callback that should be invoked to resolve model factories.
	 *
	 * @param callable(class-string<\Mantle\Database\Model\Model>): class-string<\Mantle\Database\Factory\Factory> $resolver Callable resolver.
	 */
	public static function resolve_factory_using( callable $resolver ): void {
		static::$factory_name_resolver = $resolver;
	}

	/**
	 * Resolve the factory name for a model.
	 *
	 * Attempts to resolve the custom factory name for the given model. If that
	 * is not found, it will attempt to resolve the default factory name for
	 * the given model (e.g. a post model would use Mantle\Database\Factory\Post_Factory).
	 *
	 * @param string $model
	 */
	public static function resolve_factory_name( string $model ): string {
		$custom_factory = static::resolve_custom_factory_name( $model );

		if ( class_exists( $custom_factory ) ) {
			return $custom_factory;
		}

		return static::default_factory_name( $model );
	}

	/**
	 * Resolve the factory name for the given model.
	 *
	 * Attempts to resolve a factory name for the given model with an option to
	 * override the resolver using a callback.
	 *
	 * @param class-string<\Mantle\Database\Model\Model> $model_name Model class
	 * name.
	 * @return class-string<\Mantle\Database\Factory\Factory>
	 */
	public static function resolve_custom_factory_name( string $model_name ): string {
		$resolver = static::$factory_name_resolver ?? function ( string $model_name ) {
			$app_namespace = static::app_namespace();

			return Str::of( $model_name )
				->after( $app_namespace . 'Models\\' )
				->prepend( $app_namespace . 'Database\\Factory\\' )
				->append( '_Factory' );
		};

		return $resolver( $model_name );
	}

	/**
	 * Resolve the default factory name for the given model.
	 *
	 * Attempts to resolve the type of model and use a built-in factory.
	 *
	 * @throws \InvalidArgumentException If the model is not a Mantle model.
	 *
	 * @param class-string<\Mantle\Database\Model\Model> $model_name Model class.
	 * @return class-string<\Mantle\Database\Factory\Factory>
	 */
	public static function default_factory_name( string $model_name ): string {
		$parent_classes  = collect( class_parents( $model_name ) );
		$model_namespace = 'Mantle\\Database\\Model';

		// Attempt to resolve the parent class that is a model and not the base
		// model class.
		$parent_class = $parent_classes->first(
			fn ( string $parent_class ) => Model\Model::class !== $parent_class
				&& str( $parent_class )->startsWith( $model_namespace ),
		);

		// Use the model name itself if the model is a base model class.
		if ( ! $parent_class && str( $model_name )->startsWith( $model_namespace ) ) {
			$parent_class = $model_name;
		}

		if ( ! $parent_class ) {
			throw new InvalidArgumentException(
				"Unable to resolve parent model for [{$model_name}]."
			);
		}

		// Handle one-off models.
		if ( Model\Site::class === $parent_class ) {
			return Factory\Blog_Factory::class;
		}

		$parent_class = Str::after_last( $parent_class, '\\' );

		// Translate the parent model class to the respective factory class.
		$factory_name = str( $parent_class )
			->after_last( '\\' )
			->prepend( 'Mantle\\Database\\Factory\\' )
			->append( '_Factory' )
			->value();

		if ( ! class_exists( $factory_name ) ) {
			throw new InvalidArgumentException(
				"Unable to resolve factory for model [{$model_name}]."
			);
		}

		return $factory_name;
	}

	/**
	 * Get the application namespace for the application.
	 */
	protected static function app_namespace(): string {
		try {
			$container = Container::get_instance();

			if ( $container instanceof Application ) {
				return str( $container->get_namespace() )->rtrim( '\\' )->append( '\\' );
			}

			return 'App\\';
		} catch ( \Throwable ) {
			return 'App\\';
		}
	}
}
