<?php
/**
 * Variable_Post_Queries class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Queries;

use Alley\WP\Types\Post_Queries;
use Alley\WP\Types\Post_Query;
use Laminas\Validator\ValidatorInterface;

/**
 * Choose queries based on the result of a validation test.
 */
final class Variable_Post_Queries implements Post_Queries {
	/**
	 * Set up.
	 *
	 * @param callable           $input    Test input.
	 * @param ValidatorInterface $test     Validation test.
	 * @param Post_Queries       $is_true  Post_Queries if the test passes.
	 * @param Post_Queries       $is_false Post_Queries if the test fails.
	 */
	public function __construct(
		private $input,
		private readonly ValidatorInterface $test,
		private readonly Post_Queries $is_true,
		private readonly Post_Queries $is_false,
	) {}

	/**
	 * Query for posts using literal arguments.
	 *
	 * @param array<string, mixed> $args The arguments to be used in the query.
	 * @return Post_Query
	 */
	public function query( array $args ): Post_Query {
		return $this->final()->query( $args );
	}

	/**
	 * Post_Queries instance to use.
	 *
	 * @return Post_Queries
	 */
	private function final(): Post_Queries {
		return $this->test->isValid( ( $this->input )() ) ? $this->is_true : $this->is_false;
	}
}
