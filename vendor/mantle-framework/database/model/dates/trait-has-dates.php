<?php
/**
 * Has_Dates trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Dates;

/**
 * This trait includes functionality for managing date fields on a model.
 *
 * @mixin \Mantle\Database\Model\Post
 *
 * @property \Mantle\Database\Model\Dates\Model_Date_Proxy $dates
 */
trait Has_Dates {
	/**
	 * Retrieve the model's dates.
	 */
	public function get_dates_attribute(): Model_Date_Proxy {
		return new Model_Date_Proxy( $this );
	}
}
