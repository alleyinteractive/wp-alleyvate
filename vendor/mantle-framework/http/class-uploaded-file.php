<?php
/**
 * Uploaded_File class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 */

namespace Mantle\Http;

use Mantle\Container\Container;
use Mantle\Contracts\Filesystem\Filesystem_Manager;
use Mantle\Database\Model\Attachment;
use Mantle\Filesystem\File_Helpers;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use Mantle\Support\Traits\Macroable;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Handles Uploaded Files
 */
class Uploaded_File extends SymfonyUploadedFile {
	use File_Helpers;
	use Macroable;

	/**
	 * Store the uploaded file on a filesystem disk.
	 *
	 * @param  string       $path
	 * @param  array|string $options
	 * @return string|false
	 */
	public function store( $path, $options = [] ) {
		return $this->store_as( $path, $this->hash_name(), $this->parse_options( $options ) );
	}

	/**
	 * Store the uploaded file on a filesystem disk with public visibility.
	 *
	 * @param  string       $path
	 * @param  array|string $options
	 * @return string|false
	 */
	public function store_publicly( $path, $options = [] ) {
		$options = $this->parse_options( $options );

		$options['visibility'] = 'public';

		return $this->store_as( $path, $this->getFilename(), $options );
	}

	/**
	 * Store the uploaded file on a filesystem disk with public visibility.
	 *
	 * @param  string       $path
	 * @param  string       $name
	 * @param  array|string $options
	 * @return string|false
	 */
	public function store_publicly_as( $path, $name, $options = [] ) {
		$options = $this->parse_options( $options );

		$options['visibility'] = 'public';

		return $this->store_as( $path, $name, $options );
	}

	/**
	 * Store the uploaded file on a filesystem disk.
	 *
	 * @param  string       $path
	 * @param  string       $name
	 * @param  array|string $options
	 * @return string|false
	 */
	public function store_as( $path, $name, $options = [] ) {
		$path    = untrailingslashit( $path );
		$options = $this->parse_options( $options );

		$disk = Arr::pull( $options, 'disk' );

		return Container::get_instance()->make( Filesystem_Manager::class )->drive( $disk )->put_file_as(
			$path,
			$this,
			$name,
			$options
		);
	}

	/**
	 * Store the file as a WordPress attachment.
	 *
	 * @param string $path Path to store uploaded file to.
	 * @param string $name File name.
	 * @param array  $options Options for storage, disk name as string.
	 *
	 * @throws RuntimeException Thrown on error storing file.
	 * @todo Enable proper attachment meta data indexing.
	 */
	public function store_as_attachment( string $path = '/', string $name = null, $options = [] ): Attachment {
		$options = $this->parse_options( $options );

		// Set the default visibility for attachments to public.
		if ( ! isset( $options['visibility'] ) ) {
			$options['visibility'] = 'public';
		}

		if ( $name ) {
			$uploaded_file = $this->store_as( $path, $name, $options );
		} else {
			$uploaded_file = $this->store( $path, $options );
		}

		if ( empty( $uploaded_file ) ) {
			throw new RuntimeException( "Error uploading file to [{$path}]: [{$this->getFilename()}]" );
		}

		$disk_name = $options['disk'] ?? null;
		$disk      = Container::getInstance()->make( Filesystem_Manager::class )->drive( $disk_name );

		// Create the attachment for the file.
		$attachment = Attachment::create(
			[
				'post_mime_type' => $this->getClientMimeType(),
				'guid'           => $disk->url( $uploaded_file ),
				'post_parent'    => 0,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $uploaded_file ) ),
				'post_content'   => '',
				'meta'           => [
					'_wp_attached_file'                => Str::unpreceding_slash( trailingslashit( $path ) . $uploaded_file ),
					Attachment::META_KEY_CLOUD_STORAGE => [
						'disk'       => $disk_name,
						'name'       => $uploaded_file,
						'path'       => $path,
						'visibility' => $options['visibility'],
					],
				],
			]
		);

		return $attachment;
	}

	/**
	 * Get the contents of the uploaded file.
	 *
	 * @return bool|string
	 *
	 * @throws FileNotFoundException When file not found.
	 */
	public function get() {
		if ( ! $this->isValid() ) {
			throw new FileNotFoundException( "File does not exist at path {$this->getPathname()}." );
		}

		return file_get_contents( $this->getPathname() ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	/**
	 * Get the file's extension supplied by the client.
	 *
	 * @return string
	 */
	public function clientExtension() {
		return $this->guessClientExtension();
	}

	/**
	 * Parse and format the given options.
	 *
	 * @param  array|string $options
	 * @return array
	 */
	protected function parse_options( $options ) {
		if ( is_string( $options ) ) {
			return [ 'disk' => $options ];
		}

		return $options;
	}

	/**
	 * Create a new file instance from a base instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile $file
	 * @param  bool                                                $test
	 * @return static
	 */
	public static function createFromBase( \Symfony\Component\HttpFoundation\File\UploadedFile $file, $test = false ) {
		return $file instanceof static ? $file : new static(
			$file->getPathname(),
			$file->getClientOriginalName(),
			$file->getClientMimeType(),
			$file->getError(),
			$test
		);
	}
}
