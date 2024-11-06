<?php
/**
 * Entity_Route_Added class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Events;

/**
 * Event for adding an entity route.
 */
class Entity_Route_Added {
	/**
	 * Entity class name.
	 *
	 * @var string
	 */
	public $entity;

	/**
	 * Controller class name.
	 *
	 * @var string
	 */
	public $controller;

	/**
	 * Constructor.
	 *
	 * @param string $entity Entity class name.
	 * @param string $controller Controller class name.
	 */
	public function __construct( string $entity, string $controller ) {
		$this->entity     = $entity;
		$this->controller = $controller;
	}
}
