<?php
/**
 * Seeder class file.
 *
 * @package Mantle
 */

namespace Mantle\Database;

use InvalidArgumentException;
use Mantle\Console\Command;
use Mantle\Contracts\Container;
use Mantle\Support\Arr;

/**
 * Base Database Seeder
 */
abstract class Seeder {
	/**
	 * The container instance.
	 *
	 * @var Container|null
	 */
	protected $container;

	/**
	 * The console command instance.
	 *
	 * @var Command|null
	 */
	protected $command;

	/**
	 * Seed the given connection from the given path.
	 *
	 * @param array<class-string>|string $class Seed to run.
	 * @param bool                       $silent Flag if the seed should be silent.
	 * @param array                      $parameters Parameters to pass to the seeder.
	 */
	public function call( $class, bool $silent = false, array $parameters = [] ): static {
		$classes = Arr::wrap( $class );

		foreach ( $classes as $class ) {
			$seeder = $this->resolve( $class );
			$name   = $seeder::class;

			if ( ! $silent && isset( $this->command ) ) {
				$this->command->line( "Seeding: {$name}" );
			}

			$start_time = microtime( true );

			$seeder( $parameters );

			$run_time = number_format( ( microtime( true ) - $start_time ) * 1000, 2 );

			if ( ! $silent && isset( $this->command ) ) {
				$this->command->line( "Seeded: {$name} ({$run_time} seconds)" );
			}
		}

		return $this;
	}

	/**
	 * Run the given seeder class with the given arguments.
	 *
	 * @param array<class-string>|string $class Seed to run.
	 * @param array                      $parameters Parameters to pass to the seeder.
	 */
	public function call_with( $class, array $parameters = [] ): static {
		return $this->call( $class, false, $parameters );
	}

	/**
	 * Resolve an instance of the given seeder class.
	 *
	 * @throws InvalidArgumentException If the class is not an instance of Seeder.
	 *
	 * @param  class-string $class Seeder class to resolve.
	 */
	protected function resolve( string $class ): Seeder {
		if ( isset( $this->container ) ) {
			$instance = $this->container->make( $class );

			$instance->set_container( $this->container );
		} else {
			$instance = new $class();
		}

		if ( ! $instance instanceof Seeder ) {
			throw new InvalidArgumentException( "Class [{$class}] must be an instance of " . self::class );
		}

		return $instance;
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param Container $container IoC container.
	 */
	public function set_container( Container $container ): static {
		$this->container = $container;

		return $this;
	}

	/**
	 * Set the command instance instance.
	 *
	 * @param Command $command
	 */
	public function set_command( Command $command ): static {
		$this->command = $command;

		return $this;
	}

	/**
	 * Run the database seeds.
	 *
	 * @param array $parameters Parameters to pass to the seeder.
	 *
	 * @throws InvalidArgumentException Thrown on bad seeder class.
	 */
	public function __invoke( array $parameters = [] ): mixed {
		if ( ! method_exists( $this, 'run' ) ) {
			throw new InvalidArgumentException( 'Method [run] missing from ' . static::class );
		}

		return isset( $this->container )
			? $this->container->call( [ $this, 'run' ], $parameters )
			: $this->run( $parameters );
	}
}
