<?php
/**
 * Comment_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

/**
 * Comment Model Meta
 */
trait Comment_Meta {
	/**
	 * Get the meta type for the object.
	 */
	public function get_meta_type(): string {
		return 'comment';
	}
}
