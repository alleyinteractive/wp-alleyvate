<?php
/**
 * Jsonable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Support;

interface Jsonable {
	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param int $options json_encode() options.
	 * @return string
	 */
	public function to_json( $options = 0 );
}
