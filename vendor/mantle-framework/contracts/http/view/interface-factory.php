<?php
/**
 * Factory interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\View;

use Mantle\Http\View\View;
use Mantle\Support\Collection;

/**
 * View Factory Contract
 */
interface Factory {
	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array|string $key Key to share.
	 * @param mixed|null   $value Value to share.
	 * @return mixed
	 */
	public function share( $key, $value = null );

	/**
	 * Get an item from the shared data.
	 *
	 * @param string $key Key to get item by.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function shared( $key, $default = null );

	/**
	 * Get all of the shared data for the environment.
	 */
	public function get_shared(): array;

	/**
	 * Create a collection of views that loop over a collection of WordPress objects.
	 *
	 * While iterating over the data, the proper post data is setup for each item.
	 *
	 * @param array|\ArrayAccess $data Array of WordPress data to loop over.
	 * @param string             $slug View slug.
	 * @param array|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array              $variables Variables for the view, optional.
	 */
	public function loop( $data, string $slug, $name = null, array $variables = [] ): Collection;

	/**
	 * Iterate over an array, loading a given template part for each item in the
	 * array.
	 *
	 * @param array|\ArrayAccess $data Array of data to iterate over over.
	 * @param string             $slug View slug.
	 * @param array|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array              $variables Variables for the view, optional.
	 */
	public function iterate( $data, string $slug, $name = null, array $variables = [] ): Collection;

	/**
	 * Get the rendered contents of a view.
	 *
	 * @param string       $slug View slug.
	 * @param array|string $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @param array        $variables Variables for the view, optional.
	 */
	public function make( string $slug, $name = null, array $variables = [] ): View;

	/**
	 * Get a variable from the current view.
	 *
	 * @param string $key Variable to get.
	 * @param mixed  $default Default value if unset.
	 * @return mixed
	 */
	public function get_var( string $key, $default = null );

	/**
	 * Push a view onto the stack and set it as the current view.
	 *
	 * @param View $view View being loaded.
	 * @return static
	 */
	public function push( View $view );

	/**
	 * Pop a partial off the top of the stack and set the current partial to the
	 * next one down.
	 *
	 * @return static
	 */
	public function pop();
}
