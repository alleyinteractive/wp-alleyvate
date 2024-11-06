<?php
/**
 * Block_Assertions trait file
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Database\Model\Post;
use WP_Post;

use function Alley\WP\match_blocks;
use function Mantle\Support\Helpers\collect;

/**
 * Assorted Block assertions
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Block_Assertions {
	/**
	 * Assert that a string can match a block.
	 *
	 * The arguments are passed directly to `match_block()`.
	 *
	 * @see \Alley\WP\match_block()
	 *
	 * @param string $string The string to check.
	 * @param array  $args The arguments to pass to `match_block()`.
	 */
	public function assertStringMatchesBlock( string $string, array $args ): void {
		$this->assertNotEmpty(
			match_blocks( $string, $args ),
			'Failed asserting that string matches block with arguments ' . print_r( $args, true ),
		);
	}

	/**
	 * Assert that a string does not match a block.
	 *
	 * The arguments are passed directly to `match_block()`.
	 *
	 * @see \Alley\WP\match_block()
	 *
	 * @param string $string The string to check.
	 * @param array  $args The arguments to pass to `match_block()`.
	 */
	public function assertStringNotMatchesBlock( string $string, array $args ): void {
		$this->assertEmpty(
			match_blocks( $string, $args ),
			'Failed asserting that string does not match block with arguments ' . print_r( $args, true ),
		);
	}

	/**
	 * Assert that a string has a block.
	 *
	 * @param string          $string The string to check.
	 * @param string|string[] $block_name The block name(s) to check for. Will attempt to match any of the names.
	 * @param array           $attrs Optional. Attributes to check for.
	 */
	public function assertStringHasBlock( string $string, string|array $block_name, array $attrs = [] ): void {
		$this->assertNotEmpty(
			match_blocks(
				$string,
				[
					'attrs' => $this->convert_arguments_for_matching( $attrs ),
					'name'  => $block_name,
				],
			),
			! empty( $attrs )
				? "Failed asserting that string has block [{$block_name}] with attributes " . print_r( $attrs, true )
				: "Failed asserting that string has block [{$block_name}]."
		);
	}

	/**
	 * Assert that a string does not have a block.
	 *
	 * @param string          $string The string to check.
	 * @param string|string[] $block_name The block name(s) to check for. Will attempt to match any of the names.
	 * @param array           $attrs Optional. Attributes to check for.
	 */
	public function assertStringNotHasBlock( string $string, string|array $block_name, array $attrs = [] ): void {
		$this->assertEmpty(
			match_blocks(
				$string,
				[
					'attrs' => $this->convert_arguments_for_matching( $attrs ),
					'name'  => $block_name,
				],
			),
			! empty( $attrs )
				? "Failed asserting that string does not have block [{$block_name}] with attributes " . print_r( $attrs, true )
				: "Failed asserting that string does not have block [{$block_name}]",
		);
	}

	/**
	 * Assert that a post has a block in its content.
	 *
	 * @param Post|WP_Post    $post The post to check.
	 * @param string|string[] $block_name The block name(s) to check for. Will attempt to match any of the names.
	 * @param array           $attrs Optional. Attributes to check for.
	 */
	public function assertPostHasBlock( Post|WP_Post $post, string|array $block_name, array $attrs = [] ): void {
		$this->assertStringHasBlock( $post->post_content, $block_name, $attrs );
	}

	/**
	 * Assert that a post does not have a block in its content.
	 *
	 * @param Post|WP_Post    $post The post to check.
	 * @param string|string[] $block_name The block name(s) to check for. Will attempt to match any of the names.
	 * @param array           $attrs Optional. Attributes to check for.
	 */
	public function assertPostNotHasBlock( Post|WP_Post $post, string|array $block_name, array $attrs = [] ): void {
		$this->assertStringNotHasBlock( $post->post_content, $block_name, $attrs );
	}

	/**
	 * Convert the key/value arguments to an array that can be passed to
	 * `match_block()`.
	 *
	 * @param array $args The arguments to convert.
	 */
	protected function convert_arguments_for_matching( array $args ): array {
		// PHPCS is crashing on these lines for some reason. Disabling it for now
		// until we've upgrading to WPCS 3.0.

		/* phpcs:disable */
		return collect( $args )->reduce(
			function ( array $carry, $value, $key ) {
				// Allow for passing an argument pair directly.
				if ( is_array( $value ) && isset( $value['key'], $value['value'] )  ) {
					$carry[] = $value;

					return $carry;
				}

				$carry[] = [
					$key    => $value,
					'value' => $value,
				];

				return $carry;
			},
			[]
		);
		/* phpcs:enable */
	}
}
