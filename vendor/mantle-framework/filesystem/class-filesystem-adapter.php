<?php
/**
 * Filesystem_Adapter class file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 *
 * @package Mantle
 */

namespace Mantle\Filesystem;

use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Mantle\Contracts\Filesystem\Filesystem;
use Mantle\Http\Uploaded_File;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Mantle\Support\Helpers\throw_if;

/**
 * Filesystem to Flysystem Adapter
 *
 * @mixin \League\Flysystem\FilesystemOperator
 */
class Filesystem_Adapter implements Filesystem {
	/**
	 * Path prefixer.
	 */
	protected PathPrefixer $prefixer;

	/**
	 * Constructor.
	 *
	 * @param FilesystemOperator $driver Filesystem instance.
	 * @param FilesystemAdapter  $adapter Filesystem adapter.
	 * @param array              $config Filesystem configuration.
	 */
	public function __construct(
		protected FilesystemOperator $driver,
		protected FilesystemAdapter $adapter,
		protected array $config = [],
	) {
		$separator = $config['directory_separator'] ?? DIRECTORY_SEPARATOR;

		$this->prefixer = new PathPrefixer( $this->config['root'] ?? '', $separator );

		if ( isset( $config['prefix'] ) ) {
			$this->prefixer = new PathPrefixer( $this->prefixer->prefixPath( $config['prefix'] ), $separator );
		}
	}

	/**
	 * Assert that the given file exists.
	 *
	 * @param string[]|string $path File path.
	 * @return static
	 */
	public function assertExists( $path ) {
		$paths = Arr::wrap( $path );

		foreach ( $paths as $path ) {
			PHPUnit::assertTrue(
				$this->exists( $path ),
				"Unable to find a file at path [{$path}]."
			);
		}

		return $this;
	}

	/**
	 * Assert that the given file does not exist.
	 *
	 * @param  string[]|string $path File path.
	 * @return static
	 */
	public function assertMissing( $path ) {
		$paths = Arr::wrap( $path );

		foreach ( $paths as $path ) {
				PHPUnit::assertFalse(
					$this->exists( $path ),
					"Found unexpected file at path [{$path}]."
				);
		}

		return $this;
	}

	/**
	 * Get all (recursive) of the directories within a given directory.
	 *
	 * @param  string $directory Directory name.
	 * @return string[]
	 */
	public function all_directories( string $directory = null ): array {
		return $this->directories( $directory, true );
	}

	/**
	 * Get all the directories within a given directory.
	 *
	 * @param string $directory Directory name.
	 * @param bool   $recursive Flag if it should be recursive.
	 * @return array<string>
	 */
	public function directories( string $directory = null, bool $recursive = false ): array {
		return $this->driver->listContents( $directory, $recursive )
			->filter(
				fn ( StorageAttributes $attributes ) => $attributes->isDir()
			)
			->map(
				fn ( StorageAttributes $attributes ) => $attributes->path()
			)
			->toArray();
	}

