<?php
/**
 * Model_Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Term;

use InvalidArgumentException;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Term;
use Mantle\Support\Arr;
use Mantle\Support\Collection;
use WP_Term;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\get_term_object;
use function Mantle\Support\Helpers\get_term_object_by;

/**
 * Interface for interfacing with a model's terms.
 *
 * @property Model_Term_Proxy $terms Proxy to manage terms for the model.
 */
trait Model_Term {
	/**
	 * Terms queued for saving.
	 *
	 * @var array
	 */
	protected $queued_terms = [];

	/**
	 * Retrieve the terms 'attribute'.
	 *
	 * @return Model_Term_Proxy
	 */
	public function get_terms_attribute() {
		return new Model_Term_Proxy( $this );
	}

	/**
	 * Allow setting terms through an array via an attribute mutator.
	 *
	 * @param array $values Term values to set.
	 * @throws Model_Exception Thrown on invalid value being set.
	 */
	public function set_terms_attribute( $values ): void {
		if ( ! is_array( $values ) ) {
			throw new Model_Exception( 'Attribute value passed to terms is not an array.' );
		}

		$this->queued_terms = $values;
	}

	/**
	 * Get a queued term attribute.
	 *
	 * @param string $key Taxonomy key.
	 * @return mixed|null Terms or null.
	 */
	public function get_queued_term_attribute( string $key ) {
		return ( $this->queued_terms[ $key ] ?? [] )[0] ?? null;
	}

	/**
	 * Queue a term for saving
	 * Allows terms to be set before a post is saved.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param mixed  $value Terms.
	 */
	public function queue_term_attribute( string $taxonomy, $value ): void {
		$this->queued_terms[ $taxonomy ] = $value;
	}

	/**
	 * Store queued model terms.
	 */
	protected function store_queued_terms() {
		if ( empty( $this->queued_terms ) ) {
			return;
		}

		// Determine if this is an array of terms instead of taxonomy => term pairs.
		if ( Arr::is_assoc( $this->queued_terms ) ) {
			foreach ( $this->queued_terms as $taxonomy => $values ) {
				$this->set_terms( $values, $taxonomy );
			}
		} else {
			$this->set_terms( $this->queued_terms );
		}

		$this->queued_terms = [];
	}

	/**
	 * Get term(s) associated with a post.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return Term[]
	 */
	public function get_terms( string $taxonomy ): array {
		$terms = \get_the_terms( $this->id(), $taxonomy );

		if ( empty( $terms ) || \is_wp_error( $terms ) ) {
			return [];
		}

		return array_map(
			fn ( WP_Term $term ) => Term::new_from_existing( (array) $term ),
			(array) $terms,
		);
	}

	/**
	 * Set the term(s) associated with a post.
	 *
	 * @param mixed  $terms Accepts an array of or a single instance of terms.
	 * @param string $taxonomy Taxonomy name, optional.
	 * @param bool   $append Append to the object's terms, defaults to false.
	 * @return static
	 *
	 * @throws Model_Exception Thrown if the $taxonomy cannot be inferred from $terms.
	 * @throws Model_Exception Thrown if error saving the post's terms.
	 */
	public function set_terms( $terms, ?string $taxonomy = null, bool $append = false ) {
		$terms = collect( Arr::wrap( $terms ) );

		// If taxonomy is not specified, chunk the terms into taxonomy groups.
		if ( ! $taxonomy ) {
			$terms = $terms->reduce(
				function ( array $carry, $term ): array {
					if ( $term instanceof WP_Term ) {
						$carry[ $term->taxonomy ][] = $term;

						return $carry;
					}

					if ( $term instanceof Term ) {
						$carry[ $term->taxonomy ][] = $term->core_object();

						return $carry;
					}

					// Support an array of taxonomy => term ID/object/slug pairs.
					if ( is_array( $term ) ) {
						foreach ( $term as $taxonomy => $item ) {
							if ( $item instanceof WP_Term || $item instanceof Term ) {
								$carry[ $item->taxonomy ][] = $item instanceof Term
									? $item->core_object()
									: $item;

								continue;
							}

							if ( is_numeric( $item ) ) {
								$item = get_term_object( $item );

								if ( $item instanceof WP_Term ) {
									$carry[ $item->taxonomy ][] = $item;
								}

								continue;
							}

							// Attempt to infer if the key is a taxonomy slug and this is a
							// taxonomy => term slug pair.
							if ( ! is_string( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
								continue;
							}

							$item = get_term_object_by( 'slug', $item, $taxonomy );

							if ( $item ) {
								$carry[ $taxonomy ][] = $item;
							}
						}

						return $carry;
					}

					if ( ! is_numeric( $term ) ) {
						throw new InvalidArgumentException( "Invalid term value passed to set_terms (expected Term/WP_Term/int): {$term}" );
					}

					$term = get_term_object( $term );

					if ( $term ) {
						$carry[ $term->taxonomy ][] = $term;
					}

					return $carry;
				},
				[],
			);

			foreach ( collect( $terms )->filter() as $taxonomy => $items ) {
				$this->set_terms( Arr::pluck( $items, 'term_id' ), $taxonomy, $append );
			}

			return $this;
		}

		// Convert the terms to a array of term IDs.
		$terms = $terms
			->map(
				function ( $term ) {
					if ( $term instanceof WP_Term || $term instanceof Term ) {
						return $term->term_id;
					}

					return $term;
				}
			)
			->filter()
			->all();

		$update = \wp_set_object_terms( $this->id(), $terms, $taxonomy, $append );

		if ( \is_wp_error( $update ) ) {
			throw new Model_Exception( "Error setting model terms: [{$update->get_error_message()}]" );
		}

		return $this;
	}


	/**
	 * Remove terms from a post.
	 *
	 * @param mixed  $terms Accepts an array of or a single instance of terms.
	 * @param string $taxonomy Taxonomy name, optional.
	 * @return static
	 *
	 * @throws Model_Exception Thrown if the $taxonomy cannot be inferred from $terms.
	 */
	public function remove_terms( $terms, string $taxonomy = null ) {
		$terms = collect( Arr::wrap( $terms ) )
			->map(
				function ( $term ) use ( &$taxonomy ) {
					if ( $term instanceof Term ) {
						if ( empty( $taxonomy ) ) {
							$taxonomy = $term->taxonomy();
						}

						return $term->id();
					}

					if ( $term instanceof \WP_Term ) {
						if ( empty( $taxonomy ) ) {
							$taxonomy = $term->taxonomy;
						}

						return $term->term_id;
					}

					return $term;
				}
			)
			->filter()
			->all();

		if ( empty( $taxonomy ) ) {
			throw new Model_Exception( 'Term taxonomy not able to be inferred.' );
		}

		\wp_remove_object_terms( $this->id(), $terms, $taxonomy );

		return $this;
	}
}
