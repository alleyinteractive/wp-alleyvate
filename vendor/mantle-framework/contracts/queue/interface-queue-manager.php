<?php
/**
 * Queue_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Queue;

/**
 * Queue Manager Contract
 */
interface Queue_Manager {
	/**
	 * Get a queue provider instance.
	 *
	 * @param string $name Provider name, optional.
	 */
	public function get_provider( string $name = null ): Provider;

	/**
	 * Add a provider for the queue manager.
	 *
	 * @param string $name Provider name.
	 * @param string $class_name Provider class name.
	 * @return static
	 */
	public function add_provider( string $name, string $class_name );
}
