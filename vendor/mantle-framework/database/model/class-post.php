<?php
/**
 * Post class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use Mantle\Contracts;
use Mantle\Database\Query\Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Support\Helpers;

/**
 * Post Model
 *
 * @extends Model<\WP_Post>
 *
 * @property int $comment_count
 * @property int $ID
 * @property int $menu_order
 * @property int $post_author
 * @property int $post_parent
 * @property string $comment_status
 * @property string $filter
 * @property string $guid
 * @property string $ping_status
 * @property string $pinged
 * @property string $post_content
 * @property string $post_date
 * @property string $post_date_gmt
 * @property string $post_excerpt
 * @property string $post_mime_type
 * @property string $post_modified
 * @property string $post_modified_gmt
 * @property string $post_name
 * @property string $post_password
 * @property string $post_status
 * @property string $post_title
 * @property string $post_type
 * @property string $to_ping
 * @property string $content Alias to post_content.
 * @property string $date Alias to post_date.
 * @property string $date_gmt Alias to post_date_gmt.
 * @property string $modified Alias to post_modified.
 * @property string $modified_gmt Alias to post_modified_gmt.
 * @property string $description Alias to post_excerpt.
 * @property string $id Alias to ID.
 * @property string $name Alias to post_title.
 * @property string $slug Alias to post_name.
 * @property string $status Alias to post_status.
 * @property string $title Alias to post_title.
 *
 * @method static \Mantle\Database\Factory\Post_Factory<static, \WP_Post, static> factory( array|callable|null $state = null )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> anyStatus()
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> where( string|array $attribute, mixed $value )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereId( int $id )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereNotIn(string $key, array $values)
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereIn(string $key, array $values)
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereName( string $name )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereSlug( string $slug )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereStatus( string $status )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereTitle( string $title )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereType( string $type )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereTerm( array|\WP_Term|\Mantle\Database\Model\Term|int $term, ?string $taxonomy = null, string $operator = 'IN', string $field = 'term_id' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> andWhereTerm( array|\WP_Term|\Mantle\Database\Model\Term|int $term, ?string $taxonomy = null, string $operator = 'IN', string $field = 'term_id' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> orWhereTerm( array|\WP_Term|\Mantle\Database\Model\Term|int $term, ?string $taxonomy = null, string $operator = 'IN', string $field = 'term_id' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereMeta( string|\BackedEnum $key, mixed $value, string $operator = '=' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereRaw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> where_raw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> orWhereRaw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> or_where_raw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereDate( DateTimeInterface|int|string $date, string $compare = '=', string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereUtcDate( DateTimeInterface|int|string $date, string $compare = '=' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereModifiedDate( DateTimeInterface|int|string $date, string $compare = '=' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> whereModifiedUtcDate( DateTimeInterface|int|string $date, string $compare = '=' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> olderThan( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> olderThanOrEqualTo( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> older_than( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> older_than_or_equal_to( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> newerThan( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> newerThanOrEqualTo( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> newer_than( DateTimeInterface|int $date, string $column = 'post_date' )
 * @method static \Mantle\Database\Query\Post_Query_Builder<static> newer_than_or_equal_to( DateTimeInterface|int $date, string $column = 'post_date' )
 */
