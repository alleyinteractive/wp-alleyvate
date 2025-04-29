<?php
/**
 * Block_Validator class file
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-match-blocks
 */

namespace Alley\WP\Validator;

use Alley\Validator\ExtendedAbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use WP_Block;
use WP_Block_Parser_Block;

/**
 * Abstract class for validating WordPress blocks.
 */
abstract class Block_Validator extends ExtendedAbstractValidator {
	/**
	 * Properties that need to be in the submitted block to convert it into a parsed block.
	 *
	 * @var string[]
	 */
	private const REQUIRED_BLOCK_KEYS = [ 'blockName', 'attrs', 'innerBlocks', 'innerHTML', 'innerContent' ];

	/**
	 * Apply block validation logic and add any validation errors.
	 *
	 * @param WP_Block_Parser_Block $block The block to test.
	 */
	abstract protected function test_block( WP_Block_Parser_Block $block ): void;

	/**
	 * Apply validation logic and add any validation errors.
	 *
	 * @param mixed $value The value to test.
	 */
	final protected function testValue( $value ): void {
		$this->test_block( $value );
	}

	/**
	 * Converts the input into a parser block instance to be validated.
	 *
	 * @throws InvalidArgumentException If the submitted value can't be parsed into a block.
	 *
	 * @param array|WP_Block|WP_Block_Parser_Block $value Original block.
	 */
	protected function setValue( $value ) {
		$value = self::to_parser_block( $value );

		parent::setValue( $value );
	}

	/**
	 * Convert a block or a representation thereof to a parser block object.
	 *
	 * @throws InvalidArgumentException If input cannot be parsed.
	 *
	 * @param array|WP_Block|WP_Block_Parser_Block $value Original block.
	 * @return WP_Block_Parser_Block|null Block instance.
	 */
	protected static function to_parser_block( $value ): WP_Block_Parser_Block {
		if ( $value instanceof WP_Block ) {
			$value = $value->parsed_block;
		}

		if ( $value instanceof WP_Block_Parser_Block ) {
			$value = (array) $value;
		}

		$actual_keys = array_keys( $value );

		if ( array_diff( self::REQUIRED_BLOCK_KEYS, $actual_keys ) ) {
			throw new InvalidArgumentException( __( 'Cannot parse block from input.', 'alley' ) );
		}

		$value = new WP_Block_Parser_Block(
			$value['blockName'],
			$value['attrs'],
			$value['innerBlocks'],
			$value['innerHTML'],
			$value['innerContent'],
		);

		return $value;
	}
}
