<?php
/**
 * Action class file
 *
 * @package Mantle
 */

namespace Mantle\Support\Attributes;

use Attribute;

/**
 * Hook Action Attribute
 *
 * Used to hook a method to an WordPress hook at a specific priority.
 */
#[Attribute]
class Action {
	/**
	 * Constructor.
	 *
	 * @param string $hook_name Hook name.
	 * @param int    $priority Priority, defaults to 10.
	 */
	public function __construct( public string $hook_name, public int $priority = 10 ) {}
}
