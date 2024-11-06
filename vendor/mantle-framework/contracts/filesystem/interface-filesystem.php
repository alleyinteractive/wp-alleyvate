<?php
/**
 * Filesystem interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Filesystem;

/**
 * Filesystem Contract
 */
interface Filesystem {
	/**
	 * The public visibility setting.
	 *
	 * @var string
	 */
	public const VISIBILITY_PUBLIC = 'public';

	/**
	 * The private visibility setting.
	 *
	 * @var string
	 */
	public const VISIBILITY_PRIVATE = 'private';

	/**
	 * Get all (recursive) of the directories within a given directory.
	 *
	 * @param  string $directory Directory name.
	 * @return string[]
	 */
	public function all_directories( string $directory = null ): array;

	/**
	 * Get all the directories within a given directory.
	 *
	 * @param string $directory Directory name.
	 * @param bool   $recursive Flag if it should be recursive.
	 */
	public function directories( string $directory = null, bool $recursive = false ): array;

	/**
	 * Create a directory.
	 *
	 * @param string $path Path to create.
	 */
	public function make_directory( string $path ): bool;

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory name.
	 */
	public function delete_directory( string $directory ): bool;

	/**
	 * Get all of the files from the given directory (recursive).
	 *
	 * @param string $directory Directory name.
	 * @return string[]
	 */
	public function all_files( string $directory = null ): array;

	/**
	 * Get an array of all files in a directory.
	 *
	 * @param string $directory Directory name.
	 * @param bool   $recursive Flag if recursive.
	 * @return string[]
	 */
	public function files( string $directory = null, bool $recursive = false ): array;

	/**
	 * Copy a file from one location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 */
	public function copy( string $from, string $to ): bool;

	/**
	 * Move a file from a location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 */
	public function move( string $from, string $to ): bool;

	/**
	 * Delete a file at the given paths.
	 *
	 * @param string|string[] $paths File paths.
	 */
	public function delete( $paths ): bool;

	/**
	 * Check if a file exists at a current path.
	 *
	 * @param string $path
	 */
	public function exists( string $path ): bool;

	/**
	 * Check if a file is missing at a given path.
	 *
	 * @param string $path File path.
	 */
	public function missing( string $path ): bool;

	/**
	 * Get the contents of a file.
	 *
	 * @param string $path File path.
	 * @return string|bool
	 */
	public function get( string $path );

	/**
	 * Get the file's last modification time.
	 *
	 * @param string $path File path.
	 * @return int|bool
	 */
	public function last_modified( string $path );

	/**
	 * Write the contents of a file.
	 *
	 * @param string          $path File path.
	 * @param string|resource $contents File contents.
	 * @param array|string    $options  Options for the files or a string visibility.
	 */
	public function put( string $path, $contents, $options = [] ): bool;

	/**
	 * Retrieve the size of the file.
	 *
	 * @param string $path File path.
	 * @return int|bool
	 */
	public function size( string $path );

	/**
	 * Read a file through a stream.
	 *
	 * @param string $path File path.
	 * @return resource|false The path resource or false on failure.
	 */
	public function read_stream( string $path );

	/**
	 * Write a file through a stream.
	 *
	 * @param string       $path File path.
	 * @param resource     $resource File resource.
	 * @param array|string $options File options or string visibility.
	 */
	public function write_stream( string $path, $resource, $options = [] ): bool;

	/**
	 * Prepend to a file.
	 *
	 * @param string $path File to prepend.
	 * @param string $data Data to prepend.
	 * @param string $separator Separator from existing data.
	 * @return bool
	 */
	public function prepend( string $path, string $data, string $separator = PHP_EOL );

	/**
	 * Append to a file.
	 *
	 * @param string $path File to append.
	 * @param string $data Data to append.
	 * @param string $separator Separator from existing data.
	 * @return bool
	 */
	public function append( $path, $data, $separator = PHP_EOL );

	/**
	 * Retrieve a file's visibility.
	 *
	 * @param string $path
	 */
	public function get_visibility( string $path ): string;

	/**
	 * Set the visibility for a file.
	 *
	 * @param string $path Path to set.
	 * @param string $visibility Visibility to set.
	 */
	public function set_visibility( string $path, string $visibility ): bool;

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param string $path Path to the file.
	 */
	public function url( string $path ): ?string;

	/**
	 * Get a temporary URL for the file at the given path.
	 *
	 * @param  string             $path File path.
	 * @param  \DateTimeInterface $expiration File expiration.
	 * @param  array              $options Options for the URL.
	 *
	 * @throws \RuntimeException Thrown on missing temporary URL.
	 */
	public function temporary_url( string $path, $expiration, array $options = [] ): string;
}
