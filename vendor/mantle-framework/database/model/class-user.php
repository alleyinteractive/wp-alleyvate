<?php
/**
 * User class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts;
use Mantle\Support\Helpers;

/**
 * User Model
 *
 * @method static \Mantle\Database\Factory\User_Factory<static, \WP_User, static> factory( array|callable|null $state = null )
 */
class User extends Model implements Contracts\Database\Core_Object, Contracts\Database\Model_Meta, Contracts\Database\Updatable {
	use Meta\Model_Meta;
	use Meta\User_Meta;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array<string, string>
	 */
	protected static $aliases = [
		'email'    => 'user_email',
		'id'       => 'ID',
		'name'     => 'display_name',
		'password' => 'user_pass',
		'slug'     => 'user_login',
		'title'    => 'display_name',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'ID',
	];

	/**
	 * Object type for the model.
	 *
	 * @var string
	 */
	public static $object_name = 'user';

	/**
	 * Find a model by Object ID.
	 *
	 * @param \WP_User|int $object User to retrieve for.
	 * @return User|null
	 */
	public static function find( $object ) {
		$user = Helpers\get_user_object( $object );

		if ( empty( $user ) ) {
			return null;
		}

		return static::new_from_existing( (array) $user->data );
	}

	/**
	 * Query builder class to use.
	 */
	public static function get_query_builder_class(): ?string {
		return null;
	}

	/**
	 * Getter for Object ID.
	 */
	public function id(): int {
		return (int) $this->get( 'id' );
	}

	/**
	 * Getter for Object Name.
	 */
	public function name(): string {
		return (string) $this->get( 'name' );
	}

	/**
	 * Getter for Object Slug.
	 */
	public function slug(): string {
		return (string) $this->get( 'slug' );
	}

	/**
	 * Getter for Parent Object (if any)
	 */
	public function parent(): ?Contracts\Database\Core_Object {
		return null;
	}

	/**
	 * Getter for Object Description
	 */
	public function description(): string {
		return (string) $this->get( 'description' );
	}

	/**
	 * Getter for Object Status
	 */
	public function status(): ?string {
		return null;
	}

	/**
	 * Getter for the Object Permalink
	 */
	public function permalink(): ?string {
		return (string) \get_author_posts_url( $this->id() );
	}

	/**
	 * Retrieve the core object for the underlying object.
	 */
	public function core_object(): ?\WP_User {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_user_object( $id );
		}

		return null;
	}

	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 *
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ): bool {
		$this->set_attributes( $attributes );

		$id = $this->id();

		if ( empty( $id ) ) {
			$save = \wp_insert_user( $this->get_attributes() );
		} else {
			$save = \wp_update_user(
				array_merge(
					$this->get_modified_attributes(),
					[
						'ID' => $id,
					]
				)
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		// Set the ID attribute.
		$this->set_raw_attribute( 'ID', $save );
		$this->store_queued_meta();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the model, unused.
	 * @return bool Returns value of wp_delete_user().
	 */
	public function delete( bool $force = false ) {
		// Include user admin functions to get access to wp_delete_user().
		require_once ABSPATH . 'wp-admin/includes/user.php';

		return \wp_delete_user( $this->id() );
	}
}
