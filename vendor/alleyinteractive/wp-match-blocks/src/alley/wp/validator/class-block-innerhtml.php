<?php
/**
 * Block_InnerHTML class file
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-match-blocks
 */

namespace Alley\WP\Validator;

use Alley\Validator\ValidatorByOperator;
use Laminas\Validator\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface;
use Traversable;
use WP_Block;
use WP_Block_Parser_Block;
use WP_Error;

/**
 * Validates whether the given block's inner HTML contains the given content.
 */
final class Block_InnerHTML extends Block_Validator {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	public const NO_MATCHING_INNERHTML = 'no_matching_innerhtml';

	/**
	 * Array of validation failure message templates.
	 *
	 * @var string[]
	 */
	protected $messageTemplates = [
		self::NO_MATCHING_INNERHTML => '',
	];

	/**
	 * Options for this validator.
	 *
	 * @var array
	 */
	protected $options = [
		'content'  => '',
		'operator' => 'LIKE',
	];

	/**
	 * Validates block inner HTML based on options.
	 *
	 * @var ValidatorInterface
	 */
	private ValidatorInterface $valid_html;

	/**
	 * Set up.
	 *
	 * @param array|Traversable $options Validator options.
	 */
	public function __construct( $options = null ) {
		$this->messageTemplates[ self::NO_MATCHING_INNERHTML ] = __( 'Block inner HTML does not match.', 'alley' );

		$this->valid_html = new ValidatorByOperator( $this->options['operator'], $this->options['content'] );

		parent::__construct( $options );
	}

	/**
	 * Sets one or multiple options. Merges new options into existing options, validates them in relation to one
	 * another, and refreshes the cached validators for the comparisons.
	 *
	 * @throws InvalidArgumentException If requested comparisons are invalid.
	 *
	 * @param array|Traversable $options Options to set.
	 * @return self
	 */
	public function setOptions( $options = [] ) {
		$next = $this->options;

		foreach ( $options as $key => $value ) {
			if ( \array_key_exists( $key, $next ) ) {
				$next[ $key ] = $value;
			}
		}

		try {
			$this->valid_html = new ValidatorByOperator( $next['operator'], $next['content'] );
		} catch ( \Exception $exception ) {
			throw new InvalidArgumentException( 'Invalid clause for inner HTML: ' . $exception->getMessage() );
		}

		$options = array_merge( $options, $next );

		return parent::setOptions( $options );
	}

	/**
	 * Apply block validation logic and add any validation errors.
	 *
	 * @param WP_Block_Parser_Block $block The block to test.
	 */
	protected function test_block( WP_Block_Parser_Block $block ): void {
		if ( ! $this->valid_html->isValid( $block->innerHTML ) ) {
			$this->error( self::NO_MATCHING_INNERHTML );
		}
	}
}
