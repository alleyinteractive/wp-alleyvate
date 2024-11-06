<?php
/**
 * Local_Adapter class file
 *
 * @package Mantle
 */

namespace Mantle\Filesystem\Adapter;

use Mantle\Filesystem\Filesystem_Adapter;

/**
 * Local Filesystem Adapter
 */
class Local_Adapter extends Filesystem_Adapter {
	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param  string $path
	 */
	public function url( string $path ): string {
		// If an explicit base URL has been set on the disk configuration then we
		// will use it as the base URL instead of the default path. This allows the
		// developer to have full control over the base path for this filesystem's
		// generated URLs.
		if ( ! empty( $this->config['url'] ) ) {
			return $this->concat_path_to_url( $this->config['url'], $path );
		}

		return rtrim( (string) wp_upload_dir()['baseurl'], '/' ) . '/' . ltrim( $path, '/' );
	}
}
