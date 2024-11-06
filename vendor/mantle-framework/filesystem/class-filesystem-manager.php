<?php
/**
 * Filesystem_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Filesystem;

use Aws\S3\S3Client;
use Closure;
use InvalidArgumentException;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Mantle\Contracts\Application;
use Mantle\Contracts\Filesystem\Filesystem_Manager as Filesystem_Manager_Contract;
use Mantle\Contracts\Filesystem\Filesystem;
use Mantle\Support\Arr;
use RuntimeException;

/**
 * Filesystem Manager
 *
 * @mixin \Mantle\Contracts\Filesystem\Filesystem
 */
class Filesystem_Manager implements Filesystem_Manager_Contract {
	/**
	 * Disk storage.
	 *
	 * @var Filesystem[]
	 */
	protected $disks = [];

	/**
	 * Storage of custom drivers for the filesystem.
	 *
	 * @var Closure[]
	 */
	protected $custom_drivers;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( protected Application $app ) {}

	/**
	 * Retrieve a filesystem disk.
	 *
	 * @param string $name Disk name.
	 *
	 * @throws InvalidArgumentException Thrown on invalid disk configuration.
	 */
	public function drive( string $name = null ): Filesystem {
		return $this->resolve_disk( $name ?: $this->get_default_disk() );
	}

	/**
	 * Retrieve a disk by name.
	 *
	 * @param string $name Disk name.
	 * @return Filesystem
	 * @throws InvalidArgumentException Thrown on invalid disk/driver configuration.
	 */
	protected function resolve_disk( string $name ): Filesystem {
		if ( isset( $this->disks[ $name ] ) ) {
			return $this->disks[ $name ];
		}

		$config = $this->get_config( $name );

		if ( empty( $config['driver'] ) ) {
			throw new InvalidArgumentException( "Disk [{$name}] does not have a configured driver." );
		}

		$driver = $config['driver'];

		// Call a custom driver callback.
		if ( isset( $this->custom_drivers[ $driver ] ) ) {
			return $this->disks[ $name ] = $this->call_custom_driver( $driver, $config );
		}

		$driver_method = 'create_' . strtolower( (string) $driver ) . '_driver';

		if ( ! method_exists( $this, $driver_method ) ) {
			throw new InvalidArgumentException( "Disk [{$name}] uses a driver [{$driver}] that is not supported." );
		}

		return $this->disks[ $name ] = $this->{$driver_method}( $config );
	}

	/**
	 * Retrieve configuration for a specific filesystem disk.
	 *
	 * @param string $disk Disk name.
	 */
	protected function get_config( string $disk ): array {
		return (array) ( $this->app['config'][ "filesystem.disks.{$disk}" ] ?? [] );
	}

	/**
	 * Retrieve the default disk driver.
	 */
	protected function get_default_disk(): string {
		return (string) ( $this->app['config']['filesystem.default'] ?? 'local' );
	}

	/**
	 * Add a custom driver to the filesystem.
	 *
	 * @param string                                                                                  $driver Driver name.
	 * @param \Closure(\Mantle\Contracts\Application, array): \Mantle\Contracts\Filesystem\Filesystem $callback Callback to create the driver.
	 * @return static
	 */
	public function extend( string $driver, Closure $callback ) {
		$this->custom_drivers[ $driver ] = $callback;

		return $this;
	}

	/**
	 * Call a custom driver.
	 *
	 * @param string $driver Driver name.
	 * @param array  $config Configuration from disk.
	 * @return Filesystem
	 */
	protected function call_custom_driver( string $driver, array $config ): Filesystem {
		return $this->custom_drivers[ $driver ]( $this->app, $config );
	}

	/**
	 * Create a Flysystem instance with the given adapter.
	 *
	 * @param FilesystemAdapter $adapter
	 * @param array             $config Adapter configuration.
	 */
	protected function create_flysystem( FilesystemAdapter $adapter, array $config = [] ): Flysystem {
		return new Flysystem( $adapter, $config );
	}

	/**
	 * Create an instance of the local driver.
	 *
	 * @param  array $config Configuration.
	 *
	 * @throws InvalidArgumentException Thrown on missing WordPress.
	 */
	public function create_local_driver( array $config ): Filesystem_Adapter {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			throw new InvalidArgumentException( 'The local filesystem cannot be used outside of a WordPress environment.' );
		}

		$visibility = PortableVisibilityConverter::fromArray(
			$config['permissions'] ?? [],
			$config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
		);

		$links = ( $config['links'] ?? null ) === 'skip'
			? LocalAdapter::SKIP_LINKS
			: LocalAdapter::DISALLOW_LINKS;

		$upload_dir = wp_upload_dir();

		// Default the root to the WordPress uploads directory.
		$root = (string) ( $config['root'] ?? $upload_dir['basedir'] );

		/**
		 * Filter the local filesystem root directory.
		 *
		 * @param string $root Root path.
		 * @param array  $config Configuration.
		 */
		$root = (string) apply_filters( 'mantle_filesystem_local_root', $root, $config );

		// Ensure the root configuration has a base URL.
		$config['root']         = $root;
		$config['url']        ??= $upload_dir['baseurl'];
		$config['visibility'] ??= Visibility::PUBLIC;

		/**
		 * Filter the local filesystem configuration.
		 *
		 * @param array $config Configuration.
		 */
		$config = (array) apply_filters( 'mantle_filesystem_local_config', $config );

		$adapter = new LocalAdapter(
			$root,
			$visibility,
			$config['lock'] ?? LOCK_EX,
			$links
		);

		return new Adapter\Local_Adapter( $this->create_flysystem( $adapter, $config ), $adapter, $config );
	}

	/**
	 * Create an instance of the Amazon S3 driver.
	 *
	 * @param array $config S3 configuration.
	 *
	 * @throws RuntimeException Thrown on missing dependency.
	 */
	public function create_s3_driver( array $config ): Adapter\AWS_S3_Adapter {
		if ( ! class_exists( S3Adapter::class ) ) {
			throw new RuntimeException( S3Adapter::class . ' class not found. Run `composer require league/flysystem-aws-s3-v3`.' );
		}

		$s3_config = $this->format_s3_config( $config );

		$root = (string) ( $s3_config['root'] ?? '' );

		$visibility = new AwsS3PortableVisibilityConverter(
			$config['visibility'] ?? Visibility::PUBLIC
		);

		$stream_reads = $s3_config['stream_reads'] ?? false;

		$client = new S3Client( $s3_config );

		$adapter = new S3Adapter( $client, $s3_config['bucket'], $root, $visibility, null, $config['options'] ?? [], $stream_reads );

		return new Adapter\AWS_S3_Adapter(
			$this->create_flysystem( $adapter, $config ),
			$adapter,
			$s3_config,
			$client
		);
	}

	/**
	 * Format the given S3 configuration with the default options.
	 *
	 * @param  array $config
	 * @return array
	 */
	protected function format_s3_config( array $config ) {
		$config += [ 'version' => 'latest' ];

		if ( ! empty( $config['key'] ) && ! empty( $config['secret'] ) ) {
			$config['credentials'] = Arr::only( $config, [ 'key', 'secret', 'token' ] );
		}

		return $config;
	}

	/**
	 * Pass the method calls to the default disk.
	 *
	 * @param string $method Method to invoke.
	 * @param array  $arguments Arguments for the method.
	 * @return mixed
	 */
	public function __call( string $method, array $arguments ) {
		return $this->drive()->$method( ...$arguments );
	}
}
