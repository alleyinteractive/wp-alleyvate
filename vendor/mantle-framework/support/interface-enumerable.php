<?php
/**
 * Enumerable interface file.
 *
 * @package Mantle
 */

// phpcs:disable Squiz.Commenting.FunctionComment

// phpcs:disable Squiz.Commenting.ClassComment.Missing

namespace Mantle\Support;

use Countable;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Enumerable interface.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Mantle\Contracts\Support\Arrayable<TKey, TValue>
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable {
	/**
	 * Create a new collection instance if the value isn't one already.
	 *
	 * @template TMakeKey of array-key
	 * @template TMakeValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null  $items
	 * @return static<TMakeKey, TMakeValue>
	 */
	public static function make( $items = [] );

	/**
	 * Create a new instance by invoking the callback a given amount of times.
	 *
	 * @param  int           $number
	 * @param  callable|null $callback
	 * @return static
	 */
	public static function times( $number, callable $callback = null );

	/**
	 * Wrap the given value in a collection if applicable.
	 *
	 * @template TWrapValue
	 *
	 * @param  iterable<array-key, TWrapValue>|TWrapValue  $value
	 * @return static<array-key, TWrapValue>
	 */
	public static function wrap( $value );

	/**
	 * Get the underlying items from the given collection if applicable.
	 *
	 * @template TUnwrapKey of array-key
	 * @template TUnwrapValue
	 *
	 * @param  array<TUnwrapKey, TUnwrapValue>|static<TUnwrapKey, TUnwrapValue>  $value
	 * @return array<TUnwrapKey, TUnwrapValue>
	 */
	public static function unwrap( $value );

	/**
	 * Get all items in the enumerable.
	 *
	 * @return array
	 */
	public function all();

	/**
	 * Alias for the "avg" method.
	 *
	 * @param  (callable(TValue): float|int)|string|null  $callback
	 * @return float|int|null
	 */
	public function average( $callback = null );

	/**
	 * Get the median of a given key.
	 *
	 * @param  string|array<array-key, string>|null  $key
	 * @return float|int|null
	 */
	public function median( $key = null );

	/**
	 * Get the mode of a given key.
	 *
	 * @param  string|array<array-key, string>|null  $key
	 * @return array<int, float|int>|null
	 */
	public function mode( $key = null );

	/**
	 * Collapse the items into a single enumerable.
	 *
	 * @return static<int, mixed>
	 */
	public function collapse();

	/**
	 * Alias for the "contains" method.
	 *
	 * @param  (callable(TValue, TKey): bool)|TValue|string  $key
	 * @param  mixed $operator
	 * @param  mixed $value
	 * @return bool
	 */
	public function some( $key, $operator = null, $value = null );

	/**
	 * Determine if an item exists, using strict comparison.
	 *
	 * @param  (callable(TValue, TKey): bool)|TValue|string  $key
	 * @param  mixed $value
	 * @return bool
	 */
	public function contains_strict( $key, $value = null );

	/**
	 * Get the average value of a given key.
	 *
	 * @param  callable|string|null $callback
	 * @return mixed
	 */
	public function avg( $callback = null );

	/**
	 * Determine if an item exists in the enumerable.
	 *
	 * @param  (callable(TValue, TKey): bool)|TValue|string  $key
	 * @param  mixed $operator
	 * @param  mixed $value
	 * @return bool
	 */
	public function contains( $key, $operator = null, $value = null );

	/**
	 * Dump the collection and end the script.
	 *
	 * @param  mixed ...$args
	 */
	public function dd( ...$args ): never;

	/**
	 * Dump the collection.
	 *
	 * @return $this
	 */
	public function dump();

	/**
	 * Get the items that are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue>  $items
	 * @return static
	 */
	public function diff( $items );

	/**
	 * Get the items that are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue>  $items
	 * @param  callable(TValue, TValue): int  $callback
	 * @return static
	 */
	public function diff_using( $items, callable $callback );

	/**
	 * Get the items whose keys and values are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
	 * @return static
	 */
	public function diff_assoc( $items );

	/**
	 * Get the items whose keys and values are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
	 * @param  callable(TValue, TValue): int  $callback
	 * @return static
	 */
	public function diff_assoc_using( $items, callable $callback );

	/**
	 * Get the items whose keys are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
	 * @return static
	 */
	public function diff_keys( $items );

	/**
	 * Get the items whose keys are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
	 * @param  callable $callback
	 * @return static
	 */
	public function diff_keys_using( $items, callable $callback );

	/**
	 * Retrieve duplicate items.
	 *
	 * @param  (callable(TValue): bool)|string|null  $callback
	 * @param  bool          $strict
	 * @return static
	 */
	public function duplicates( $callback = null, $strict = false );

	/**
	 * Retrieve duplicate items using strict comparison.
	 *
	 * @param  (callable(TValue): bool)|string|null  $callback
	 * @return static
	 */
	public function duplicates_strict( $callback = null );

	/**
	 * Execute a callback over each item.
	 *
	 * @param  callable(TValue, TKey): mixed  $callback
	 * @return $this
	 */
	public function each( callable $callback );

	/**
	 * Execute a callback over each nested chunk of items.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function each_spread( callable $callback );

	/**
	 * Determine if all items pass the given truth test.
	 *
	 * @param  (callable(TValue, TKey): bool)|TValue|string  $key
	 * @param  mixed           $operator
	 * @param  mixed           $value
	 * @return bool
	 */
	public function every( $key, $operator = null, $value = null );

	/**
	 * Get all items except for those with the specified keys.
	 *
	 * @param  \Mantle\Support\Enumerable<array-key, TKey>|array<array-key, TKey>  $keys
	 * @return static
	 */
	public function except( $keys );

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  (callable(TValue): bool)|null  $callback
	 * @return static
	 */
	public function filter( callable $callback = null );

	/**
	 * Apply the callback if the value is truthy.
	 *
	 * @template TWhenParameter
	 * @template TWhenReturnType
	 *
	 * @param  (\Closure($this): TWhenParameter)|TWhenParameter  $value
	 * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $callback
	 * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $default
	 * @return static|TWhenReturnType
	 */
	public function when( $value, callable $callback = null, callable $default = null );

	/**
	 * Apply the callback if the collection is empty.
	 *
	 * @template TWhenEmptyReturnType
	 *
	 * @param  (callable($this): TWhenEmptyReturnType)  $callback
	 * @param  (callable($this): TWhenEmptyReturnType)|null  $default
	 * @return $this|TWhenEmptyReturnType
	 */
	public function when_empty( callable $callback, callable $default = null );

	/**
	 * Apply the callback if the collection is not empty.
	 *
	 * @template TWhenEmptyReturnType
	 *
	 * @param  (callable($this): TWhenEmptyReturnType)  $callback
	 * @param  (callable($this): TWhenEmptyReturnType)|null  $default
	 * @return $this|TWhenEmptyReturnType
	 */
	public function when_not_empty( callable $callback, callable $default = null );

	/**
	 * Apply the callback if the value is falsy.
	 *
	 * @template TUnlessParameter
	 * @template TUnlessReturnType
	 *
	 * @param  (\Closure( $this): TUnlessParameter)|TUnlessParameter  $value
	 * @param  (callable( $this, TUnlessParameter): TUnlessReturnType)|null  $callback
	 * @param  (callable( $this, TUnlessParameter): TUnlessReturnType)|null  $default
	 * @return $this|TUnlessReturnType
	 */
	public function unless( $value, callable $callback = null, callable $default = null );

	/**
	 * Apply the callback unless the collection is empty.
	 *
	 * @template TUnlessEmptyReturnType
	 *
	 * @param  callable($this): TUnlessEmptyReturnType  $callback
	 * @param  (callable($this): TUnlessEmptyReturnType)|null  $default
	 * @return $this|TUnlessEmptyReturnType
	 */
	public function unless_empty( callable $callback, callable $default = null );

	/**
	 * Apply the callback unless the collection is not empty.
	 *
	 * @template TUnlessNotEmptyReturnType
	 *
	 * @param  callable($this): TUnlessNotEmptyReturnType  $callback
	 * @param  (callable($this): TUnlessNotEmptyReturnType)|null  $default
	 * @return $this|TUnlessNotEmptyReturnType
	 */
	public function unless_not_empty( callable $callback, callable $default = null );

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string $key
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @return static
	 */
	public function where( $key, $operator = null, $value = null );

	/**
	 * Filter items by the given key value pair using strict comparison.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return static
	 */
	public function where_strict( $key, $value );

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string $key
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $values
	 * @param  bool   $strict
	 * @return static
	 */
	public function where_in( $key, $values, $strict = false );

	/**
	 * Filter items by the given key value pair using strict comparison.
	 *
	 * @param  string $key
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $values
	 * @return static
	 */
	public function where_in_strict( $key, $values );

	/**
	 * Filter items such that the value of the given key is between the given values.
	 *
	 * @param  string $key
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $values
	 * @return static
	 */
	public function where_between( $key, $values );

	/**
	 * Filter items such that the value of the given key is not between the given values.
	 *
	 * @param  string $key
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $values
	 * @return static
	 */
	public function where_not_between( $key, $values );

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string $key
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $values
	 * @param  bool   $strict
	 * @return static
	 */
	public function where_not_in( $key, $values, $strict = false );

	/**
	 * Filter items by the given key value pair using strict comparison.
	 *
	 * @param  string $key
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $values
	 * @return static
	 */
	public function where_not_in_strict( $key, $values );

	/**
	 * Filter the items, removing any items that don't match the given type.
	 *
	 * @template TWhereInstanceOf
	 *
	 * @param  class-string<TWhereInstanceOf>|array<array-key, class-string<TWhereInstanceOf>>  $type
	 * @return static<TKey, TWhereInstanceOf>
	 */
	public function where_instance_of( $type );

	/**
	 * Get the first item from the enumerable passing the given truth test.
	 *
	 * @template TFirstDefault
	 *
	 * @param  (callable(TValue,TKey): bool)|null  $callback
	 * @param  TFirstDefault|(\Closure(): TFirstDefault)  $default
	 * @return TValue|TFirstDefault
	 */
	public function first( callable $callback = null, $default = null );

	/**
	 * Get the first item by the given key value pair.
	 *
	 * @param  string $key
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @return TValue|null
	 */
	public function first_where( $key, $operator = null, $value = null );

	/**
	 * Flip the values with their keys.
	 *
	 * @return static<int|string, TKey>
	 */
	public function flip();

	/**
	 * Get an item from the collection by key.
	 *
	 * @template TGetDefault
	 *
	 * @param  TKey  $key
	 * @param  TGetDefault|(\Closure(): TGetDefault)  $default
	 * @return TValue|TGetDefault
	 */
	public function get( $key, $default = null );

	/**
	 * Group an associative array by a field or using a callback.
	 */
	public function group_by( $group_by, $preserve_keys = false );

	/**
	 * Key an associative array by a field or using a callback.
	 *
	 * @param  (callable(TValue, TKey): array-key)|array|string  $keyBy
	 * @return static<array-key, TValue>
	 */
	public function key_by( $key_by );

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  TKey|array<array-key, TKey>  $key
	 * @return bool
	 */
	public function has( $key );

	/**
	 * Concatenate values of a given key as a string.
	 *
	 * @param  string      $value
	 * @param  string|null $glue
	 * @return string
	 */
	public function implode( $value, $glue = null );

	/**
	 * Intersect the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable|iterable  $items
	 * @return static
	 */
	public function intersect( $items );

	/**
	 * Intersect the collection with the given items by key.
	 *
	 * @param   \Mantle\Contracts\Support\Arrayable|iterable  $items
	 * @return static
	 */
	public function intersect_by_keys( $items );

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function is_empty();

	/**
	 * Determine if the collection is not empty.
	 *
	 * @return bool
	 */
	public function is_not_empty();

	/**
	 * Join all items from the collection using a string. The final items can use a separate glue string.
	 *
	 * @param  string $glue
	 * @param  string $final_glue
	 * @return string
	 */
	public function join( $glue, $final_glue = '' );

	/**
	 * Get the keys of the collection items.
	 *
	 * @return static
	 */
	public function keys();

	/**
	 * Get the last item from the collection.
	 *
	 * @template TLastDefault
	 *
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @param  TLastDefault|(\Closure(): TLastDefault)  $default
	 * @return TValue|TLastDefault
	 */
	public function last( callable $callback = null, $default = null );

	/**
	 * Run a map over each of the items.
	 *
	 * @template TMapValue
	 *
	 * @param  callable(TValue, TKey): TMapValue  $callback
	 * @return static<TKey, TMapValue>
	 */
	public function map( callable $callback );

	/**
	 * Run a map over each nested chunk of items.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function map_spread( callable $callback );

	/**
	 * Run a dictionary map over the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapToDictionaryKey of array-key
	 * @template TMapToDictionaryValue
	 *
	 * @param  callable(TValue, TKey): array<TMapToDictionaryKey, TMapToDictionaryValue>  $callback
	 * @return static<TMapToDictionaryKey, array<int, TMapToDictionaryValue>>
	 */
	public function map_to_dictionary( callable $callback );

	/**
	 * Run a grouping map over the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 */
	public function map_to_groups( callable $callback );

	/**
	 * Run an associative map over each of the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapWithKeysKey of array-key
	 * @template TMapWithKeysValue
	 *
	 * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
	 * @return static<TMapWithKeysKey, TMapWithKeysValue>
	 */
	public function map_with_keys( callable $callback );

	/**
	 * Map a collection and flatten the result by a single level.
	 *
	 * @template TFlatMapKey of array-key
	 * @template TFlatMapValue
	 *
	 * @param  callable(TValue, TKey): (\Illuminate\Support\Collection<TFlatMapKey, TFlatMapValue>|array<TFlatMapKey, TFlatMapValue>)  $callback
	 * @return static<TFlatMapKey, TFlatMapValue>
	 */
	public function flat_map( callable $callback );

	/**
	 * Map the values into a new class.
	 *
	 * @template TMapIntoValue
	 *
	 * @param  class-string<TMapIntoValue>  $class
	 * @return static<TKey, TMapIntoValue>
	 */
	public function map_into( $class );

	/**
	 * Merge the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
	 * @return static
	 */
	public function merge( $items );

	/**
	 * Recursively merge the collection with the given items.
	 *
	 * @template TMergeRecursiveValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TMergeRecursiveValue>|iterable<TKey, TMergeRecursiveValue>  $items
	 * @return static<TKey, TValue|TMergeRecursiveValue>
	 */
	public function merge_recursive( $items );

	/**
	 * Create a collection by using this collection for keys and another for its values.
	 *
	 * @template TCombineValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TCombineValue>|iterable<array-key, TCombineValue>  $values
	 * @return static<TValue, TCombineValue>
	 */
	public function combine( $values );

	/**
	 * Union the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
	 * @return static
	 */
	public function union( $items );

	/**
	 * Get the min value of a given key.
	 *
	 * @param  (callable(TValue):mixed)|string|null  $callback
	 * @return mixed
	 */
	public function min( $callback = null );

	/**
	 * Get the max value of a given key.
	 *
	 * @param  (callable(TValue):mixed)|string|null  $callback
	 * @return mixed
	 */
	public function max( $callback = null );

	/**
	 * Create a new collection consisting of every n-th element.
	 *
	 * @param  int $step
	 * @param  int $offset
	 * @return static
	 */
	public function nth( $step, $offset = 0 );

	/**
	 * Get the items with the specified keys.
	 *
	 * @param  \Mantle\Support\Enumerable<array-key, TKey>|array<array-key, TKey>|string  $keys
	 * @return static
	 */
	public function only( $keys );

	/**
	 * "Paginate" the collection by slicing it into a smaller collection.
	 *
	 * @param  int $page
	 * @param  int $per_page
	 * @return static
	 */
	public function for_page( $page, $per_page );

	/**
	 * Partition the collection into two arrays using the given callback or key.\
	 */
	public function partition( $key, $operator = null, $value = null );

	/**
	 * Push all of the given items onto the collection.
	 *
	 * @param  iterable<array-key, TValue>  $source
	 * @return static
	 */
	public function concat( $source );

	/**
	 * Get one or a specified number of items randomly from the collection.
	 *
	 * @param  int|null $number
	 * @return static|mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function random( $number = null );

	/**
	 * Reduce the collection to a single value.
	 *
	 * @template TReduceInitial
	 * @template TReduceReturnType
	 *
	 * @param  callable(TReduceInitial|TReduceReturnType, TValue, TKey): TReduceReturnType  $callback
	 * @param  TReduceInitial  $initial
	 * @return TReduceReturnType
	 */
	public function reduce( callable $callback, $initial = null );

	/**
	 * Replace the collection items with the given items.
	 *
	 * @param  mixed $items
	 * @return static
	 */
	public function replace( $items );

	/**
	 * Recursively replace the collection items with the given items.
	 *
	 * @param  mixed $items
	 * @return static
	 */
	public function replace_recursive( $items );

	/**
	 * Reverse items order.
	 *
	 * @return static
	 */
	public function reverse();

	/**
	 * Search the collection for a given value and return the corresponding key if successful.
	 *
	 * @param  TValue|callable(TValue,TKey): bool  $value
	 * @param  bool  $strict
	 * @return TKey|bool
	 */
	public function search( $value, $strict = false );

	/**
	 * Shuffle the items in the collection.
	 *
	 * @param  int|null $seed
	 * @return static
	 */
	public function shuffle( $seed = null );

	/**
	 * Skip the first {$count} items.
	 *
	 * @param  int $count
	 * @return static
	 */
	public function skip( $count );

	/**
	 * Get a slice of items from the enumerable.
	 *
	 * @param  int      $offset
	 * @param  int|null $length
	 * @return static
	 */
	public function slice( $offset, $length = null );

	/**
	 * Split a collection into a certain number of groups.
	 *
	 * @param  int $number_of_groups
	 * @return static<int, static>
	 */
	public function split( $number_of_groups );

	/**
	 * Chunk the collection into chunks of the given size.
	 *
	 * @param  int $size
	 * @return static<int, static>
	 */
	public function chunk( $size );

	/**
	 * Sort through each item with a callback.
	 *
	 * @param  (callable(TValue, TValue): int)|null|int  $callback
	 * @return static
	 */
	public function sort( $callback = null );

	/**
	 * Sort items in descending order.
	 *
	 * @param  int $options
	 * @return static
	 */
	public function sort_desc( $options = SORT_REGULAR );

	/**
	 * Sort the collection using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string  $callback
	 * @param  int             $options
	 * @param  bool            $descending
	 * @return static
	 */
	public function sort_by( $callback, $options = SORT_REGULAR, $descending = false );

	/**
	 * Sort the collection in descending order using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string  $callback
	 * @param  int             $options
	 * @return static
	 */
	public function sort_by_desc( $callback, $options = SORT_REGULAR );

	/**
	 * Sort the collection keys.
	 *
	 * @param  int  $options
	 * @param  bool $descending
	 * @return static
	 */
	public function sort_keys( $options = SORT_REGULAR, $descending = false );

	/**
	 * Sort the collection keys in descending order.
	 *
	 * @param  int $options
	 * @return static
	 */
	public function sort_keys_desc( $options = SORT_REGULAR );

	/**
	 * Get the sum of the given values.
	 *
	 * @param  (callable(TValue): mixed)|string|null  $callback
	 * @return mixed
	 */
	public function sum( $callback = null );

	/**
	 * Take the first or last {$limit} items.
	 *
	 * @param  int $limit
	 * @return static
	 */
	public function take( $limit );

	/**
	 * Pass the collection to the given callback and then return it.
	 *
	 * @param  callable(TValue): mixed  $callback
	 * @return $this
	 */
	public function tap( callable $callback );

	/**
	 * Pass the enumerable to the given callback and return the result.
	 *
	 * @template TPipeReturnType
	 *
	 * @param  callable($this): TPipeReturnType  $callback
	 * @return TPipeReturnType
	 */
	public function pipe( callable $callback );

	/**
	 * Get the values of a given key.
	 *
	 * @param  string|array $value
	 * @param  string|null  $key
	 * @return static
	 */
	public function pluck( $value, $key = null );

	/**
	 * Create a collection of all elements that do not pass a given truth test.
	 *
	 * @param  (callable(TValue, TKey): bool)|bool|TValue  $callback
	 * @return static
	 */
	public function reject( $callback = true );

	/**
	 * Return only unique items from the collection array.
	 *
	 * @param  (callable(TValue, TKey): mixed)|string|null  $key
	 * @param  bool                 $strict
	 * @return static
	 */
	public function unique( $key = null, $strict = false );

	/**
	 * Return only unique items from the collection array using strict comparison.
	 *
	 * @param  (callable(TValue, TKey): mixed)|string|null  $key
	 * @return static
	 */
	public function unique_strict( $key = null );

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return static
	 */
	public function values();

	/**
	 * Pad collection to the specified length with a value.
	 *
	 * @template TPadValue
	 *
	 * @param  int  $size
	 * @param  TPadValue  $value
	 * @return static<int, TValue|TPadValue>
	 */
	public function pad( $size, $value );

	/**
	 * Count the number of items in the collection using a given truth test.
	 *
	 * @param  (callable(TValue, TKey): array-key)|string|null  $countBy
	 * @return static<array-key, int>
	 */
	public function count_by( $callback = null );

	/**
	 * Collect the values into a collection.
	 *
	 * @return \Mantle\Support\Collection<TKey, TValue>
	 */
	public function collect();

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	public function __toString();

	/**
	 * Add a method to the list of proxied methods.
	 *
	 * @param  string $method
	 * @return void
	 */
	public static function proxy( $method );

	/**
	 * Dynamically access collection proxies.
	 *
	 * @param  string $key
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function __get( $key );
}
