<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * This file contains the Assertions trait
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use BackedEnum;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Database\Model\User;
use PHPUnit\Framework\Assert as PHPUnit;
use WP_Post;
use WP_Term;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\get_term_object;

/**
 * Assorted Test_Cast assertions.
 */
trait Assertions {
	use Asset_Assertions;
	use Block_Assertions;

	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public static function assertWPError( $actual, $message = '' ): void {
		PHPUnit::assertInstanceOf( \WP_Error::class, $actual, $message );
	}

	/**
	 * Asserts that the given value is not an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public static function assertNotWPError( $actual, $message = '' ): void {
		if ( '' === $message && is_wp_error( $actual ) ) {
			$message = $actual->get_error_message();
		}
		PHPUnit::assertNotInstanceOf( \WP_Error::class, $actual, $message );
	}

	/**
	 * Asserts that the given value is an instance of WP_Post.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPPost( $actual, $message = '' ): void {
		PHPUnit::assertInstanceOf( \WP_Post::class, $actual, $message );
	}

	/**
	 * Asserts that the given value is an instance of WP_Term.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPTerm( $actual, $message = '' ): void {
		PHPUnit::assertInstanceOf( \WP_Term::class, $actual, $message );
	}

	/**
	 * Asserts that the given value is an instance of WP_User.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPUser( $actual, $message = '' ): void {
		PHPUnit::assertInstanceOf( \WP_User::class, $actual, $message );
	}

	/**
	 * Asserts that the given fields are present in the given object.
	 *
	 * @param object $object The object to check.
	 * @param array  $fields The fields to check.
	 */
	public static function assertEqualFields( $object, $fields ): void {
		foreach ( $fields as $field_name => $field_value ) {
			if ( $object->$field_name !== $field_value ) {
				PHPUnit::fail();
			}
		}
	}

