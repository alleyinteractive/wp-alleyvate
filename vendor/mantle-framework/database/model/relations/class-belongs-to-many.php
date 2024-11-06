<?php
/**
 * Belongs_To_Many class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Mantle\Support\Collection;

use function Mantle\Support\Helpers\collect;

/**
 * Creates a 'Belongs To Many' relationship.
 */
class Belongs_To_Many extends Belongs_To {
	/**
	 * Retrieve the results of the query.
	 *
	 * @return Collection|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->get();
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection $models Parent models.
	 * @param Collection $results Eagerly loaded results to match.
	 */
	public function match( Collection $models, Collection $results ): Collection {
		$dictionary = $this->build_dictionary( $results, $models );

		return $models->each(
			function ( $model ) use ( $dictionary ): void {
				$key = $model->{$this->foreign_key};

				$model->set_relation( $this->relationship, $dictionary[ $key ] ?? null );
			}
		);
	}

	/**
	 * Build a model dictionary keyed by the relation's foreign key.
	 *
	 * @param Collection $results Collection of results.
	 * @param Collection $models Eagerly loaded results to match.
	 */
	protected function build_dictionary( Collection $results, Collection $models ): array {
		$results    = $results->key_by( $this->foreign_key );
		$dictionary = collect();

		foreach ( $models as $model ) {
			$dictionary[ $model->{$this->foreign_key} ] = $model->get_meta( $this->local_key, false );
		}

		return $dictionary
			->map(
				fn ( $child_ids ) => $results->only( $child_ids )->values()->all()
			)
			->filter()
			->all();
	}
}
