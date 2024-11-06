<?php
/**
 * Htmlable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Support;

interface Htmlable {

	/**
	 * Get content as a string of HTML.
	 *
	 * @return string
	 */
	public function to_html();
}
