<?php
/**
 * Route_File_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

/**
 * Route File Registrar
 */
class Route_File_Registrar {

	/**
	 * The router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Create a new route file registrar instance.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( Router $router ) {
		$this->router = $router;
	}

	/**
	 * Require the given routes file.
	 *
	 * @param  string $routes Routes to register.
	 */
	public function register( string $routes ): void {
		$router = $this->router;
		require $routes; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	}
}
