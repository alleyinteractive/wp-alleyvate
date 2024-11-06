<?php
/**
 * View_Finder interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\View;

/**
 * View Loader Contract
 */
interface View_Finder {
	/**
	 * Add a path to check against when loading a template.
	 *
	 * @param string $path Path to add.
	 * @return static
	 */
	public function add_path( string $path );

	/**
	 * Remove a path to check against when loading a template.
	 *
	 * @param string $path Path to remove.
	 * @return static
	 */
	public function remove_path( string $path );

	/**
	 * Remove all paths to check against.
	 *
	 * @return static
	 */
	public function clear_paths();

	/**
	 * Load a template by template name.
	 *
	 * Acts as a replacement to `get_template_part()` to allow sites to load templates
	 * outside of a theme.
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template name.
	 * @return string The template filename if one is located.
	 */
	public function load( string $slug, string $name = null ): string;
}
