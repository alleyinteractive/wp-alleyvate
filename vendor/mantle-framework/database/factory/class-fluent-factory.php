<?php
/**
 * Fluent_Factory class file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use BadMethodCallException;
use Faker\Generator;

use function Mantle\Support\Helpers\collect;

/**
 * Fluent Database Factory
 *
 * Extends upon the factory that is included with Mantle (one that is designed
 * to mirror WordPress) and builds upon it to provide a fluent interface.
 *
 * @template TModel of \Mantle\Database\Model\Model
 * @template TObject
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Fluent_Factory extends Factory {
	/**
	 * Number of objects to create.
	 */
	protected int $count = 1;

	/**
	 * Constructor.
	 *
	 * @param Factory   $factory The factory to make fluent.
	 * @param Generator $faker The Faker instance.
	 */
	protected function __construct(
		protected Factory $factory,
		Generator $faker,
	) {
		parent::__construct( $faker );
	}

	/**
	 * Set the number of objects to create in the factory.
	 *
	 * @param int $count Count of objects to create.
	 */
	public function count( int $count ): static {
		$this->count = $count;

		return $this;
	}

	/**
	 * Create one or multiple objects and return the IDs.
	 *
	 * @param array $args Arguments to pass to the factory.
	 * @return \Mantle\Support\Collection<int, TReturnValue>|mixed
	 */
	public function create( array $args = [] ): mixed {
		if ( 1 === $this->count ) {
			return $this->factory->create( $args );
		} else {
			return collect( $this->factory->create_many( $this->count, $args ) );
		}
	}

	/**
	 * Create one or multiple objects and return the objects.
	 *
	 * @param array $args Arguments to pass to the factory.
	 * @return \Mantle\Support\Collection<int, TReturnValue>|TReturnValue
	 */
	public function create_and_get( array $args = [] ): mixed {
		if ( 1 === $this->count ) {
			return $this->factory->create_and_get( $args );
		}

		return collect()
			->times(
				$this->count,
				fn () => $this->factory->create_and_get( $args ),
			);
	}

	/**
	 * Method to proxy back to the definition method on the factory.
	 */
	public function definition(): array {
		return $this->factory->definition();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param mixed $object_id The object ID.
	 * @return TReturnValue
	 */
	public function get_object_by_id( mixed $object_id ): mixed {
		return $this->factory->get_object_by_id( $object_id );
	}

	/**
	 * Magic method to prevent an infinite loop when calling methods that do not
	 * exist on this class. Allows for the factory to proxy back to the base
	 * factory for scopes and other methods.
	 *
	 * @param string $method The method name.
	 * @param array  $args   The arguments.
	 *
	 * @throws BadMethodCallException If the method does not exist.
	 */
	public function __call( string $method, array $args ): mixed {
		if ( method_exists( $this->factory, $method ) ) {
			$value = $this->factory->$method( ...$args );

			if ( $value instanceof Factory ) {
				return $this->make_fluent( $value );
			}

			return $value;
		}

		throw new BadMethodCallException( "Method {$method} does not exist." );
	}

	/**
	 * Convert a factory to a fluent factory and copy over the state from the
	 * current fluent factory.
	 *
	 * @param Factory $factory The factory to convert.
	 */
	protected function make_fluent( Factory $factory ): self {
		return $factory
			->create_fluent_factory()
			->count( $this->count );
	}
}
