<?php
/**
 * Attachment class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Facade\Storage;

/**
 * Attachment Model
 *
 * @method static \Mantle\Database\Factory\Post_Factory<static, \WP_Post, static> factory( array|callable|null $state = null )
 */
class Attachment extends Post {
	/**
	 * Attachment type for the model.
	 *
	 * @var string
	 */
	public static $object_name = 'attachment';

	/**
	 * Meta key for storage of file information in the cloud.
	 *
	 * @var string
	 */
	public const META_KEY_CLOUD_STORAGE = 'mantle_cloud';

	/**
	 * Get an attachment's URL by size.
	 *
	 * @param array|string $size Image URL.
	 *
	 * @throws Model_Exception Thrown when getting image.
	 */
	public function image_url( $size ): ?string {
		if ( ! $this->id() ) {
			throw new Model_Exception( 'Unable to get attachment URL for unsaved attachment.' );
		}

		return wp_get_attachment_image_url( $this->id(), $size ) ?: null;
	}

	/**
	 * Get the full-size attachment URL.
	 */
	public function url(): ?string {
		$settings = $this->get_cloud_settings();

		if ( empty( $settings['disk'] ) ) {
			$url = \wp_get_attachment_url( $this->id() );

			return $url ?: null;
		}

		// For private attachments serve a temporary URL.
		// todo: allow some permissions to be used.
		if ( ! empty( $settings['visibility'] ) && 'private' === $settings['visibility'] ) {
			return $this->get_temporary_url();
		}

		return Storage::drive( $settings['disk'] )->url( untrailingslashit( $settings['path'] ) . $settings['name'] );
	}

	/**
	 * Retrieve a temporary URL for a file.
	 *
	 * @param \DateTimeInterface $expiration File expiration.
	 */
	public function get_temporary_url( $expiration = null ): ?string {
		$settings = $this->get_cloud_settings();

		if ( empty( $settings['disk'] ) ) {
			return null;
		}

		$disk = $settings['disk'];

		if ( is_null( $expiration ) ) {
			$expiration = time() + max( 1, (int) config( "filesystem.disks.{$disk}.temporary_url_expiration" ) );
		}

		return Storage::drive( $disk )->temporary_url( untrailingslashit( $settings['path'] ) . $settings['name'], $expiration );
	}

	/**
	 * Get the stored cloud settings for an attachment.
	 */
	protected function get_cloud_settings(): ?array {
		return (array) $this->get_meta( static::META_KEY_CLOUD_STORAGE, true );
	}

	/**
	 * Create or get an already saved attachment from an URL address.
	 *
	 * Mirrors 'media_sideload_image()' with the ability to also load PDFs.
	 *
	 * @param string $url Image URL.
	 * @param array  $args  {
	 *        Optional. Arguments for the attachment. Default empty array.
	 *
	 *        @type string      $alt            Alt text.
	 *        @type string      $caption        Caption text.
	 *        @type string      $description    Description text.
	 *        @type array       $meta           Associate array of meta to set.
	 *                                          The value of alt text will
	 *                                          automatically be mapped into
	 *                                          this value and will be
	 *                                          overridden by the alt explicitly
	 *                                          passed into this array.
	 *        @type null|int    $parent_post_id Parent post id.
	 *        @type null|string $title          Title text. Null defaults to the
	 *                                          sanitized filename.
	 * }
	 * @throws Model_Exception Thrown on error sideloading image.
	 */
	public static function create_from_url( string $url, array $args = [] ): Model {
		$existing = static::query()->whereMeta( '_source_url', $url )->first();

		if ( $existing ) {
			return $existing;
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|pdf)\b/i', $url, $matches );

		if ( ! $matches ) {
			throw new Model_Exception( __( 'Invalid URL to create from.', 'mantle' ) );
		}

		$file_array         = [];
		$file_array['name'] = \wp_basename( $matches[0] );

		// Download file to temp location.
		$file_array['tmp_name'] = \download_url( $url );

		// If error storing temporarily, return the error.
		if ( \is_wp_error( $file_array['tmp_name'] ) ) {
			throw new Model_Exception( $file_array['tmp_name']->get_error_message() );
		}

		// Do the validation and storage of the file.
		$attachment_id = \media_handle_sideload( $file_array, 0, $args['description'] ?? '' );

		// If error storing permanently, unlink.
		if ( \is_wp_error( $attachment_id ) ) {
			@unlink( $file_array['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink, Generic.PHP.NoSilencedErrors.Forbidden
			throw new Model_Exception( $attachment_id->get_error_message() );
		}

		// Update the arguments if they exist.
		if ( ! empty( array_filter( $args ) ) ) {
			$args['ID'] = $attachment_id;
			\wp_update_post( $args );
		}

		return static::find( $attachment_id );
	}

	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 *
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ): bool {
		$this->set_attributes( $attributes );

		$id = $this->id();

		if ( empty( $id ) ) {
			$save = \wp_insert_attachment( $this->get_attributes(), false, 0, true );
		} else {
			$save = \wp_update_post(
				array_merge(
					$this->get_modified_attributes(),
					[
						'ID' => $id,
					]
				),
				true,
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		// Set the attachment ID attribute.
		$this->set_raw_attribute( 'ID', $save );
		$this->store_queued_meta();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the attachment.
	 *
	 * @param bool $force Force delete the model.
	 * @return mixed
	 */
	public function delete( bool $force = false ) {
		return \wp_delete_attachment( $this->id(), $force );
	}
}
