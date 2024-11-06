<?php
/**
 * Scope interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Database;

use Mantle\Database\Model\Model;
use Mantle\Database\Query\Builder;

/**
 * Query Scope Contract
 */
interface Scope {
	/**
	 * Apply the scope to a given query builder.
	 *
	 * @param Builder $builder Query Builder instance.
	 * @param Model   $model Model object.
	 */
	public function apply( Builder $builder, Model $model );
}
