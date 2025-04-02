<?php
/**
 * Block_InnerBlocks_Count class file
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-match-blocks
 */

namespace Alley\WP\Validator;

use Alley\Validator\AlwaysValid;
use Alley\Validator\Comparison;
use Alley\Validator\Not;
use Alley\Validator\WithMessage;
use Laminas\Validator\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface;
use Traversable;
use WP_Block;
use WP_Block_Parser_Block;
use WP_Error;

/**
 * Validates whether the given block has a number of inner blocks.
 */
final class Block_InnerBlocks_Count extends Block_Validator {
	/**
	 * Array of validation failure message templates.
	 *
	 * @var string[]
	 */
	protected $messageTemplates = [
		'not_equal'                    => '',
		'not_identical'                => '',
		'is_equal'                     => '',
		'is_identical'                 => '',
		'not_less_than'                => '',
		'not_greater_than'             => '',
		'not_less_than_or_equal_to'    => '',
		'not_greater_than_or_equal_to' => '',
		'invalid_comparison'           => '',
		'default'                      => '',
	];

	/**
	 * Array of additional variables available for validation failure messages.
	 *
	 * @var string[]
	 */
	protected $messageVariables = [
		'count' => [
			'options' => 'count',
		],
	];

	/**
	 * Options for this validator.
	 *
	 * @var array
	 */
	protected $options = [
		'operator' => '>=',
		'count'    => 0,
	];

	/**
	 * Valid inner block counts based on options.
	 *
	 * @var ValidatorInterface
	 */
	private ValidatorInterface $valid_comparisons;

	/**
	 * Set up.
	 *
	 * @param array|Traversable $options Validator options.
	 */
	public function __construct( $options = null ) {
		$this->localize_templates();

		$this->valid_comparisons = new Comparison(
			[
				'operator' => $this->options['operator'],
				'compared' => $this->options['count'],
			],
		);

		parent::__construct( $options );
	}

	/**
	 * Sets one or multiple options. Refreshes the cached validators for the comparisons.
	 *
	 * @throws InvalidArgumentException If requested comparisons are invalid.
	 *
	 * @param array|Traversable $options Options to set.
	 * @return self
	 */
	public function setOptions( $options = [] ) {
		parent::setOptions( $options );

		try {
			$this->valid_comparisons = new Comparison(
				[
					'operator' => $this->options['operator'],
					'compared' => $this->options['count'],
				],
			);
		} catch ( \Exception $exception ) {
			$message = 'Invalid comparison options for count of inner blocks: ' . $exception->getMessage();

			/*
			 * Force all blocks to fail validation while the new options are invalid in relation to one another.
			 * Don't try to undo the work of `parent::setOptions()`, since that might leave the validator in
			 * an unpredictable state.
			 */
			$this->valid_comparisons = new WithMessage(
				'invalidComparison',
				$message,
				new Not( new AlwaysValid(), $message ),
			);

			throw new InvalidArgumentException( $message );
		}

		return $this;
	}

	/**
	 * Apply block validation logic and add any validation errors.
	 *
	 * @param WP_Block_Parser_Block $block The block to test.
	 */
	protected function test_block( WP_Block_Parser_Block $block ): void {
		$count = \count( $block->innerBlocks );

		if ( ! $this->valid_comparisons->isValid( $count ) ) {
			$message_keys = array_keys( $this->valid_comparisons->getMessages() );

			foreach ( $message_keys as $key ) {
				$this->error( $this->message( $key ), $count );
			}
		}
	}

	/**
	 * Sets the 'count' option.
	 *
	 * @param int $count Option.
	 */
	protected function setCount( $count ) {
		$this->options['count'] = (int) $count;
	}

	/**
	 * This validator relies on other validators to perform the final comparison of the inner block count.
	 * Here, map failure message identifiers from those validators to the ones defined by this validator.
	 *
	 * @param string $origin Upstream validator failure message identifier.
	 * @return string This validator's identifier.
	 */
	private function message( string $origin ): string {
		switch ( $origin ) {
			case 'notEqual':
				return 'not_equal';
			case 'notIdentical':
				return 'not_identical';
			case 'isEqual':
				return 'is_equal';
			case 'isIdentical':
				return 'is_identical';
			case 'notLessThan':
				return 'not_less_than';
			case 'notGreaterThan':
				return 'not_greater_than';
			case 'notLessThanOrEqualTo':
				return 'not_less_than_or_equal_to';
			case 'notGreaterThanOrEqualTo':
				return 'not_greater_than_or_equal_to';
			case 'invalidComparison':
				return 'invalid_comparison';
			default:
				return 'default';
		}
	}

	/**
	 * Localize message templates.
	 */
	private function localize_templates(): void {
		$neq = sprintf(
			/* translators: 1: expected count placeholder, 2: actual count placeholder */
			__( 'Number of inner blocks must be %1$s but is %2$s.', 'alley' ),
			'%count%',
			'%value%',
		);

		$ieq = sprintf(
			/* translators: 1: expected count placeholder */
			__( 'Number of inner blocks must not be %1$s.', 'alley' ),
			'%count%',
		);

		$nlt = sprintf(
			/* translators: 1: expected count placeholder, 2: actual count placeholder */
			__( 'Number of inner blocks must be less than %1$s but is %2$s.', 'alley' ),
			'%count%',
			'%value%',
		);

		$ngt = sprintf(
			/* translators: 1: expected count placeholder, 2: actual count placeholder */
			__( 'Number of inner blocks must be greater than %1$s but is %2$s.', 'alley' ),
			'%count%',
			'%value%',
		);

		$nlte = sprintf(
			/* translators: 1: expected count placeholder, 2: actual count placeholder */
			__( 'Number of inner blocks must be less than or equal to %1$s but is %2$s.', 'alley' ),
			'%count%',
			'%value%',
		);

		$ngte = sprintf(
			/* translators: 1: expected count placeholder, 2: actual count placeholder */
			__( 'Number of inner blocks must be greater than or equal to %1$s but is %2$s.', 'alley' ),
			'%count%',
			'%value%',
		);

		$this->messageTemplates = [
			'not_equal'                    => $neq,
			'not_identical'                => $neq,
			'is_equal'                     => $ieq,
			'is_identical'                 => $ieq,
			'not_less_than'                => $nlt,
			'not_greater_than'             => $ngt,
			'not_less_than_or_equal_to'    => $nlte,
			'not_greater_than_or_equal_to' => $ngte,
			'invalid_comparison'           => __( 'Invalid comparison options for count of inner blocks.', 'alley' ),
			'default'                      => __( 'Invalid count of inner blocks.', 'alley' ),
		];
	}
}
