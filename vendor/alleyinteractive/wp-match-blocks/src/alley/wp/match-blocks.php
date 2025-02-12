<?php
/**
 * `match_blocks()` functions
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-match-blocks
 */

namespace Alley\WP;

use Alley\Validator\FastFailValidatorChain;
use Alley\WP\Validator\Block_InnerHTML;
use Alley\WP\Validator\Block_Name;
use Alley\WP\Validator\Block_Offset;
use Alley\WP\Validator\Block_InnerBlocks_Count;
use Alley\WP\Validator\Nonempty_Block;
use Laminas\Validator\ValidatorInterface;

/**
 * Match blocks within the given content.
 *
 * @param int|\WP_Post|string|array[]|\WP_Block_Parser_Block|array $source Post ID or object with blocks in `post_content`, string of block HTML,
 *                                                                         array of blocks, or a single block instance. Passing a single block
 *                                                                         will return matches from its inner blocks.
 * @param array                                                    $args   {
 *    Optional. Array of arguments for matching which blocks to return. The defaults match all non-empty blocks.
 *
 *    @type array                     $attrs             {
 *        Match blocks with the given attributes.
 *
 *        @type string $relation Optional. The keyword used to join the block attribute clauses. Accepts 'AND', or 'OR'. Default 'AND'.
 *        @type array  ...$0 {
 *            An array of attribute clause parameters, or another fully formed array of attributes to match.
 *
 *            @type string|string[] $key          The name of a block attribute, or an array of names, or a regular
 *                                                expression pattern. Default none.
 *            @type mixed           $value        A block attribute value, or an array of values, or regular
 *                                                expression pattern. Default none.
 *            @type string          $operator     The operator with which to compare `$value` to block attributes.
 *                                                Accepts `CONTAINS`, `NOT CONTAINS` (case-sensitive), `IN`, `NOT IN`,
 *                                                `LIKE`, `NOT LIKE` (case-insensitive), `REGEX`, `NOT REGEX`, or any
 *                                                operator supported by `\Alley\Validator\Comparison`. Default `===`.
 *            @type string          $key_operator Equivalent to `$operator` but for `$key`.
 *        }
 *    }
 *    @type bool                      $count             Return the number of found blocks instead of the set.
 *    @type bool                      $has_innerblocks   Return only blocks that have, or don't have, inner blocks.
 *    @type ValidatorInterface        $is_valid          Match blocks that pass the given validator.
 *    @type bool                      $flatten           Recursively descend into inner blocks, test each one against the
 *                                                       criteria, and count each towards totals. Default false.
 *    @type int                       $limit             Extract at most this many blocks. Default `-1`, or no limit.
 *    @type int|int[]|string|string[] $nth_of_type       {
 *        Extract blocks based on their position in the set of found blocks.
 *
 *        @type string                    $relation Optional. The keyword used to join 'An+B' selectors if more than one is passed.
 *                                                  Accepts 'AND', or 'OR'. Default 'AND'. Integer arrays are always joined with 'OR'.
 *        @type int|int[]|string|string[] ...$0     A 1-based integer index or array of indices, a `An+B` pattern (like the `:nth-child`
 *                                                  pattern in CSS), or an array of `An+B` patterns for matching 1-based indices.
 *    }
 *    @type string|string[]           $name              Match blocks with this block name or names.
 *    @type int|int[]                 $position          Match blocks that appear at the given index. A negative index counts from the end.
 *                                                       Note that all blocks with identical HTML to the matched block will also match.
 *    @type bool                      $skip_empty_blocks Ignore blocks representing white space. Default true.
 *    @type string|string[]           $with_attrs        Match blocks with non-empty values for this attribute or these attributes.
 *                                                       Blocks must match all of the given `$with_attrs` and `$attrs`.
 *    @type string                    $with_innerhtml    Match blocks whose `innerHTML` property contains this content, ignoring case.
 * }
 * @return array[]|int Array of found blocks or count thereof.
 */
