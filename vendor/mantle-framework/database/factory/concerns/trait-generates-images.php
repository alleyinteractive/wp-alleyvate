<?php
/**
 * Generates_Images trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Concerns;

use Mantle\Http_Client\Factory;
use Mantle\Support\Str;
use RuntimeException;

/**
 * Generate images for testing factories.
 *
 * Does not rely on FakerPHP since the image generation is being deprecated.
 */
trait Generates_Images {
	/**
	 * Generate an image.
	 *
	 * @param int         $width The width of the image.
	 * @param int         $height The height of the image.
	 * @param string|null $directory The directory to store the image to, defaults to the system temp directory.
	 * @param string|null $filename The filename to save the image as.
	 */
	public function generate_image( int $width, int $height, ?string $directory = null, ?string $filename = null ): string {
		if ( ! $directory ) {
			$directory = get_temp_dir();
		}

		$image = imagecreatetruecolor( $width, $height );

		if ( ! is_gd_image( $image ) ) {
			return $this->generate_remote_image( $width, $height, $directory, $filename );
		}

		imagefill( $image, 0, 0, imagecolorallocate( $image, 192, 192, 192 ) );

		if ( ! $filename ) {
			$filename = $this->generate_filename() . '.png';
		}

		// Ensure the filename ends with .png.
		if ( ! Str::ends_with( $filename, '.png' ) ) {
			$filename .= '.png';
		}

		imagepng( $image, "{$directory}/{$filename}" );

		imagedestroy( $image );

		return "{$directory}/{$filename}";
	}

	/**
	 * Generate an image by fetching the image from picsum.photos.
	 *
	 * @throws RuntimeException If the image cannot be fetched.
	 *
	 * @param int         $width The width of the image.
	 * @param int         $height The height of the image.
	 * @param string|null $directory The directory to save the image to.
	 * @param string|null $filename The filename to save the image as.
	 */
	public function generate_remote_image( int $width, int $height, ?string $directory = null, ?string $filename = null ): string {
		if ( ! $directory ) {
			$directory = get_temp_dir();
		}

		if ( ! $filename ) {
			$filename = $this->generate_filename() . '.jpg';
		}

		// Ensure the filename ends with .jpg.
		if ( ! Str::ends_with( $filename, '.jpg' ) ) {
			$filename .= '.jpg';
		}

		$request = ( new Factory() )
			->stream( "{$directory}/{$filename}" )
			->get( "https://picsum.photos/{$width}/{$height}" );

		if ( ! $request->ok() ) {
			throw new RuntimeException( 'Unable to fetch remote image from picsum.photos.' );
		}

		return "{$directory}/{$filename}";
	}

	/**
	 * Generate a random filename.
	 */
	protected function generate_filename(): string {
		return md5( (string) wp_generate_password( 15, false ) );
	}
}
