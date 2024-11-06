<?php
/**
 * Paginator interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Paginator;

use Mantle\Support\Collection;

/**
 * Paginator Contract
 */
interface Paginator {
	/**
	 * Set the path for the paginator.
	 *
	 * @param string $path Path to set.
	 * @return static
	 */
	public function path( string $path = null );

	/**
	 * Retrieve the paginator's path.
	 */
	public function get_path(): string;

	/**
	 * Flag to use query string for pagination.
	 *
	 * @return static
	 */
	public function use_query_string();

	/**
	 * Flag to use path for pagination.
	 *
	 * @return static
	 */
	public function use_path();

	/**
	 * Set the current page.
	 *
	 * @param int $current_page Page to set.
	 * @return static
	 */
	public function set_current_page( int $current_page = null );

	/**
	 * Retrieve the current page.
	 */
	public function current_page(): int;

	/**
	 * Retrieve the items in the paginator.
	 */
	public function items(): Collection;

	/**
	 * Retrieve the count of the paginator.
	 */
	public function count(): int;

	/**
	 * Append a query variable or set multiple query variables.
	 *
	 * @param string $key Query variable or an array of key value query variables.
	 * @param mixed  $value Variable value.
	 * @return static
	 */
	public function append( $key, $value = null );

	/**
	 * Set the paginator to use the current query variables from the request.
	 *
	 * @return static
	 */
	public function with_query_string();

	/**
	 * Retrieve the query variables for the paginator.
	 */
	public function query(): array;

	/**
	 * Retrieve the next URL.
	 */
	public function next_url(): ?string;

	/**
	 * Retrieve the previous URL.
	 */
	public function previous_url(): ?string;
}
