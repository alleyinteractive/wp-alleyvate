<?php
/**
 * Permalink_Generating class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Events;

use Mantle\Database\Model\Permalink_Generator;

/**
 * Permalink Generated Event
 *
 * Fired before the permalink is generated to allow for custom attributes to be
 * registered.
 */
class Permalink_Generated {
	/**
	 * Permalink generator.
	 *
	 * @var Permalink_Generator
	 */
	public $generator;

	/**
	 * Constructor.
	 *
	 * @param Permalink_Generator $generator
	 */
	public function __construct( Permalink_Generator $generator ) {
		$this->generator = $generator;
	}
}
