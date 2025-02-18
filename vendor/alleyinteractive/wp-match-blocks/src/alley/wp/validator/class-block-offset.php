<?php
/**
 * Block_Offset class file
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-match-blocks
 */

namespace Alley\WP\Validator;

use Alley\Validator\Type;
use Laminas\Validator\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface;
use Traversable;
use WP_Block;
use WP_Block_Parser_Block;
use WP_Error;

/**
 * Validates whether the given block appears at an offset within a set of blocks.
 */
final class Block_Offset extends Block_Validator {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	public const NOT_AT_OFFSET = 'not_at_offset';

	/**
	 * Array of validation failure message templates.
	 *
	 * @var string[]
	 */
	protected $messageTemplates = [
		self::NOT_AT_OFFSET => '',
	];

	/**
	 * Array of additional variables available for validation failure messages.
	 *
	 * @var string[]
	 */
	protected $messageVariables = [
		'offset' => [
			'options' => 'offset',
		],
	];

	/**
	 * Options for this validator.
	 *
	 * @var array
	 */
	protected $options = [
		'blocks'            => [],
		'offset'            => 0,
		'skip_empty_blocks' => true,
	];

	/**
	 * Blocks that will be used in validation based on options.
	 *
	 * @var WP_Block_Parser_Block[]
	 */
	private array $final_blocks = [];

	/**
	 * Set up.
	 *
	 * @param array|Traversable $options Validator options.
	 */
	public function __construct( $options = null ) {
		$this->messageTemplates[ self::NOT_AT_OFFSET ] = sprintf(
			/* translators: %s: offset placeholder */
			__( 'Must be at offset %s within the blocks.', 'alley' ),
			'%offset%'
		);

		parent::__construct( $options );
	}

	/**
	 * Sets one or multiple options. Determines the final set of blocks.
	 *
	 * @param array|Traversable $options Options to set.
	 * @return self
	 */
	public function setOptions( $options = [] ) {
		parent::setOptions( $options );

		$this->final_blocks = $this->options['blocks'];

		if ( $this->options['skip_empty_blocks'] ) {
			$this->final_blocks = array_filter( $this->final_blocks, [ new Nonempty_Block(), 'isValid' ] );
		}

		return $this;
	}

	/**
	 * Apply block validation logic and add any validation errors.
	 *
	 * @param WP_Block_Parser_Block $block The block to test.
	 */
	protected function test_block( WP_Block_Parser_Block $block ): void {
		foreach ( (array) $this->options['offset'] as $offset ) {
			$check = [];

			/*
			 * Manually checking negative offsets is required because
			 * `array_slice()` will return the first item if the negative offset
			 * is out of bounds.
			 */
			if ( 0 <= $offset || abs( $offset ) <= \count( $this->final_blocks ) ) {
				$check = \array_slice( $this->final_blocks, $offset, 1 );
			}

			if ( isset( $check[0] ) && (array) $check[0] === (array) $block ) {
				return;
			}
		}

		$this->error( self::NOT_AT_OFFSET );
	}

	/**
	 * Sets the 'blocks' option.
	 *
	 * @throws InvalidArgumentException If blocks aren't iterable.
	 *
	 * @param array[]|WP_Block[]|WP_Block_Parser_Block[] $blocks Blocks.
	 */
	protected function setBlocks( $blocks ) {
		if ( ! is_iterable( $blocks ) ) {
			throw new InvalidArgumentException( 'Blocks must be iterable.' );
		}

		if ( ! $blocks instanceof \Traversable ) {
			$blocks = new \ArrayIterator( (array) $blocks );
		}

		$blocks = iterator_to_array( $blocks );
		$blocks = array_map( [ self::class, 'to_parser_block' ], $blocks );

		$this->options['blocks'] = array_values( $blocks );
	}

	/**
	 * Sets the 'offset' option.
	 *
	 * @param int|int[] $offset Offset or offsets.
	 */
	protected function setOffset( $offset ) {
		$offset = \is_array( $offset ) ? array_map( 'intval', $offset ) : (int) $offset;

		$this->options['offset'] = $offset;
	}

	/**
	 * Sets the 'skip_empty_blocks' option.
	 *
	 * @param bool $skip Option.
	 */
	protected function setSkipEmptyBlocks( $skip ) {
		$this->options['skip_empty_blocks'] = (bool) $skip;
	}
}
