<?php
/**
 * Filesystem_Manager interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Filesystem;

/**
 * Filesystem Manager Contract
 */
interface Filesystem_Manager {
	/**
	 * Retrieve a filesystem disk.
	 *
	 * @param string $name Disk name.
	 */
	public function drive( string $name = null ): Filesystem;
}
