<?php
/**
 * Site class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts;
use Mantle\Support\Helpers;

/**
 * Site Model
 *
 * @method static \Mantle\Database\Factory\Post_Factory<static, \WP_Site, static> factory( array|callable|null $state = null )
 */
class Site extends Model implements Contracts\Database\Core_Object, Contracts\Database\Updatable {
	/**
	 * Attributes for the model from the object
	 *
	 * @var array<string, string>
	 */
	protected static $aliases = [
		'id'   => 'blog_id',
		'name' => 'title',
		'slug' => 'path',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'site_ID',
	];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		$this->attributes = (array) $object;
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param \WP_Site|string|int $object Site to retrieve.
	 * @return Site|null
	 */
	public static function find( $object ) {
		$site = Helpers\get_site_object( $object );
		return $site ? new static( $site ) : null;
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
		return (string) \get_blog_option( $this->id(), 'blogname' );
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
	 * Getter for the Object Permalink
	 */
	public function permalink(): ?string {
		return (string) \get_home_url( $this->id() );
	}

	/**
	 * Retrieve the core object for the underlying object.
	 */
	public function core_object(): ?\WP_Site {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_site_object( $id );
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
			$save = \wp_insert_site( $this->get_attributes() );
		} else {
			$save = \wp_update_site(
				$this->id(),
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
		$this->set_raw_attribute( 'blog_id', $save );

		$this->refresh();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode.
	 * @return \WP_Site|\WP_Error The deleted site object on success, or error object on failure.
	 */
	public function delete( bool $force = false ) {
		return \wp_delete_site( $this->id() );
	}
}
