<?php
/**
 * Engine interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\View;

/**
 * Engine Interface
 */
interface Engine {
	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string $path View path.
	 * @param  array  $data View data.
	 */
	public function get( string $path, array $data = [] ): string;
}
