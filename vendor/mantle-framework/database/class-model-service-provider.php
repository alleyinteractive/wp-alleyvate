<?php
/**
 * Model_Register class file.
 *
 * @package Mantle
 */

namespace Mantle\Database;

use Mantle\Database\Model\Model;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Framework\Manifest\Model_Manifest;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider;

/**
 * Model Service Provider
 */
class Model_Service_Provider extends Service_Provider {
	/**
	 * Models to register for the application.
	 *
	 * @var string[]
	 */
	protected $models = [];

	/**
	 * Register the service provider.
	 */
	public function register(): void {
	}

	/**
	 * Bootstrap the service provider.
	 */
	public function boot(): void {
		// Allow the configuration to disable discovery.
		if ( $this->app['config']->get( 'models.disable_discovery', false ) ) {
			return;
		}

		$configuration = (array) $this->app['config']->get( 'models.register', [] );

		// Allows models to always be booted on each request to register whatever side-effects they desire.
		$this->set_models_to_register(
			array_merge(
				$configuration,
				$this->app[ Model_Manifest::class ]->models()
			)
		);

		if ( empty( $this->models ) ) {
			return;
		}

		foreach ( $this->models as $model ) {
			if ( class_exists( $model ) ) {
				$model::boot_if_not_booted();
			}
		}
	}

	/**
	 * Set the models to register.
	 *
	 * @param string[] $models Models to register.
	 */
	public function set_models_to_register( array $models ): void {
		$this->models = array_unique( $models );
	}

	/**
	 * Register the internal taxonomy for post <--> post relationships.
	 */
	#[Action( 'init', 5 )]
	public static function register_internal_taxonomy(): void {
		register_taxonomy(
			Relation::RELATION_TAXONOMY,
			array_keys( get_post_types() ),
			[
				'public'       => false,
				'rewrite'      => false,
				'show_in_rest' => false,
			]
		);
	}
}
