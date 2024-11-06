<?php
/**
 * Filesystem_Service_Provider class file.
 *
 * @package mantle
 */

namespace Mantle\Filesystem;

use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Database\Model\Attachment;
use Mantle\Support\Service_Provider;
use RuntimeException;

/**
 * Filesystem Service Provider
 */
class Filesystem_Service_Provider extends Service_Provider implements Isolated_Service_Provider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->register_native_filesystem();
		$this->register_flysystem();
	}

	/**
	 * Register the native filesystem.
	 *
	 * @return void
	 */
	protected function register_native_filesystem() {
		$this->app->singleton( 'files', fn () => new Filesystem() );
	}

	/**
	 * Register the Flysystem Manager
	 */
	public function register_flysystem(): void {
		$this->app->singleton( 'filesystem', fn ( $app ) => new Filesystem_Manager( $app ) );
	}

	/**
	 * Filter the attachment URL for cloud-stored attachments.
	 *
	 * @param string $url Attachment URL.
	 * @param int    $post_id Attachment ID.
	 */
	public function on_wp_get_attachment_url( string $url, int $post_id ): string {
		static $doing_wp_get_attachment_url = false;

		if ( ! $doing_wp_get_attachment_url ) {
			$doing_wp_get_attachment_url = true;

			$attachment = Attachment::find( $post_id );
			if ( $attachment ) {
				try {
					$url = $attachment->url();
				} catch ( RuntimeException $e ) {
					unset( $e );
				}
			}

			$doing_wp_get_attachment_url = false;
		}

		return $url;
	}
}