	/**
	 * Create a directory.
	 *
	 * @param string $path Path to create.
	 */
	public function make_directory( string $path ): bool {
		try {
			$this->driver->createDirectory( $path );
		} catch ( UnableToCreateDirectory $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory name.
	 */
	public function delete_directory( string $directory ): bool {
		try {
			$this->driver->deleteDirectory( $directory );
		} catch ( UnableToDeleteDirectory $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Get all of the files from the given directory (recursive).
	 *
	 * @param string $directory Directory name.
	 * @return string[]
	 */
	public function all_files( string $directory = null ): array {
		return $this->files( $directory, true );
	}

	/**
	 * Get an array of all files in a directory.
	 *
	 * @param string $directory Directory name.
	 * @param bool   $recursive Flag if recursive.
	 * @return string[]
	 */
	public function files( string $directory = null, bool $recursive = false ): array {
		return $this->driver->listContents( $directory, $recursive )
			->filter(
				fn ( StorageAttributes $attributes ) => $attributes->isFile()
			)
			->sortByPath()
			->map(
				fn ( StorageAttributes $attributes ) => $attributes->path()
			)
			->toArray();
	}

	/**
	 * Copy a file from one location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 */
	public function copy( string $from, string $to ): bool {
		try {
			$this->driver->copy( $from, $to );
		} catch ( UnableToCopyFile $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Move a file from a location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 */
	public function move( string $from, string $to ): bool {
		try {
			$this->driver->move( $from, $to );
		} catch ( UnableToMoveFile $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Delete a file at the given paths.
	 *
	 * @param string|string[] $paths File paths.
	 */
	public function delete( $paths ): bool {
		$paths   = is_array( $paths ) ? $paths : func_get_args();
		$success = true;

		foreach ( $paths as $path ) {
			try {
				$this->driver->delete( $path );
			} catch ( UnableToDeleteFile $e ) {
				throw_if( $this->throws_exceptions(), $e );

				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Check if a file exists at a current path.
	 *
	 * @param string $path
	 */
	public function exists( string $path ): bool {
		return $this->driver->has( $path );
	}

	/**
	 * Check if a file is missing at a given path.
	 *
	 * @param string $path File path.
	 */
	public function missing( string $path ): bool {
		return ! $this->exists( $path );
	}

	/**
	 * Get the full path for the file at the given "short" path.
	 *
	 * @param string $path File path.
	 */
	public function path( string $path ): string {
		return $this->prefixer->prefixPath( $path );
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param string $path File path.
	 * @return string|null
	 */
	public function get( string $path ) {
		try {
			return $this->driver->read( $path );
		} catch ( UnableToReadFile $e ) {
			throw_if( $this->throws_exceptions(), $e );
		}

		return null;
	}

	/**
	 * Create a streamed response for a given file.
	 *
	 * @param string      $path File path.
	 * @param string|null $name File name.
	 * @param array       $headers Headers to include.
	 * @param string      $disposition File disposition.
	 */
	public function response( string $path, ?string $name = null, array $headers = [], string $disposition = 'inline' ): StreamedResponse {
		$response = new StreamedResponse();

		if ( ! array_key_exists( 'Content-Type', $headers ) ) {
			$headers['Content-Type'] = $this->mimeType( $path );
		}

		if ( ! array_key_exists( 'Content-Length', $headers ) ) {
			$headers['Content-Length'] = $this->size( $path );
		}

		if ( ! array_key_exists( 'Content-Disposition', $headers ) ) {
			$filename = $name ?? basename( $path );

			$disposition = $response->headers->makeDisposition(
				$disposition,
				$filename,
				$this->fallback_name( $filename )
			);

			$headers['Content-Disposition'] = $disposition;
		}

		$response->headers->replace( $headers );

		$response->setCallback(
			function () use ( $path ): void {
				$stream = $this->readStream( $path );
				fpassthru( $stream );
				fclose( $stream );
			}
		);

		return $response;
	}

	/**
	 * Create a streamed download response for a given file.
	 *
	 * @param string      $path File path.
	 * @param string|null $name File name.
	 * @param array       $headers HTTP headers.
	 */
	public function download( $path, $name = null, array $headers = [] ): StreamedResponse {
		return $this->response( $path, $name, $headers, 'attachment' );
	}

	/**
	 * Convert the string to ASCII characters that are equivalent to the given name.
	 *
	 * @param string $name Fallback name.
	 */
	protected function fallback_name( string $name ): string {
		return str_replace( '%', '', Str::ascii( $name ) );
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @param string $path File path.
	 */
	public function last_modified( string $path ): int {
		return $this->driver->lastModified( $path );
	}

	/**
	 * Get the mime-type of a given file.
	 *
	 * @param string $path File path.
	 * @return string|false
	 */
	public function mime_type( string $path ) {
		return $this->driver->mimeType( $path );
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param string                                             $path File path.
	 * @param string|File|Uploaded_File|StreamInterface|resource $contents File contents.
	 * @param array|string                                       $options  Options for the files or a string visibility.
	 */
	public function put( string $path, $contents, $options = [] ): bool {
		$options = is_string( $options )
			? [ 'visibility' => $options ]
			: (array) $options;

		if (
			$contents instanceof File
			|| $contents instanceof Uploaded_File
		) {
			return $this->put_file( $path, $contents, $options ) ? true : false;
		}

		if ( $contents instanceof StreamInterface ) {
			$this->driver->writeStream( $path, $contents->detach(), $options );

			return true;
		}

		is_resource( $contents )
			? $this->driver->writeStream( $path, $contents, $options )
			: $this->driver->write( $path, $contents, $options );

		return true;
	}

	/**
	 * Store the uploaded file on the disk.
	 *
	 * @param string                                 $path File path.
	 * @param File|\Mantle\Http\Uploaded_File|string $file File object.
	 * @param mixed                                  $options Options.
	 * @return string|false
	 */
	public function put_file( string $path, $file, $options = [] ): string|bool {
		$file = is_string( $file ) ? new File( $file ) : $file;

		return $this->put_file_as( $path, $file, $file->hash_name(), $options );
	}

	/**
	 * Store the uploaded file on the disk with a given name.
	 *
	 * @param  string                                 $path File path.
	 * @param  File|\Mantle\Http\Uploaded_File|string $file File object.
	 * @param  string                                 $name File name.
	 * @param  mixed                                  $options Options.
	 * @return string|false
	 */
	public function put_file_as( string $path, $file, string $name, $options = [] ): string|bool {
		$stream = fopen( is_string( $file ) ? $file : $file->getRealPath(), 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$path   = trim( $path . '/' . $name, '/' );

		// Next, we will format the path of the file and store the file using a stream since
		// they provide better performance than alternatives. Once we write the file this
		// stream will get closed automatically by us so the developer doesn't have to.
		$result = $this->put(
			$path,
			$stream,
			$options
		);

		if ( is_resource( $stream ) ) {
			fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}

		return $result ? $path : false;
	}

	/**
	 * Retrieve the size of the file.
	 *
	 * @param string $path File path.
	 */
	public function size( string $path ): int {
		return $this->driver->fileSize( $path );
	}

	/**
	 * {@inheritdoc}
	 */
	public function readStream( $path ) {
		try {
			return $this->driver->readStream( $path );
		} catch ( UnableToReadFile $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}
	}

	/**
	 * Read a file through a stream.
	 *
	 * @param string $path File path.
	 * @return resource|false The path resource or false on failure.
	 */
	public function read_stream( string $path ) {
		return $this->readStream( $path );
	}

	/**
	 * {@inheritdoc}
	 */
	public function writeStream( string $path, $resource, $options = [] ): bool {
		$options = is_string( $options )
			? [ 'visibility' => $options ]
			: (array) $options;

		try {
			$this->driver->writeStream( $path, $resource, $options );
		} catch ( UnableToWriteFile | UnableToSetVisibility $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Write a file through a stream.
	 *
	 * @param string       $path File path.
	 * @param resource     $resource File resource.
	 * @param array|string $options File options or string visibility.
	 */
	public function write_stream( string $path, $resource, $options = [] ): bool {
		return $this->writeStream( $path, $resource, $options );
	}

	/**
	 * Retrieve a file's visibility.
	 *
	 * @param string $path
	 */
	public function get_visibility( string $path ): string {
		return $this->driver->visibility( $path ) === Visibility::PUBLIC
			? Filesystem::VISIBILITY_PUBLIC
			: Filesystem::VISIBILITY_PRIVATE;
	}

	/**
	 * Set the visibility for a file.
	 *
	 * @param string $path Path to set.
	 * @param string $visibility Visibility to set.
	 */
	public function setVisibility( string $path, string $visibility ): bool {
		try {
			$this->driver->setVisibility( $path, $this->parse_visibility( $visibility ) );
		} catch ( UnableToSetVisibility $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Set the visibility for a file (alias).
	 *
	 * @param string $path Path to set.
	 * @param string $visibility Visibility to set.
	 */
	public function set_visibility( string $path, string $visibility ): bool {
		return $this->setVisibility( $path, $visibility );
	}

	/**
	 * Prepend to a file.
	 *
	 * @param string $path File to prepend.
	 * @param string $data Data to prepend.
	 * @param string $separator Separator from existing data.
	 * @return bool
	 */
	public function prepend( string $path, string $data, string $separator = PHP_EOL ) {
		if ( $this->exists( $path ) ) {
			return $this->put( $path, $data . $separator . $this->get( $path ) );
		}

		return $this->put( $path, $data );
	}

	/**
	 * Append to a file.
	 *
	 * @param string $path File to append.
	 * @param string $data Data to append.
	 * @param string $separator Separator from existing data.
	 * @return bool
	 */
	public function append( $path, $data, $separator = PHP_EOL ) {
		if ( $this->exists( $path ) ) {
			return $this->put( $path, $this->get( $path ) . $separator . $data );
		}

		return $this->put( $path, $data );
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param string $path Path to the file.
	 *
	 * @throws RuntimeException Thrown on invalid filesystem adapter.
	 */
	public function url( string $path ): ?string {
		if ( isset( $this->config['prefix'] ) ) {
			$path = $this->concat_path_to_url( $this->config['prefix'], $path );
		}

		$adapter = $this->adapter;

		if ( method_exists( $adapter, 'getUrl' ) ) {
			return $adapter->getUrl( $path );
		} elseif ( method_exists( $adapter, 'get_url' ) ) {
			return $adapter->get_url( $path );
		} elseif ( method_exists( $this->driver, 'getUrl' ) ) {
			return $this->driver->getUrl( $path );
		} elseif ( method_exists( $this->driver, 'get_url' ) ) {
			return $this->driver->get_url( $path );
		} else {
			throw new RuntimeException( 'This driver does not support retrieving URLs.' );
		}
	}

	/**
	 * Determine if temporary URLs can be generated.
	 */
	public function provides_temporary_urls(): bool {
		return method_exists( $this->adapter, 'getTemporaryUrl' ) || method_exists( $this->adapter, 'get_temporary_url' );
	}

	/**
	 * Get a temporary URL for the file at the given path.
	 *
	 * @param  string             $path File path.
	 * @param  \DateTimeInterface $expiration File expiration.
	 * @param  array              $options Options for the URL.
	 *
	 * @throws RuntimeException Thrown on missing temporary URL.
	 */
	public function temporary_url( string $path, $expiration, array $options = [] ): string {
		if ( method_exists( $this->adapter, 'getTemporaryUrl' ) ) {
			return $this->adapter->getTemporaryUrl( $path, $expiration, $options );
		} elseif ( method_exists( $this->adapter, 'get_temporary_url' ) ) {
			return $this->adapter->get_temporary_url( $path, $expiration, $options );
		}

		throw new RuntimeException( 'This driver does not support creating temporary URLs.' );
	}

	/**
	 * Concatenate a path to a URL.
	 *
	 * @param  string $url
	 * @param  string $path
	 */
	protected function concat_path_to_url( string $url, string $path ): string {
		return rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
	}

	/**
	 * Replace the scheme, host and port of the given UriInterface with values from the given URL.
	 *
	 * @param  \Psr\Http\Message\UriInterface $uri
	 * @param  string                         $url
	 */
	protected function replace_base_url( UriInterface $uri, string $url ): UriInterface {
		$parsed = wp_parse_url( $url );

		return $uri
			->withScheme( $parsed['scheme'] )
			->withHost( $parsed['host'] )
			->withPort( $parsed['port'] ?? null );
	}

	/**
	 * Parse the given visibility value.
	 *
	 * @param string $visibility Visibility to set.
	 *
	 * @throws InvalidArgumentException Thrown on invalid visibility.
	 */
	protected function parse_visibility( string $visibility ): string {
		return match ( $visibility ) {
			Filesystem::VISIBILITY_PUBLIC => Visibility::PUBLIC,
			Filesystem::VISIBILITY_PRIVATE => Visibility::PRIVATE,
			default => throw new InvalidArgumentException( "Unknown visibility: {$visibility}." ),
		};
	}

	/**
	 * Determine if Flysystem exceptions should be thrown.
	 */
	protected function throws_exceptions(): bool {
		return (bool) ( $this->config['throw'] ?? false );
	}

	/**
	 * Pass dynamic methods call onto Flysystem instance.
	 *
	 * @param string $method Method to call.
	 * @param array  $parameters Parameters for the driver.
	 * @return mixed
	 */
	public function __call( $method, array $parameters ) {
		return $this->driver->{$method}( ...$parameters );
	}
}
