<?php
/**
 * AWS_S3_Adapter class file
 *
 * @package Mantle
 */

namespace Mantle\Filesystem\Adapter;

use Aws\S3\S3Client;
use DateTimeInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\FilesystemOperator;
use Mantle\Filesystem\Filesystem_Adapter;

/**
 * AWS S3 Adapter
 */
class AWS_S3_Adapter extends Filesystem_Adapter {
	/**
	 * The AWS S3 client.
	 *
	 * @var \Aws\S3\S3Client
	 */
	protected $client;

	/**
	 * Create a new AwsS3V3FilesystemAdapter instance.
	 *
	 * @param  \League\Flysystem\FilesystemOperator     $driver
	 * @param  \League\Flysystem\AwsS3V3\AwsS3V3Adapter $adapter
	 * @param  array                                    $config
	 * @param  \Aws\S3\S3Client                         $client
	 * @return void
	 */
	public function __construct( FilesystemOperator $driver, S3Adapter $adapter, array $config, S3Client $client ) {
		parent::__construct( $driver, $adapter, $config );

		$this->client = $client;
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param  string $path
	 */
	public function url( string $path ): string {
		// If an explicit base URL has been set on the disk configuration then we will use
		// it as the base URL instead of the default path. This allows the developer to
		// have full control over the base path for this filesystem's generated URLs.
		if ( isset( $this->config['url'] ) ) {
			return $this->concat_path_to_url( $this->config['url'], $this->prefixer->prefixPath( $path ) );
		}

		return $this->client->getObjectUrl(
			$this->config['bucket'],
			$this->prefixer->prefixPath( $path )
		);
	}

	/**
	 * Determine if temporary URLs can be generated.
	 */
	public function provides_temporary_urls(): bool {
		return true;
	}

	/**
	 * Get a temporary URL for the file at the given path.
	 *
	 * @param  string             $path
	 * @param  \DateTimeInterface $expiration
	 * @param  array              $options
	 */
	public function temporary_url( string $path, $expiration, array $options = [] ): string {
		$command = $this->client->getCommand(
			'GetObject',
			array_merge(
				[
					'Bucket' => $this->config['bucket'],
					'Key'    => $this->prefixer->prefixPath( $path ),
				],
				$options
			)
		);

		$uri = $this->client->createPresignedRequest(
			$command,
			$expiration,
			$options
		)->getUri();

		// If an explicit base URL has been set on the disk configuration then we will use
		// it as the base URL instead of the default path. This allows the developer to
		// have full control over the base path for this filesystem's generated URLs.
		if ( isset( $this->config['temporary_url'] ) ) {
			$uri = $this->replace_base_url( $uri, $this->config['temporary_url'] );
		}

		return (string) $uri;
	}

	/**
	 * Get a temporary upload URL for the file at the given path.
	 *
	 * @param  string             $path
	 * @param  \DateTimeInterface $expiration
	 * @param  array              $options
	 */
	public function temporary_upload_url( string $path, $expiration, array $options = [] ): array {
		$command = $this->client->getCommand(
			'PutObject',
			array_merge(
				[
					'Bucket' => $this->config['bucket'],
					'Key'    => $this->prefixer->prefixPath( $path ),
				],
				$options
			)
		);

		$signed_request = $this->client->createPresignedRequest(
			$command,
			$expiration,
			$options
		);

		$uri = $signed_request->getUri();

		// If an explicit base URL has been set on the disk configuration then we will use
		// it as the base URL instead of the default path. This allows the developer to
		// have full control over the base path for this filesystem's generated URLs.
		if ( isset( $this->config['temporary_url'] ) ) {
			$uri = $this->replace_base_url( $uri, $this->config['temporary_url'] );
		}

		return [
			'url'     => (string) $uri,
			'headers' => $signed_request->getHeaders(),
		];
	}

	/**
	 * Get the underlying S3 client.
	 */
	public function get_client(): S3Client {
		return $this->client;
	}
}
