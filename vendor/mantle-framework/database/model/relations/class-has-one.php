<?php
/**
 * Has_One class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Mantle\Support\Collection;

/**
 * Has One Relationship
 */
class Has_One extends Has_One_Or_Many {
	/**
	 * Get the results of the relationship.
	 *
	 * @return \Mantle\Database\Model\Model|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->first();
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
				$key = $model[ $this->local_key ];
				$model->set_relation( $this->relationship, $dictionary[ $key ][0] ?? null );
			}
		);
	}
}
