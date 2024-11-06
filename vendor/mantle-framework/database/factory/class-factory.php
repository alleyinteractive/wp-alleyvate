<?php
/**
 * Factory class file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, Squiz.Commenting.FunctionComment.ParamNameNoMatch
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Closure;
use Faker\Generator;
use Mantle\Contracts\Database\Core_Object;
use Mantle\Database\Model\Model;
use Mantle\Support\Collection;
use Mantle\Support\Pipeline;
use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Macroable;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Base Factory
 *
 * @template TModel of \Mantle\Database\Model\Model
 * @template TObject
 * @template TReturnValue
 *
 * @method \Mantle\Database\Factory\Fluent_Factory<TModel, TObject, TReturnValue> count(int $count)
 */
abstract class Factory {
	use Concerns\Resolves_Factories;
	use Conditionable;
	use Macroable {
		__call as macro_call;
	}

	/**
	 * Flag to return the factory as a model.
	 */
	protected bool $as_models = false;

	/**
	 * Array of pipes (middleware) to run through.
	 *
	 * @var \Mantle\Support\Collection<int, callable(array $args, Closure $next): mixed>
	 */
	public Collection $middleware;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string
	 */
	protected string $model;

	/**
	 * Constructor.
	 *
	 * @param Generator $faker The Faker instance.
	 */
	public function __construct( protected Generator $faker ) {
		$this->middleware = new Collection();
	}

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	abstract public function definition(): array;

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return TModel|TObject|null
	 */
	abstract public function get_object_by_id( int $object_id );

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create( array $args = [] ): mixed {
		return $this->make( $args )?->id();
	}

	/**
	 * Creates an object and returns its ID.
	 *
	 * @deprecated Use create() or create_and_get() instead.
	 *
	 * @param array $args The arguments.
	 */
	public function create_object( $args ): int|null {
		return $this->create( $args );
	}

	/**
	 * Generate models from the factory.
	 *
	 * @return static<TModel, TObject, TModel>
	 */
	public function as_models() {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->as_models = true,
		);
	}

	/**
	 * Generate core WordPress objects from the factory.
	 *
	 * @return static<TModel, TObject, TObject>
	 */
	public function as_objects() {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->as_models = false,
		);
	}

	/**
	 * Create a new factory instance with middleware.
	 *
	 * @param callable(array $args, \Closure $next): mixed $middleware Middleware to run the factory through.
	 * @return static
	 */
	public function with_middleware( callable $middleware ) {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->middleware = $this->middleware->merge( $middleware ),
		);
	}

	/**
	 * Create a new factory instance without any middleware.
	 *
	 * This will return the factory to its original state with only the factory
	 * definition applied.
	 *
	 * @return static
	 */
	public function without_middleware() {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->middleware = new Collection(),
		);
	}

	/**
	 * Specify the model to use when creating objects.
	 *
	 * @throws \InvalidArgumentException If the model does not extend from the base model class.
	 *
	 * @template TNewModel of \Mantle\Database\Model\Model
	 *
	 * @param class-string<TNewModel> $model The model to use.
	 * @return static<TNewModel, TObject, TReturnValue>
	 */
	public function with_model( string $model ) {
		// Validate that model extends from the base model class.
		if ( ! is_subclass_of( $model, Model::class ) ) {
			throw new \InvalidArgumentException( 'Model must extend from the base model class.' );
		}

		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->model = $model,
		);
	}

	/**
	 * Retrieve the model to use when creating objects.
	 *
	 * @return class-string<TObject>
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Add a new state transformation to the factory. Functions the same as
	 * middleware but supports returning an array of attributes vs a closure.
	 *
	 * @param (callable(array<string, mixed>): array<string, mixed>|array<string, mixed>) $state The state transformation.
	 * @return static
	 */
	public function state( array|callable $state ) {
		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $state ) {
				$args = array_merge(
					$args,
					is_callable( $state ) ? $state( $args ) : $state,
				);

				return $next( $args );
			},
		);
	}

	/**
	 * Creates multiple objects.
	 *
	 * @param int   $count Amount of objects to create.
	 * @param array $args  Optional. The arguments for the object to create. Default is empty array.
	 *
	 * @return array<int, int>
	 */
	public function create_many( int $count, array $args = [] ) {
		return collect()
			->pad( $count, null )
			->map(
				fn () => $this->create( $args ),
			)
			->to_array();
	}

	/**
	 * Creates an object and returns its object.
	 *
	 * @param array $args Optional. The arguments for the object to create. Default is empty array.
	 * @return TReturnValue The created object.
	 */
	public function create_and_get( array $args = [] ) {
		return $this->get_object_by_id( $this->create( $args ) );
	}

	/**
	 * Pass arguments through the middleware and return a core object.
	 *
	 * @param array $args  Arguments to pass through the middleware.
	 * @return TObject|Core_Object|null
	 */
	protected function make( array $args ) {
		// Apply the factory definition to top of the middleware stack.
		$this->middleware->prepend( $this->apply_definition() );

		// Append the arguments passed to make() as the last state values to apply.
		$factory = $this->state( $args );

		return Pipeline::make()
			->send( [] )
			->through( $factory->middleware->all() )
			->then(
				fn ( array $args ) => $this->get_model()::create( $args ),
			);
	}

	/**
	 * Load the factory's definition and make a new instance of the factory.
	 */
	public function apply_definition(): Closure {
		return fn ( array $args, Closure $next ) => $next(
			array_merge( $args, $this->definition() ),
		);
	}

	/**
	 * Create a new fluent factory instance.
	 */
	protected function create_fluent_factory(): Fluent_Factory {
		return new Fluent_Factory(
			clone $this,
			$this->faker,
		);
	}

	/**
	 * Magic method to proxy calls to the fluent factory.
	 *
	 * @param string $method The method name.
	 * @param array  $args   The arguments.
	 */
	public function __call( string $method, array $args ): mixed {
		if ( static::has_macro( $method ) ) {
			return $this->macro_call( $method, $args );
		}

		return $this->create_fluent_factory()->$method( ...$args );
	}
}
