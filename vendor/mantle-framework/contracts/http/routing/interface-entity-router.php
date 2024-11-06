<?php
/**
 * Entity_Router interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\Routing;

/**
 * Entity Router Contract
 */
interface Entity_Router {
	/**
	 * Add an entity to the router.
	 *
	 * @param Router $router Router instance.
	 * @param string $entity Entity class name.
	 * @param string $controller Controller class name.
	 */
	public function add( Router $router, string $entity, string $controller ): void;
}
