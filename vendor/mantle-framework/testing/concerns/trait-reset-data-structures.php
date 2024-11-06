<?php
/**
 * Reset_Data_Structures trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

/**
 * Reset the data structures on set up.
 *
 * When the trait is used it will reset the post types and taxonomies back to
 * their original state for each test run.
 */
trait Reset_Data_Structures {
	use WordPress_State;

	/**
	 * Reset the data structures on set up.
	 */
	public function reset_data_structures_set_up(): void {
		$this->reset_post_types();
		$this->reset_taxonomies();
	}
}
