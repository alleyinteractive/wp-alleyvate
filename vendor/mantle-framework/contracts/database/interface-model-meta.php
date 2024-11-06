<?php
/**
 * Model_Meta interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Database;

use Mantle\Database\Model\Model_Exception;

/**
 * Model Meta Interface
 */
interface Model_Meta {
	/**
	 * Retrieve meta data for the object.
	 *
	 * @param string $meta_key Meta key to retrieve.
	 * @param bool   $single Return the first meta key, defaults to true.
	 */
	public function get_meta( string $meta_key, bool $single = true ): mixed;

	/**
	 * Add meta value for the object.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value to store.
	 */
	public function add_meta( string $meta_key, mixed $meta_value ): void;

	/**
	 * Update meta value for the object.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value to store.
	 * @param string $prev_value Optional, previous meta value.
	 */
	public function set_meta( string $meta_key, mixed $meta_value, mixed $prev_value = '' ): void;

	/**
	 * Delete a object's meta.
	 *
	 * @param string $meta_key Meta key to delete by.
	 * @param mixed  $meta_value Previous meta value to delete.
	 */
	public function delete_meta( string $meta_key, mixed $meta_value = '' );

	/**
	 * Allow setting meta through an array via an attribute mutator.
	 *
	 * @param array $meta_values Meta values to set.
	 * @throws Model_Exception Thrown on invalid value being set.
	 */
	public function set_meta_attribute( array $meta_values ): void;

	/**
	 * Get a queued meta attribute.
	 *
	 * @param string $key Meta key.
	 * @return mixed|null Meta value or null.
	 */
	public function get_queued_meta_attribute( string $key ): mixed;

	/**
	 * Queue a meta attribute for saving.
	 * Allows meta to be set before a model is saved.
	 *
	 * Should not be called directly, only to be used via `$model->meta->...`.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param bool   $update Flag to update the queued meta.
	 */
	public function queue_meta_attribute( string $key, $value, bool $update = true ): void;

	/**
	 * Store queued model meta.
	 */
	public function store_queued_meta(): void;
}
