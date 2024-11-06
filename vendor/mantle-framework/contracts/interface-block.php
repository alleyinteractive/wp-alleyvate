<?php
/**
 * Block interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

/**
 * Block Contract
 */
interface Block {
	/**
	 * Executed by the Block Service Provider to handle registering the block
	 * with Mantle and WordPress.
	 */
	public function register(): void;
}
