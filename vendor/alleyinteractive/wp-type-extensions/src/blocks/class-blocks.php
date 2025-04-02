<?php
/**
 * Blocks class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Serialized_Blocks;

/**
 * Bundle many blocks.
 */
final class Blocks implements Serialized_Blocks {
	/**
	 * Collected blocks.
	 *
	 * @var Serialized_Blocks[]
	 */
	private readonly array $blocks;

	/**
	 * Set up.
	 *
	 * @param Serialized_Blocks ...$blocks Blocks.
	 */
	public function __construct( Serialized_Blocks ...$blocks ) {
		$this->blocks = $blocks;
	}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		return array_reduce(
			$this->blocks,
			fn ( string $carry, Serialized_Blocks $block ) => $carry .= $block->serialized_blocks(),
			'',
		);
	}

	/**
	 * Constructor for creating blocks from a set of values.
	 *
	 * @phpstan-param iterable<mixed> $values
	 * @phpstan-param callable(Serialized_Blocks[] $carry, mixed $item, mixed $index, iterable<mixed> $values): Serialized_Blocks[] $reduce
	 *
	 * @param iterable $values Values.
	 * @param callable $reduce Reducer callback that produces block instances.
	 * @return Serialized_Blocks
	 */
	public static function from_iterable( iterable $values, callable $reduce ): Serialized_Blocks {
		return new Lazy_Blocks(
			function () use ( $values, $reduce ) {
				$carry = [];

				foreach ( $values as $index => $item ) {
					$carry = ( $reduce )( $carry, $item, $index, $values );
				}

				return new Blocks( ...$carry );
			}
		);
	}
}
