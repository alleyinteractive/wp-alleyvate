<?php
/**
 * Filesystem class file.
 *
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions
 *
 * @package Mantle
 */

namespace Mantle\Filesystem;

use ErrorException;
use FilesystemIterator;
use Mantle\Support\Str;
use Mantle\Support\Traits\Macroable;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Mime\MimeTypes;

/**
 * Filesystem Interface
 */
class Filesystem {
	use Macroable;

	/**
	 * Determine if a file or directory exists.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function exists( $path ) {
		return file_exists( $path );
	}

	/**
	 * Determine if a file or directory is missing.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function missing( $path ) {
		return ! $this->exists( $path );
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param  string $path
	 * @param  bool   $lock
	 *
	 * @throws File_Not_Found_Exception Thrown on missing file.
	 */
	public function get( string $path, bool $lock = false ): string {
		if ( $this->is_file( $path ) ) {
			return $lock ? $this->shared_get( $path ) : file_get_contents( $path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		}

		throw new File_Not_Found_Exception( "File does not exist at path {$path}." );
	}

	/**
	 * Get contents of a file with shared access.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function shared_get( $path ) {
		$contents = '';

		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( $handle ) {
			try {
				if ( flock( $handle, LOCK_SH ) ) {
					clearstatcache( true, $path );

					$contents = fread( $handle, $this->size( $path ) ?: 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread

					flock( $handle, LOCK_UN );
				}
			} finally {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}

		return $contents;
	}

	/**
	 * Get the returned value of a file.
	 *
	 * @param  string $path
	 * @param  array  $data
	 * @return mixed
	 *
	 * @throws File_Not_Found_Exception Thrown on missing file.
	 */
	public function get_require( $path, array $data = [] ) {
		if ( $this->is_file( $path ) ) {
			$__path = $path;
			$__data = $data;

			return ( static function () use ( $__path, $__data ) {
				extract( $__data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract, WordPress.PHP.DiscouragedPHPFunctions.extract_extract

				return require $__path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			} )();
		}

		throw new File_Not_Found_Exception( "File does not exist at path {$path}." );
	}

	/**
	 * Require the given file once.
	 *
	 * @param  string $path
	 * @param  array  $data
	 * @return mixed
	 *
	 * @throws File_Not_Found_Exception Thrown on missing file.
	 */
	public function require_once( $path, array $data = [] ) {
		if ( $this->is_file( $path ) ) {
			$__path = $path;
			$__data = $data;

			return ( static function () use ( $__path, $__data ) {
				extract( $__data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract, WordPress.PHP.DiscouragedPHPFunctions.extract_extract

				return require_once $__path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			} )();
		}

		throw new File_Not_Found_Exception( "File does not exist at path {$path}." );
	}

	/**
	 * Get the MD5 hash of the file at the given path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function hash( $path ) {
		return md5_file( $path );
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string $path
	 * @param  string $contents
	 * @param  bool   $lock
	 * @return int|bool
	 */
	public function put( $path, $contents, $lock = false ) {
		return file_put_contents( $path, $contents, $lock ? LOCK_EX : 0 );
	}

	/**
	 * Write the contents of a file, replacing it atomically if it already exists.
	 *
	 * @param  string $path
	 * @param  string $content
	 */
	public function replace( $path, $content ): void {
		// If the path already exists and is a symlink, get the real path...
		clearstatcache( true, $path );

		$path = realpath( $path ) ?: $path;

		$temp_path = tempnam( dirname( $path ), basename( $path ) );

		// Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
		chmod( $temp_path, 0777 - umask() );

		file_put_contents( $temp_path, $content );

		rename( $temp_path, $path );
	}

	/**
	 * Prepend to a file.
	 *
	 * @param  string $path
	 * @param  string $data
	 * @return int
	 */
	public function prepend( $path, $data ) {
		if ( $this->exists( $path ) ) {
			return $this->put( $path, $data . $this->get( $path ) );
		}

		return $this->put( $path, $data );
	}

	/**
	 * Append to a file.
	 *
	 * @param  string $path
	 * @param  string $data
	 * @return int
	 */
	public function append( $path, $data ) {
		return file_put_contents( $path, $data, FILE_APPEND );
	}

	/**
	 * Get or set UNIX mode of a file or directory.
	 *
	 * @param  string   $path
	 * @param  int|null $mode
	 * @return mixed
	 */
	public function chmod( $path, $mode = null ) {
		if ( $mode ) {
			return chmod( $path, $mode );
		}

		return substr( sprintf( '%o', fileperms( $path ) ), -4 );
	}

	/**
	 * Delete the file at a given path.
	 *
	 * @param  string|array $paths
	 * @return bool
	 */
	public function delete( $paths ) {
		$paths = is_array( $paths ) ? $paths : func_get_args();

		$success = true;

		foreach ( $paths as $path ) {
			try {
				if ( ! @unlink( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden
					$success = false;
				}
			} catch ( ErrorException ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Move a file to a new location.
	 *
	 * @param  string $path
	 * @param  string $target
	 * @return bool
	 */
	public function move( $path, $target ) {
		return rename( $path, $target );
	}

	/**
	 * Copy a file to a new location.
	 *
	 * @param  string $path
	 * @param  string $target
	 * @return bool
	 */
	public function copy( $path, $target ) {
		return copy( $path, $target );
	}

	/**
	 * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
	 *
	 * @param  string $target
	 * @param  string $link
	 */
	public function link( $target, $link ): void {
		$mode = $this->is_directory( $target ) ? 'J' : 'H';

		exec( "mklink /{$mode} " . escapeshellarg( $link ) . ' ' . escapeshellarg( $target ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	}

	/**
	 * Extract the file name from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function name( $path ) {
		return pathinfo( $path, PATHINFO_FILENAME );
	}

	/**
	 * Extract the trailing name component from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function basename( $path ) {
		return pathinfo( $path, PATHINFO_BASENAME );
	}

	/**
	 * Extract the parent directory from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function dirname( $path ) {
		return pathinfo( $path, PATHINFO_DIRNAME );
	}

	/**
	 * Extract the file extension from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function extension( $path ) {
		return pathinfo( $path, PATHINFO_EXTENSION );
	}

	/**
	 * Guess the file extension from the mime-type of a given file.
	 *
	 * @param  string $path
	 * @return string|null
	 *
	 * @throws RuntimeException Thrown on missing extension.
	 */
	public function guess_extension( $path ) {
		if ( ! class_exists( MimeTypes::class ) ) {
			throw new RuntimeException(
				'To enable support for guessing extensions, please install the symfony/mime package.'
			);
		}

		return ( new MimeTypes() )->getExtensions( $this->mime_type( $path ) )[0] ?? null;
	}

	/**
	 * Guess the class name for a file path.
	 *
	 * @param string $path File path.
	 */
	public function guess_class_name( string $path ): ?string {
		$name = $this->name( $path );

		if ( Str::starts_with( $name, [ 'class-', 'trait-', 'interface-' ] ) ) {
			$name = preg_replace( '/^(\w*-)/', '', $name, 1 );

			return Str::studly_underscore( $name );
		}

		return $name;
	}

	/**
	 * Get the file type of a given file.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function type( $path ) {
		return filetype( $path );
	}

	/**
	 * Get the mime-type of a given file.
	 *
	 * @param  string $path
	 * @return string|false
	 */
	public function mime_type( $path ) {
		return finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $path );
	}

	/**
	 * Get the file size of a given file.
	 *
	 * @param  string $path
	 * @return int
	 */
	public function size( $path ) {
		return filesize( $path );
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @param  string $path
	 * @return int
	 */
	public function last_modified( $path ) {
		return filemtime( $path );
	}

	/**
	 * Determine if the given path is a directory.
	 *
	 * @param  string $directory
	 * @return bool
	 */
	public function is_directory( $directory ) {
		return is_dir( $directory );
	}

	/**
	 * Determine if the given path is readable.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function is_readable( $path ) {
		return is_readable( $path );
	}

	/**
	 * Determine if the given path is writable.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function is_writable( $path ) {
		return is_writable( $path );
	}

	/**
	 * Determine if the given path is a file.
	 *
	 * @param  string $file
	 * @return bool
	 */
	public function is_file( $file ) {
		return is_file( $file );
	}

	/**
	 * Find path names matching a given pattern.
	 *
	 * @param  string $pattern
	 * @param  int    $flags
	 * @return array
	 */
	public function glob( $pattern, $flags = 0 ) {
		return glob( $pattern, $flags );
	}

	/**
	 * Get an array of all files in a directory.
	 *
	 * @param  string $directory
	 * @param  bool   $hidden
	 * @return \Symfony\Component\Finder\SplFileInfo[]
	 */
	public function files( $directory, $hidden = false ) {
		return iterator_to_array(
			Finder::create()->files()->ignoreDotFiles( ! $hidden )->in( $directory )->depth( 0 )->sortByName(),
			false
		);
	}

	/**
	 * Get all of the files from the given directory (recursive).
	 *
	 * @param  string $directory
	 * @param  bool   $hidden
	 * @return \Symfony\Component\Finder\SplFileInfo[]
	 */
	public function all_files( $directory, $hidden = false ) {
		return iterator_to_array(
			Finder::create()->files()->ignoreDotFiles( ! $hidden )->in( $directory )->sortByName(),
			false
		);
	}

	/**
	 * Get all of the directories within a given directory.
	 *
	 * @param  string $directory
	 */
	public function directories( $directory ): array {
		$directories = [];

		foreach ( Finder::create()->in( $directory )->directories()->depth( 0 )->sortByName() as $dir ) {
			$directories[] = $dir->getPathname();
		}

		return $directories;
	}

	/**
	 * Ensure a directory exists.
	 *
	 * @param  string $path
	 * @param  int    $mode
	 * @param  bool   $recursive
	 */
	public function ensure_directory_exists( $path, $mode = 0755, $recursive = true ): void {
		if ( ! $this->is_directory( $path ) ) {
			$this->make_directory( $path, $mode, $recursive );
		}
	}

	/**
	 * Create a directory.
	 *
	 * @param  string $path
	 * @param  int    $mode
	 * @param  bool   $recursive
	 * @return bool
	 */
	public function make_directory( $path, $mode = 0755, $recursive = false ) {
		return mkdir( $path, $mode, $recursive );
	}

	/**
	 * Move a directory.
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  bool   $overwrite
	 */
	public function move_directory( $from, $to, $overwrite = false ): bool {
		if ( $overwrite && $this->is_directory( $to ) && ! $this->delete_directory( $to ) ) {
			return false;
		}

		return @rename( $from, $to ) === true; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden
	}

	/**
	 * Copy a directory from one location to another.
	 *
	 * @param  string   $directory
	 * @param  string   $destination
	 * @param  int|null $options
	 */
	public function copy_directory( $directory, $destination, $options = null ): bool {
		if ( ! $this->is_directory( $directory ) ) {
			return false;
		}

		$options = $options ?: FilesystemIterator::SKIP_DOTS;

		// If the destination directory does not actually exist, we will go ahead and
		// create it recursively, which just gets the destination prepared to copy
		// the files over. Once we make the directory we'll proceed the copying.
		$this->ensure_directory_exists( $destination, 0777 );

		$items = new FilesystemIterator( $directory, $options );

		foreach ( $items as $item ) {
			// As we spin through items, we will check to see if the current file is actually
			// a directory or a file. When it is actually a directory we will need to call
			// back into this function recursively to keep copying these nested folders.
			$target = $destination . '/' . $item->getBasename();

			if ( $item->isDir() ) {
				$path = $item->getPathname();

				if ( ! $this->copy_directory( $path, $target, $options ) ) {
					return false;
				}
			} elseif ( ! $this->copy( $item->getPathname(), $target ) ) {
				// If the current items is just a regular file, we will just copy this to the new
				// location and keep looping. If for some reason the copy fails we'll bail out
				// and return false, so the developer is aware that the copy process failed.
				return false;
			}
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * The directory itself may be optionally preserved.
	 *
	 * @param  string $directory
	 * @param  bool   $preserve
	 */
	public function delete_directory( $directory, $preserve = false ): bool {
		if ( ! $this->is_directory( $directory ) ) {
			return false;
		}

		$items = new FilesystemIterator( $directory );

		foreach ( $items as $item ) {
			// If the item is a directory, we can just recurse into the function and
			// delete that sub-directory otherwise we'll just delete the file and
			// keep iterating through each file until the directory is cleaned.
			if ( $item->isDir() && ! $item->isLink() ) {
				$this->delete_directory( $item->getPathname() );
			} else {

				// If the item is just a file, we can go ahead and delete it since we're
				// just looping through and waxing all of the files in this directory
				// and calling directories recursively, so we delete the real path.
				$this->delete( $item->getPathname() );
			}
		}

		if ( ! $preserve ) {
			@rmdir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden
		}

		return true;
	}

	/**
	 * Remove all of the directories within a given directory.
	 *
	 * @param  string $directory
	 */
	public function delete_directories( $directory ): bool {
		$all_directories = $this->directories( $directory );

		if ( ! empty( $all_directories ) ) {
			foreach ( $all_directories as $all_directory ) {
				$this->delete_directory( $all_directory );
			}

			return true;
		}

		return false;
	}

	/**
	 * Empty the specified directory of all files and folders.
	 *
	 * @param  string $directory
	 * @return bool
	 */
	public function clean_directory( $directory ) {
		return $this->delete_directory( $directory, true );
	}
}
