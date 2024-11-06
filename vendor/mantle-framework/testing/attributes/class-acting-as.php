<?php
/**
 * Acting_As class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;
use Mantle\Database\Model\User;
use WP_User;

/**
 * Acting As
 *
 * Used to authenticate a test suite/case as a specific user/role.
 */
#[Attribute]
class Acting_As {
	/**
	 * Constructor.
	 *
	 * @param User|WP_User|string|int|null $user User to act as.
	 */
	public function __construct( public User|WP_User|string|int|null $user ) {}
}
