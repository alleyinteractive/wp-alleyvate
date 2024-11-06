<?php
/**
 * Term_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

/**
 * Term Model Meta
 */
trait Term_Meta {
	/**
	 * Get the meta type for the object.
	 */
	public function get_meta_type(): string {
		return 'term';
	}
}
