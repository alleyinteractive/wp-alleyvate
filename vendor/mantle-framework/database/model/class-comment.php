<?php
/**
 * Comment class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts;
use Mantle\Support\Helpers;

/**
 * Comment Model
 *
 * @method static \Mantle\Database\Factory\Post_Factory<static, \WP_Comment, static> factory( array|callable|null $state = null )
 */
class Comment extends Model implements Contracts\Database\Core_Object, Contracts\Database\Model_Meta, Contracts\Database\Updatable {
	use Meta\Model_Meta;
	use Meta\Comment_Meta;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array<string, string>
	 */
	protected static $aliases = [
		'description' => 'comment_content',
		'id'          => 'comment_ID',
		'name'        => 'comment_author',
		'title'       => 'comment_author',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'comment_ID',
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
	 * @param \WP_Comment|string|int $object Comment to retrieve.
	 * @return Comment|null
	 */
	public static function find( $object ) {
		$post = Helpers\get_comment_object( $object );
		return $post ? new static( $post ) : null;
	}

	/**
	 * Get the meta type for the object.
	 */
	public function get_meta_type(): string {
		return 'comment';
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
		return (string) $this->get( 'name' );
	}

	/**
	 * Getter for Parent Object (if any)
	 */
	public function parent(): ?Contracts\Database\Core_Object {
		$parent = $this->get_attribute( 'comment_parent' );

		if ( ! empty( $parent ) ) {
			return static::find( (int) $parent );
		}

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
		return (string) \get_comment_link( $this->id() );
	}

	/**
	 * Retrieve the core object for the underlying object.
	 */
	public function core_object(): ?\WP_Comment {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_comment_object( $id );
		}

		return null;
	}

	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ): bool {
		$this->set_attributes( $attributes );

		$id = $this->id();

		if ( empty( $id ) ) {
			$save = \wp_insert_comment( $this->get_attributes() );
		} else {
			$save = \wp_update_comment(
				array_merge(
					$this->get_modified_attributes(),
					[
						'comment_ID' => $id,
					]
				)
			);
		}

		if ( ! $save ) {
			throw new Model_Exception( 'Error saving model' );
		}

		$this->set_raw_attribute( 'comment_ID', $save );
		$this->store_queued_meta();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode.
	 */
	public function delete( bool $force = false ): void {
		\wp_delete_comment( $this->id(), $force );
	}
}