class Post extends Model implements Contracts\Database\Core_Object, Contracts\Database\Model_Meta, Contracts\Database\Updatable {
	use Dates\Has_Dates;
	use Events\Post_Events;
	use Meta\Model_Meta;
	use Meta\Post_Meta;
	use Term\Model_Term;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array<string, string>
	 */
	protected static $aliases = [
		'content'      => 'post_content',
		'date'         => 'post_date',
		'date_gmt'     => 'post_date_gmt',
		'modified'     => 'post_modified',
		'modified_gmt' => 'post_modified_gmt',
		'description'  => 'post_excerpt',
		'id'           => 'ID',
		'title'        => 'post_title',
		'name'         => 'post_title',
		'slug'         => 'post_name',
		'status'       => 'post_status',
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
	 * The attributes that should be hidden for serialization.
	 *
	 * @var string[]
	 */
	protected $hidden = [
		'post_password',
	];

	/**
	 * Post type for the model.
	 *
	 * @var string
	 */
	public static $object_name;

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		// Set the post type on the model by default.
		$this->attributes['post_type'] = $this->get_object_name();

		parent::__construct( $object );
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @todo Add global scopes for the model to allow for the query builder
	 *       to verify the object type as well.
	 *
	 * @param \WP_Post|int $object Post to retrieve for.
	 * @return static|null
	 */
	public static function find( $object ) {
		$post = Helpers\get_post_object( $object );

		if ( empty( $post ) ) {
			return null;
		}

		// Verify the object type matches the model type.
		if ( static::get_object_name() !== $post->post_type ) {
			return null;
		}

		return static::new_from_existing( (array) $post );
	}

	/**
	 * Create a new model instance for a given post type.
	 *
	 * @param string $post_type Post type to create the model for.
	 */
	public static function for( string $post_type ): self {
		$instance = new class() extends Post {
			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Post type for the model.
			 */
			public static string $for_object_name = '';

			/**
			 * Retrieve the object name.
			 */
			public static function get_object_name(): ?string {
				return self::$for_object_name;
			}

			/**
			 * Boot the model if it has not been booted.
			 *
			 * Prevent booting the model unless the object name is set.
			 */
			public static function boot_if_not_booted(): void {
				if ( empty( self::$for_object_name ) ) {
					return;
				}

				parent::boot_if_not_booted();
			}
		};

		$instance::$for_object_name = $post_type;
		$instance::boot_if_not_booted();

		return $instance;
	}

	/**
	 * Query builder class to use.
	 */
	public static function get_query_builder_class(): ?string {
		return Post_Query_Builder::class;
	}

	/**
	 * Create a new query instance.
	 *
	 * @return \Mantle\Database\Query\Post_Query_Builder<static>
	 */
	public static function query(): Post_Query_Builder {
		return ( new static() )->new_query();
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
		if ( ! empty( $this->attributes['post_parent'] ) ) {
			return static::find( (int) $this->attributes['post_parent'] );
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
	 * Getter for Object Status
	 *
	 * @return string
	 */
	public function status(): ?string {
		return $this->get( 'status' ) ?? null;
	}

	/**
	 * Getter for the Object Permalink
	 */
	public function permalink(): ?string {
		return (string) \get_permalink( $this->id() );
	}

	/**
	 * Retrieve the core object for the underlying object.
	 */
	public function core_object(): ?\WP_Post {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_post_object( $id );
		}

		return null;
	}

	/**
	 * Publish a post.
	 */
	public function publish(): bool {
		return $this->save( [ 'status' => 'publish' ] );
	}

	/**
	 * Schedule a post for publication.
	 *
	 * @param string|DateTime $date Date to schedule the post for.
	 */
	public function schedule( $date ): bool {
		if ( $date instanceof DateTime ) {
			$date = $date->format( 'Y-m-d H:i:s' );
		} else {
			$date = Carbon::parse( $date )->format( 'Y-m-d H:i:s' );
		}

		return $this->save(
			[
				'status' => 'future',
				'date'   => $date,
			]
		);
	}

	/**
	 * Save the model.
	 *
	 * @param array<string, mixed> $attributes Attributes to save.
	 *
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ): bool {
		$this->set_attributes( $attributes );

		$id = $this->id();

		// Update the post modified date if it has been modified.
		if ( $this->is_attribute_modified( 'post_modified' ) || $this->is_attribute_modified( 'post_modified_gmt' ) ) {
			add_filter( 'wp_insert_post_data', [ $this, 'set_post_modified_date_on_save' ] );
		}

		if ( empty( $id ) ) {
			$save = \wp_insert_post( $this->get_attributes(), true );

			if ( \is_wp_error( $save ) ) {
				throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
			}
		} else {
			$save = \wp_update_post(
				array_merge(
					$this->get_modified_attributes(),
					[
						'ID' => $id,
					]
				),
				true
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		// Set the post ID attribute.
		$this->set_raw_attribute( 'ID', $save );

		$this->store_queued_meta();
		$this->store_queued_terms();
		$this->refresh();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode.
	 * @return \WP_Post|false|mixed
	 */
	public function delete( bool $force = false ) {
		return \wp_delete_post( $this->id(), $force );
	}

	/**
	 * Allow the query to query against any post status.
	 *
	 * This will check against _any_ post status that is registered and does not
	 * use the 'any' post_status attribute.
	 *
	 * @param Builder $builder Query builder instance.
	 */
	public function scopeAnyStatus( Builder $builder ): Builder {
		return $builder->where(
			'post_status',
			array_values( get_post_stati( [], 'names' ) )
		);
	}

	/**
	 * Get the registerable route for the model.
	 */
	public static function get_route(): ?string {
		if ( 'post' === static::get_object_name() ) {
			$structure = get_option( 'permalink_structure' );

			if ( ! empty( $structure ) ) {
				$index     = 1;
				$structure = preg_replace_callback(
					'/\%/',
					function () use ( &$index ) {
						return ( $index++ ) % 2 ? '{' : '}';
					},
					(string) $structure
				);

				$route_structure = str_replace( '{postname}', '{post}', (string) $structure );
			} else {
				$route_structure = null;
			}
		} else {
			$route_structure = '/' . static::get_object_name() . '/{slug}';
		}

		/**
		 * Filter the route structure for a post handled through the entity router.
		 *
		 * @param string $route_structure Route structure.
		 * @param string $object_name Post type.
		 * @param string $object_class Model class name.
		 */
		return (string) apply_filters(
			'mantle_entity_router_post_route',
			$route_structure,
			static::get_object_name(),
			static::class
		);
	}

	/**
	 * Set the post's modified date on save via the 'wp_insert_post_data' filter.
	 *
	 * @param array<string, mixed> $data Data to save.
	 */
	public function set_post_modified_date_on_save( array $data ) {
		// Only update the post modified date if the post ID matches the current model.
		if ( isset( $data['ID'] ) && $this->id() !== (int) $data['ID'] ) {
			return $data;
		}

		if ( $this->is_attribute_modified( 'post_modified' ) ) {
			$date = Carbon::parse( $this->get_attribute( 'post_modified' ), wp_timezone() );
		} elseif ( $this->is_attribute_modified( 'post_modified_gmt' ) ) {
			$date = Carbon::parse( $this->get_attribute( 'post_modified_gmt' ), new \DateTimeZone( 'UTC' ) );
		} else {
			return $data;
		}

		$data['post_modified']     = $date->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
		$data['post_modified_gmt'] = $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

		// Unhook the filter after it has been used.
		remove_filter( 'wp_insert_post_data', [ $this, 'set_post_modified_date_on_save' ] );

		return $data;
	}
}
