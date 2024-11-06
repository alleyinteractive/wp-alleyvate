<?php
/**
 * Seed_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Console;

use Mantle\Console\Command;
use Mantle\Console\Confirmable;

use function Mantle\Support\Helpers\remove_action_validated;

/**
 * Database Seed Command
 */
class Seed_Command extends Command {
	use Confirmable;

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = 'db:seed {--class=}';

	/**
	 * Run Database Seeding
	 */
	public function handle(): void {
		if ( ! $this->confirm_to_proceed() ) {
			return;
		}

		// Disable cache purging.
		if ( class_exists( 'WPCOM_VIP_Cache_Manager' ) ) {
			remove_action_validated( 'shutdown', [ \WPCOM_VIP_Cache_Manager::instance(), 'execute_purges' ] );
		}

		$this->container
			->make( $this->option( 'class', \App\Database\Seeds\Database_Seeder::class ) )
			->set_container( $this->container )
			->set_command( $this )
			->__invoke();

		$this->success( __( 'Database seeding completed.', 'mantle' ) );
	}
}
