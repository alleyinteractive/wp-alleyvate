<?php
/**
 * User_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Mantle\Database\Model\User;

use function Mantle\Support\Helpers\get_user_object;

/**
 * User Factory
 *
 * @template TModel of \Mantle\Database\Model\User
 * @template TObject of \WP_User
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class User_Factory extends Factory {
	use Concerns\With_Meta;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = User::class;

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'description' => $this->faker->sentence(),
			'role'        => 'subscriber',
			'user_email'  => $this->faker->email(),
			'user_login'  => $this->faker->userName(),
			'user_pass'   => 'password',
		];
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_User|\Mantle\Database\Model\User|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models ? $this->model::find( $object_id ) : get_user_object( $object_id );
	}
}
