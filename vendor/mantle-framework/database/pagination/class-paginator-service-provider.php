<?php
/**
 * Paginator_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Pagination;

use Mantle\Support\Service_Provider;

/**
 * Paginator Service Provider
 */
class Paginator_Service_Provider extends Service_Provider {
	/**
	 * Register the provider
	 */
	public function register(): void {
		if ( isset( $this->app['view.loader'] ) ) {
			$this->app['view.loader']->add_path( __DIR__ . '/resources', 'paginator' );
		}
	}
}
