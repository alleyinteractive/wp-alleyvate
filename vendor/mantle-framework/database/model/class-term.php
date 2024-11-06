<?php
/**
 * Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Query\Term_Query_Builder;
use Mantle\Support\Helpers;

/**
 * Term Model
 *
 * @property int    $id
 * @property int    $term_id
 * @property string $name
 * @property string $slug
 * @property string $taxonomy
 *
 * @method static \Mantle\Database\Factory\Term_Factory<static, \WP_Term, static> factory( array|callable|null $state = null )
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereId( int $id )
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereName(string $name)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereSlug(string $slug)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereTaxonomy(string $taxonomy)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereMeta(string $key, string $value)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereNotIn(string $key, array $values)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereIn(string $key, array $values)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> where(string|array $attribute, mixed $value)
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> whereRaw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> where_raw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> orWhereRaw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Term_Query_Builder<static> or_where_raw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 */
class Term extends Model implements Core_Object, Model_Meta, Updatable {
	use Events\Term_Events;
	use Meta\Model_Meta;
	use Meta\Term_Meta;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array<string, string>
	 */
	protected static $aliases = [
		'id' => 'term_id',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'term_id',
	];

	/**
	 * Object type for the model when registering the taxonomy.
	 *
	 * Used to associate a taxonomy with another object (post type).
	 *
	 * @var string[]
	 */
	protected static $object_types = [];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		// Set the taxonomy on the model by default.
		$this->attributes['taxonomy'] = $this->get_object_name();

		parent::__construct( $object );
	}

	/**
	 * Taxonomy of the term.
	 */
	public function taxonomy(): string {
		return $this->get( 'taxonomy' );
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param \WP_Term|string|int $object Term to retrieve.
	 * @return Term|null
	 */
	public static function find( $object ) {
		$term = Helpers\get_term_object( $object );

		if ( empty( $term ) ) {
			return null;
		}

		// Bail if the taxonomy doesn't match the expected object type.
		if ( static::get_object_name() !== $term->taxonomy ) {
			return null;
		}

		return static::new_from_existing( (array) $term );
	}

	/**
	 * Create a new model instance for a given taxonomy.
	 *
	 * @param string $taxonomy Taxonomy to create the model for.
	 */
	public static function for( string $taxonomy ): self {
		$instance = new class() extends Term {
			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Boot the model if it has not been booted.
			 *
			 * Prevent booting the model unless the object name is set.
			 */
			public static function boot_if_not_booted(): void {
				if ( empty( self::$object_name ) ) {
					return;
				}

				parent::boot_if_not_booted();
			}
		};

		$instance::$object_name = $taxonomy;
		$instance::boot_if_not_booted();

		return $instance;
	}

	/**
	 * Query builder class to use.
	 */
	public static function get_query_builder_class(): ?string {
		return Term_Query_Builder::class;
	}

	/**
	 * Create a new query instance.
	 *
	 * @return \Mantle\Database\Query\Term_Query_Builder<static>
	 */
	public static function query(): Term_Query_Builder {
		return ( new static() )->new_query();
	}

	/**
	 * Get the meta type for the object.
	 */
	public function get_meta_type(): string {
		return 'term';
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
	public function parent(): ?Core_Object {
		$parent = $this->get( 'parent' );

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
		$term_link = \get_term_link( $this->id() );
		return ! \is_wp_error( $term_link ) ? (string) $term_link : null;
	}

	/**
	 * Retrieve the core object for the underlying object.
	 */
	public function core_object(): ?\WP_Term {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_term_object( $id );
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
			$save = \wp_insert_term(
				$this->name(),
				$this->taxonomy(),
				$this->get_attributes()
			);
		} else {
			$save = \wp_update_term(
				$this->id(),
				$this->taxonomy(),
				$this->get_modified_attributes()
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		$this->set_raw_attribute( 'term_id', $save['term_id'] );
		$this->store_queued_meta();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode, not used.
	 */
	public function delete( bool $force = false ): void {
		\wp_delete_term( $this->id(), $this->taxonomy() );
	}

	/**
	 * Get the registerable route for the model. By default this is set relative
	 * the object's archive route with the object's slug.
	 *
	 *     /object_name/object_slug/
	 */
	public static function get_route(): ?string {
		$route_structure = static::get_archive_route() . '/{' . static::get_object_name() . '}/';

		/**
		 * Filter the route structure for a term handled through the entity router.
		 *
		 * @param string $route_structure Route structure.
		 * @param string $object_name Taxonomy name.
		 * @param string $object_class Model class name.
		 */
		return (string) apply_filters(
			'mantle_entity_router_term_route',
			$route_structure,
			static::get_object_name(),
			static::class
		);
	}
}