	/**
	 * Asserts that two values are equal, with whitespace differences discarded.
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public static function assertDiscardWhitespace( $expected, $actual ): void {
		PHPUnit::assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}

	/**
	 * Asserts that two values are equal, with EOL differences discarded.
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public static function assertEqualsIgnoreEOL( $expected, $actual ): void {
		PHPUnit::assertEquals( str_replace( "\r\n", "\n", $expected ), str_replace( "\r\n", "\n", $actual ) );
	}

	/**
	 * Asserts that the contents of two un-keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public static function assertEqualSets( $expected, $actual ): void {
		sort( $expected );
		sort( $actual );
		PHPUnit::assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the contents of two keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public static function assertEqualSetsWithIndex( $expected, $actual ): void {
		ksort( $expected );
		ksort( $actual );
		PHPUnit::assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the given variable is a multidimensional array, and that all arrays are non-empty.
	 *
	 * @param array $array Array to check.
	 */
	public static function assertNonEmptyMultidimensionalArray( $array ): void {
		PHPUnit::assertTrue( is_array( $array ) );
		PHPUnit::assertNotEmpty( $array );

		foreach ( $array as $sub_array ) {
			PHPUnit::assertTrue( is_array( $sub_array ) );
			PHPUnit::assertNotEmpty( $sub_array );
		}
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected
	 * boolean value.
	 *
	 * Any properties that are listed by name as parameters will be expected to be
	 * true; all others are expected to be false. For example,
	 * assertQueryTrue( 'is_single', 'is_feed' ) means is_single() and is_feed()
	 * must be true and everything else must be false to pass.
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected
	 *                        to be true for the current request.
	 */
	public static function assertQueryTrue( ...$prop ): void {
		global $wp_query;

		$all = [
			'is_404',
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_comment_feed',
			'is_date',
			'is_day',
			'is_embed',
			'is_feed',
			'is_front_page',
			'is_home',
			'is_privacy_policy',
			'is_month',
			'is_page',
			'is_paged',
			'is_post_type_archive',
			'is_posts_page',
			'is_preview',
			'is_robots',
			'is_favicon',
			'is_search',
			'is_single',
			'is_singular',
			'is_tag',
			'is_tax',
			'is_time',
			'is_trackback',
			'is_year',
		];

		foreach ( $prop as $true_thing ) {
			PHPUnit::assertContains( $true_thing, $all, "Unknown conditional: {$true_thing}." );
		}

		$passed  = true;
		$message = '';

		foreach ( $all as $query_thing ) {
			$result = is_callable( $query_thing ) ? call_user_func( $query_thing ) : $wp_query->$query_thing;

			if ( in_array( $query_thing, $prop, true ) ) {
				if ( ! $result ) {
					$message .= $query_thing . ' is false but is expected to be true. ' . PHP_EOL;
					$passed   = false;
				}
			} elseif ( $result ) {
				$message .= $query_thing . ' is true but is expected to be false. ' . PHP_EOL;
				$passed   = false;
			}
		}

		if ( ! $passed ) {
			PHPUnit::fail( $message );
		}
	}

	/**
	 * Assert that a given ID matches the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 */
	public static function assertQueriedObjectId( int $id ): void {
		PHPUnit::assertSame( $id, get_queried_object_id(), 'Queried object ID is not the same.' );
	}

	/**
	 * Assert that a given ID does not the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 */
	public static function assertNotQueriedObjectId( int $id ): void {
		PHPUnit::assertNotSame( $id, get_queried_object_id(), 'Queried object ID is the same.' );
	}

	/**
	 * Assert that a given object is equivalent to the global queried object.
	 *
	 * @param mixed $object Expected object.
	 * @param bool  $strict Whether to assert the same object type or just the same identifying data.
	 */
	public static function assertQueriedObject( mixed $object, bool $strict = false ): void {
		$queried_object = get_queried_object();

		// Assert the same object types if strict mode.
		if ( $strict ) {
			PHPUnit::assertInstanceOf( $object::class, $queried_object );
		}

		// Next, assert identifying data about the object.
		match ( true ) {
			$object instanceof Post || $object instanceof User => PHPUnit::assertSame( $object->id(), $queried_object->ID, 'Queried object ID is not the same.' ),
			$object instanceof Term => PHPUnit::assertSame( $object->id(), $queried_object->term_id, 'Queried object ID is not the same.' ),
			$object instanceof \WP_Post || $object instanceof \WP_User => PHPUnit::assertSame( $object->ID, $queried_object->ID, 'Queried object ID is not the same.' ),
			$object instanceof \WP_Term => PHPUnit::assertSame( $object->term_id, $queried_object->term_id, 'Queried object ID is not the same.' ),
			$object instanceof \WP_Post_Type => PHPUnit::assertSame( $object->name, $queried_object->name, 'Queried object name is not the same.' ),
			default => PHPUnit::fail( 'Unknown object type.' ),
		};
	}

	/**
	 * Assert that a given object is not equivalent to the global queried object.
	 *
	 * @param mixed $object Expected object.
	 */
	public static function assertNotQueriedObject( mixed $object ): void {
		$queried_object = get_queried_object();

		match ( true ) {
			$object instanceof Post || $object instanceof User => PHPUnit::assertNotSame( $object->id(), $queried_object->ID, 'Queried object ID is the same.' ),
			$object instanceof Term => PHPUnit::assertNotSame( $object->id(), $queried_object->term_id, 'Queried object ID is the same.' ),
			$object instanceof \WP_Post || $object instanceof \WP_User => PHPUnit::assertNotSame( $object->ID, $queried_object->ID, 'Queried object ID is the same.' ),
			$object instanceof \WP_Term => PHPUnit::assertNotSame( $object->term_id, $queried_object->term_id, 'Queried object ID is the same.' ),
			$object instanceof \WP_Post_Type => PHPUnit::assertNotSame( $object->name, $queried_object->name, 'Queried object name is the same.' ),
			default => PHPUnit::fail( 'Unknown object type.' ),
		};
	}

	/**
	 * Assert that the queried object is null.
	 */
	public static function assertQueriedObjectNull(): void {
		PHPUnit::assertNull( get_queried_object(), 'Queried object is not null.' );
	}

	/**
	 * Assert if a post exists given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertPostExists( array $arguments ): void {
		$arguments = $this->serialize_arguments(
			$arguments,
			[
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'suppress_filters' => false,
			],
		);

		PHPUnit::assertNotEmpty(
			\get_posts( $arguments ),
			"Post not found with arguments: \n" . print_r( $arguments, true ),
		);
	}

	/**
	 * Assert if a post does not exist given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertPostDoesNotExists( array $arguments ): void {
		$arguments = $this->serialize_arguments(
			$arguments,
			[
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'suppress_filters' => false,
			],
		);

		PHPUnit::assertEmpty(
			\get_posts( $arguments ),
			"Post found with arguments: \n" . print_r( $arguments, true ),
		);
	}

	/**
	 * Assert if a term exists given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertTermExists( array $arguments ): void {
		$arguments = $this->serialize_arguments(
			$arguments,
			[
				'fields'     => 'ids',
				'count'      => 1,
				'hide_empty' => false,
			],
		);

		PHPUnit::assertNotEmpty(
			\get_terms( $arguments ),
			"Term not found with arguments: \n" . print_r( $arguments, true ),
		);
	}

	/**
	 * Assert if a term does not exist given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertTermDoesNotExists( array $arguments ): void {
		$arguments = $this->serialize_arguments(
			$arguments,
			[
				'fields'     => 'ids',
				'count'      => 1,
				'hide_empty' => false,
			],
		);

		PHPUnit::assertEmpty(
			\get_terms( $arguments ),
			"Term found with arguments: \n" . print_r( $arguments, true ),
		);
	}

	/**
	 * Assert if a user exists given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertUserExists( array $arguments ): void {
		$arguments = $this->serialize_arguments(
			$arguments,
			[
				'fields' => 'ids',
				'count'  => 1,
			],
		);

		PHPUnit::assertNotEmpty(
			\get_users( $arguments ),
			"User not found with arguments: \n" . print_r( $arguments, true ),
		);
	}

	/**
	 * Assert if a user does not exist given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertUserDoesNotExists( array $arguments ): void {
		$arguments = $this->serialize_arguments(
			$arguments,
			[
				'fields' => 'ids',
				'count'  => 1,
			],
		);

		PHPUnit::assertEmpty(
			\get_users( $arguments ),
			"User found with arguments: \n" . print_r( $arguments, true ),
		);
	}

	/**
	 * Get a term object from a flexible argument.
	 *
	 * @param mixed $argument Term object, term ID, or term slug.
	 */
	protected function get_term_from_argument( $argument ): ?WP_Term {
		if ( $argument instanceof Term ) {
			return $argument->core_object();
		}

		if ( is_int( $argument ) ) {
			return get_term_object( $argument );
		}

		if ( $argument instanceof WP_Term ) {
			return $argument;
		}

		return null;
	}

	/**
	 * Assert that a post has a specific term.
	 *
	 * `assertPostNotHasTerm()` is the inverse of this method.
	 *
	 * @param Post|\WP_Post|int $post Post to check.
	 * @param Term|\WP_Term|int $term Term to check.
	 */
	public function assertPostHasTerm( Post|WP_Post|int $post, Term|WP_Term|int $term ): void {
		if ( $post instanceof Post ) {
			$post = $post->id();
		}

		$term = $this->get_term_from_argument( $term );

		PHPUnit::assertInstanceOf( \WP_Term::class, $term, 'Term not found to assert against' );
		PHPUnit::assertTrue( \has_term( $term->term_id, $term->taxonomy, $post ), 'Term not found on post' );
	}

	/**
	 * Assert that a post doesn't have a specific term.
	 *
	 * `assertPostHasTerm()` is the inverse of this method.
	 *
	 * @param Post|\WP_Post|int $post Post to check.
	 * @param Term|\WP_Term|int $term Term to check.
	 */
	public function assertPostNotHasTerm( Post|WP_Post|int $post, Term|WP_Term|int $term ): void {
		if ( $post instanceof Post ) {
			$post = $post->id();
		}

		$term = $this->get_term_from_argument( $term );

		if ( $term ) {
			PHPUnit::assertFalse( \has_term( $term->term_id, $term->taxonomy, $post ), 'Term found on post' );
		}
	}

	/**
	 * Alias of `assertPostNotHasTerm()`.
	 *
	 * @param Post|\WP_Post|int $post Post to check.
	 * @param Term|\WP_Term|int $term Term to check.
	 */
	public function assertPostsDoesNotHaveTerm( Post|WP_Post|int $post, Term|WP_Term|int $term ): void {
		$this->assertPostNotHasTerm( $post, $term );
	}

	/**
	 * Serialize arguments for use in assertions.
	 *
	 * Convert string-backed enums to an array of all possible values from an enumeration.
	 *
	 * @param array $arguments Arguments to serialize.
	 * @param array $defaults  Default values.
	 */
	protected function serialize_arguments( array $arguments, array $defaults = [] ): array {
		$arguments = array_merge( $defaults, $arguments );

		foreach ( $arguments as $key => $value ) {
			if ( $value instanceof Arrayable ) {
				$arguments[ $key ] = $value->to_array();
			}

			// Check for PHP 8.1+ support.
			if ( interface_exists( BackedEnum::class ) ) {
				// Convert an enum to an array of all possible values.
				if ( is_string( $value ) && enum_exists( $value ) && is_subclass_of( $value, BackedEnum::class ) ) {
					$arguments[ $key ] = collect( $value::cases() )->pluck( 'value' )->all();
				}

				// Convert an enum object to its value.
				if ( is_object( $value ) && $value instanceof BackedEnum ) {
					$arguments[ $key ] = $value->value;
				}

				// Convert an array of enum objects to an array of their values.
				if ( is_array( $value ) ) {
					$arguments[ $key ] = array_map(
						fn ( $item ) => is_object( $item ) && $item instanceof BackedEnum ? $item->value : $item,
						$value,
					);
				}
			}
		}

		return $arguments;
	}
}
