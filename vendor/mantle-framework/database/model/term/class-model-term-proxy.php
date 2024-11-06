<?php
/**
 * Model_Term_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Term;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use RuntimeException;

/**
 * Allow term to be retrieve as an attribute on the object.
 *
 * @property Term[] $category Categories for the model.
 * @property Term[] $post_tag Tags for the model.
 */
class Model_Term_Proxy {
	/**
	 * Constructor.
	 *
	 * @param Post $model Model to reference.
	 */
	public function __construct( protected Post $model ) {
	}

	/**
	 * Retrieve model terms by taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return Term[]
	 */
	public function __get( string $taxonomy ) {
		$queued_value = $this->model->get_queued_term_attribute( $taxonomy );

		if ( null !== $queued_value ) {
			return $queued_value;
		}

		return $this->model->get_terms( $taxonomy );
	}

	/**
	 * Set model term.
	 *
	 * @param string                  $taxonomy Taxonomy name..
	 * @param Term[]|\WP_Term[]|int[] $values Terms.
	 */
	public function __set( string $taxonomy, $values ) {
		$this->model->queue_term_attribute( $taxonomy, $values );
	}

	/**
	 * Delete model terms.
	 *
	 * @throws RuntimeException Thrown on usage.
	 * @param string $key Taxonomy key.
	 */
	public function __unset( string $key ) {
		throw new RuntimeException( 'Deleting model terms by attribute is not supported.' );
	}
}
