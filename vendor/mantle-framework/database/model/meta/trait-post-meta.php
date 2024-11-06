<?php
/**
 * Post_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

/**
 * Post Model Meta
 */
trait Post_Meta {
	/**
	 * Get the meta type for the object.
	 */
	public function get_meta_type(): string {
		return 'post';
	}
}
