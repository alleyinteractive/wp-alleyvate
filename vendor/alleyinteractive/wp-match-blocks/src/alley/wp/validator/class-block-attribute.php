<?php
/**
 * Block_Attribute class file
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
use Alley\Validator\ValidatorByOperator;
use Laminas\Validator\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface;
use Traversable;
use WP_Block;
use WP_Block_Parser_Block;
use WP_Error;

/**
 * Validates whether the given block contains the specified attribute.
 */
final class Block_Attribute extends Block_Validator {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	public const NO_MATCHING_KEY = 'no_matching_key';

	/**
	 * Error code.
	 *
	 * @var string
	 */
	public const NO_MATCHING_VALUE = 'no_matching_value';

	/**
	 * Internal symbol.
	 *
	 * @var string
	 */
	private const UNDEFINED = '__UNDEFINED__';

	/**
	 * Array of validation failure message templates.
	 *
	 * @var string[]
	 */
	protected $messageTemplates = [
		self::NO_MATCHING_KEY   => '',
		self::NO_MATCHING_VALUE => '',
	];

	/**
	 * Options for this validator.
	 *
	 * @var array
	 */
	protected $options = [
		'key'          => null,
		'key_operator' => '===',
		'value'        => self::UNDEFINED,
		'operator'     => '===',
	];

	/**
	 * Validates attribute keys based on options.
	 *
	 * @var ValidatorInterface
	 */
	private ValidatorInterface $key_validator;

	/**
	 * Validates attribute values based on options.
	 *
	 * @var ValidatorInterface
	 */
	private ValidatorInterface $value_validator;

	/**
	 * Set up.
	 *
	 * @param array|Traversable $options Validator options.
	 */
	public function __construct( $options = null ) {
		$this->messageTemplates = [
			self::NO_MATCHING_KEY   => __( 'Block must have attribute with eligible key.', 'alley' ),
			self::NO_MATCHING_VALUE => __( 'Block must have attribute with eligible value.', 'alley' ),
		];

		$this->key_validator   = new AlwaysValid();
		$this->value_validator = new AlwaysValid();

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

		if ( null !== $next['key'] ) {
			try {
				$this->key_validator = new ValidatorByOperator( $next['key_operator'], $next['key'] );
			} catch ( \Exception $exception ) {
				throw new InvalidArgumentException( 'Invalid clause for attribute key: ' . $exception->getMessage() );
			}
		}

		if ( self::UNDEFINED !== $next['value'] ) {
			try {
				$this->value_validator = new ValidatorByOperator( $next['operator'], $next['value'] );
			} catch ( \Exception $exception ) {
				throw new InvalidArgumentException( 'Invalid clause for attribute value: ' . $exception->getMessage() );
			}
		}

		$options = array_merge( $options, $next );

		/*
		 * Temporarily move 'value' option to a new key so that
		 * `\Laminas\Validator\AbstractValidator::setOptions()`
		 * doesn't attempt to pass it to `::setValue()`.
		 */
		$options['val'] = $options['value'];
		unset( $options['value'] );

		return parent::setOptions( $options );
	}

	/**
	 * Restore 'value' option.
	 *
	 * @param mixed $val Value.
	 */
	protected function setVal( $val ) {
		$this->options['value'] = $val;
	}

	/**
	 * Apply block validation logic and add any validation errors.
	 *
	 * @param WP_Block_Parser_Block $block The block to test.
	 */
	protected function test_block( WP_Block_Parser_Block $block ): void {
		$attrs = $block->attrs;

		// For each test performed against the attributes, an array of matching attribute indices.
		$tests = [];

		// For each test performed against the attributes, the number of failed attributes.
		$misses = [];

		[ $hit_indices, $miss_count ] = self::test(
			$this->key_validator,
			array_keys( $attrs ),
		);

		$tests[]  = $hit_indices;
		$misses[] = $miss_count;

		if ( ! self::passing( $tests, $misses ) ) {
			$this->error( self::NO_MATCHING_KEY );
			return;
		}

		[ $hit_indices, $miss_count ] = self::test(
			$this->value_validator,
			array_values( $attrs )
		);

		$tests[]  = $hit_indices;
		$misses[] = $miss_count;

		if ( ! self::passing( $tests, $misses ) ) {
			$this->error( self::NO_MATCHING_VALUE );
			return;
		}
	}

	/**
	 * Test each of the given values against a configured operator.
	 *
	 * @param ValidatorInterface $validator Validator to test against.
	 * @param array              $values    Indexed list of values to test.
	 * @return array The indices of $values that passed the comparison and the
	 *               number of $values that failed the comparison.
	 */
	private static function test( ValidatorInterface $validator, array $values ): array {
		$hit_indices = [];

		foreach ( $values as $i => $value ) {
			if ( $validator->isValid( $value ) ) {
				$hit_indices[] = $i;
			}
		}

		return [ $hit_indices, \count( $values ) - \count( $hit_indices ) ];
	}

	/**
	 * Whether the block is passing validation.
	 *
	 * @param array[] $tests  Zero or more arrays, each containing the indices of
	 *                        attribute keys or values that passed a comparison.
	 * @param int[]   $misses The number of failed comparisons across tests of
	 *                        attribute keys and values.
	 * @return bool
	 */
	private static function passing( array $tests, array $misses ): bool {
		return (
			(
				// No comparisons were specified for key or value.
				\count( $tests ) === 0
				// At least one attribute was available on the block to test.
				|| ( \count( array_merge( ...$tests ) ) > 0 || array_filter( $misses ) )
			)
			&& (
				// Both key and value were tested, and at least one index matched in both.
				( \count( $tests ) > 1 && array_intersect( ...$tests ) )
				// Either key or value was tested, and at least one index matched.
				|| ( \count( $tests ) === 1 ) && \count( ...$tests ) > 0
				// No tests missed, if any ran.
				|| ! array_filter( $misses )
			)
		);
	}
}
