<?php
/**
 * Updatable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Database;

/**
 * Updatable Model Interface
 */
interface Updatable {
	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 */
	public function save( array $attributes = [] );

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode.
	 */
	public function delete( bool $force = false );
}