function match_blocks( $source, $args = [] ) {
	$args = wp_parse_args(
		$args,
		[
			'attrs'             => [],
			'count'             => false,
			'flatten'           => false,
			'has_innerblocks'   => null,
			'is_valid'          => null,
			'limit'             => -1,
			'name'              => '',
			'nth_of_type'       => null,
			'position'          => null,
			'skip_empty_blocks' => true,
			'with_attrs'        => [],
			'with_innerhtml'    => null,
		],
	);

	$blocks = [];
	$error  = $args['count'] ? 0 : [];

	if ( $source instanceof \WP_Block_Parser_Block ) {
		$source = (array) $source;
	}

	if ( \is_array( $source ) ) {
		$blocks = $source;
	}

	if ( is_numeric( $source ) || $source instanceof \WP_Post ) {
		$post = get_post( $source );

		if ( ! $post ) {
			return $error;
		}

		$blocks = parse_blocks( $post->post_content );
	}

	if ( \is_string( $source ) ) {
		$blocks = parse_blocks( $source );
	}

	if ( \is_array( $blocks ) && isset( $blocks['innerBlocks'] ) ) {
		$blocks = $blocks['innerBlocks'];
	}

	if ( ! wp_is_numeric_array( $blocks ) || 0 === \count( $blocks ) ) {
		return $error;
	}

	if ( $args['flatten'] ) {
		$blocks = Internals\flatten_blocks( $blocks );
	}

	try {
		$validator = new FastFailValidatorChain( [] );

		if ( $args['skip_empty_blocks'] ) {
			$validator->attach( new Nonempty_Block() );
		}

		if ( '' !== $args['name'] ) {
			$validator->attach(
				new Block_Name(
					[
						'name' => $args['name'],
					],
				),
			);
		}

		if ( null !== $args['position'] ) {
			$validator->attach(
				new Block_Offset(
					[
						'blocks'            => $blocks,
						'offset'            => $args['position'],
						'skip_empty_blocks' => $args['skip_empty_blocks'],
					],
				),
			);
		}

		if ( $args['with_attrs'] ) {
			$args['attrs'] = [
				'relation' => 'AND',
				[
					'key'          => (array) $args['with_attrs'],
					'key_operator' => 'IN',
					'value'        => '',
					'operator'     => '!=',
				],
				$args['attrs'],
			];
		}

		if ( $args['attrs'] && \is_array( $args['attrs'] ) ) {
			$validator->attach(
				Internals\parse_attrs_clauses( $args['attrs'] ),
			);
		}

		if ( \is_string( $args['with_innerhtml'] ) || $args['with_innerhtml'] instanceof \Stringable ) {
			$validator->attach(
				new Block_InnerHTML(
					[
						'content'  => $args['with_innerhtml'],
						'operator' => 'LIKE',
					],
				),
			);
		}

		if ( null !== $args['has_innerblocks'] ) {
			$validator->attach(
				new Block_InnerBlocks_Count(
					[
						'count'    => 0,
						'operator' => $args['has_innerblocks'] ? '>' : '===',
					],
				),
			);
		}

		if ( $args['is_valid'] instanceof ValidatorInterface ) {
			$validator->attach( $args['is_valid'] );
		}
	} catch ( \Exception $exception ) {
		return $error;
	}

	// Reduce to matching indices.
	$matches = array_map( [ $validator, 'isValid' ], $blocks );
	$matches = array_filter( $matches );
	$matches = array_keys( $matches );

	if ( null !== $args['nth_of_type'] ) {
		// These are 1-based indices. Map them to 0-based.
		$nth_of_type = Internals\parse_nth_of_type( $args['nth_of_type'], \count( $matches ) );
		$nth_indices = array_map(
			fn( $nth ) => (int) $nth - 1,
			$nth_of_type
		);

		// Flip indices into array keys, then intersect with keys of matched blocks.
		$nth_as_keys = array_flip( $nth_indices );
		$matches     = array_intersect_key( $matches, $nth_as_keys );
	}

	if ( $args['limit'] >= 0 ) {
		$matches = \array_slice( $matches, 0, $args['limit'] );
	}

	if ( $args['count'] ) {
		return \count( $matches );
	}

	// Flip indices into array keys.
	$matches = array_flip( $matches );

	// Intersect matching keys with keys in original list of blocks.
	$matches = array_intersect_key( $blocks, $matches );

	// Return matched blocks in a new list.
	return array_values( $matches );
}

/**
 * Return the first matching block from `match_blocks()`, if any.
 *
 * @param array|int|\WP_Post|string $source See `match_blocks()`.
 * @param array                     $args   See `match_blocks()`.
 * @return array|int|null The found block or null.
 */
function match_block( $source, $args = [] ): ?array {
	$args['limit'] = 1;

	$blocks = match_blocks( $source, $args );

	if ( \is_int( $blocks ) ) {
		return $blocks;
	}

	if ( isset( $blocks[0] ) && \is_array( $blocks[0] ) ) {
		return $blocks[0];
	}

	return null;
}
