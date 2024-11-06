<?php
/**
 * Collection class file
 *
 * phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.ContentAfterBrace
 *
 * @package Mantle
 */

namespace Mantle\Database\Query;

use Mantle\Database\Model\Model;
use Mantle\Support\Collection as Base_Collection;

/**
 * Database Query Collection
 *
 * @template TKey of array-key
 * @template TModel of \Mantle\Database\Model\Model
 *
 * @extends \Mantle\Support\Collection<TKey, TModel>
 */
class Collection extends Base_Collection {
	/**
	 * Total number of rows found for the query.
	 */
	public ?int $found_rows = null;

	/**
	 * Set the total number of rows found for the query.
	 *
	 * @param int|null $found_rows Total number of rows found for the query.
	 */
	public function with_found_rows( ?int $found_rows ): static {
		$this->found_rows = $found_rows;

		return $this;
	}

	/**
	 * Get the total number of rows found for the query.
	 */
	public function found_rows(): ?int {
		return $this->found_rows;
	}

	/**
	 * Retrieve the models in the collection.
	 *
	 * @return \Mantle\Support\Collection<int, class-string<TModel>>
	 */
	public function models(): Base_Collection {
		return $this->map( fn ( $model ) => $model::class )->values()->unique();
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @template TMapValue
	 *
	 * @param  callable(TModel, TKey): TMapValue $callback The callback to run.
	 * @return \Mantle\Support\Collection<TKey, TMapValue>|static<TKey, TMapValue>
	 */
	public function map( callable $callback ) {
		$result = parent::map( $callback );

		if ( $result instanceof self ) {
			$result->with_found_rows( $this->found_rows );
		}

		return $result->contains( fn ( $item ) => ! $item instanceof Model )
			? $result->to_base()
			: $result;
	}

	/**
	 * Run an associative map over each of the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapWithKeysKey of array-key
	 * @template TMapWithKeysValue
	 *
	 * @param  callable(TModel, TKey): array<TMapWithKeysKey, TMapWithKeysValue> $callback The callback to run.
	 * @return \Mantle\Support\Collection<TMapWithKeysKey, TMapWithKeysValue>|static<TMapWithKeysKey, TMapWithKeysValue>
	 */
	public function map_with_keys( callable $callback ) {
		$result = parent::map_with_keys( $callback );

		if ( $result instanceof self ) {
			$result->with_found_rows( $this->found_rows );
		}

		return $result->contains( fn ( $item ) => ! $item instanceof Model )
			? $result->to_base()
			: $result;
	}

	/**
	 * The following methods are intercepted to always return base collections.
	 */

	/**
	 * Count the number of items in the collection by a field or using a callback.
	 *
	 * @param  (callable(TModel, TKey): array-key)|string|null $count_by
	 * @return \Mantle\Support\Collection<array-key, int>
	 */
	public function count_by( $count_by = null ) {
		return $this->to_base()->count_by( $count_by );
	}

	/**
	 * Collapse the collection of items into a single array.
	 *
	 * @return \Mantle\Support\Collection<int, mixed>
	 */
	public function collapse() {
		return $this->to_base()->collapse();
	}

	/**
	 * Get a flattened array of the items in the collection.
	 *
	 * @param  int|float $depth
	 * @return \Mantle\Support\Collection<int, mixed>
	 */
	public function flatten( $depth = INF ) {
		return $this->to_base()->flatten( $depth );
	}

	/**
	 * Flip the items in the collection.
	 *
	 * @return \Mantle\Support\Collection<int|string, TKey>
	 */
	public function flip() {
		return $this->to_base()->flip();
	}

	/**
	 * Get the keys of the collection items.
	 *
	 * @return \Mantle\Support\Collection<int, TKey>
	 */
	public function keys() {
		return $this->to_base()->keys();
	}

	/**
	 * Pad collection to the specified length with a value.
	 *
	 * @template TPadValue
	 *
	 * @param  int       $size
	 * @param  TPadValue $value
	 * @return \Mantle\Support\Collection<int, TModel|TPadValue>
	 */
	public function pad( $size, $value ) {
		return $this->to_base()->pad( $size, $value );
	}

	/**
	 * Get an array with the values of a given key.
	 *
	 * @param  string|array<array-key, string> $value
	 * @param  string|null                     $key
	 * @return \Mantle\Support\Collection<array-key, mixed>
	 */
	public function pluck( $value, $key = null ) {
		return $this->to_base()->pluck( $value, $key );
	}

	/**
	 * Zip the collection together with one or more arrays.
	 *
	 * @template TZipValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TZipValue>|iterable<array-key, TZipValue> ...$items
	 * @return static<int, static<TKey, TModel|TZipValue>>
	 */
	public function zip( ...$items ) { // @phpstan-ignore-line return
		return $this->to_base()->zip( ...$items );
	}
}
