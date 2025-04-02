<?php
/**
 * Internal functions. These functions are not subject to semantic-versioning constraints
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-match-blocks
 */

namespace Alley\WP\Internals;

use Alley\Validator\AnyValidator;
use Alley\Validator\FastFailValidatorChain;
use Alley\WP\Validator\Block_Attribute;
use Laminas\Validator\ValidatorInterface;

/**
 * Merge inner blocks into the top-level array of blocks.
 *
 * @param array $blocks Blocks.
 * @return array More blocks.
 */
function flatten_blocks( array $blocks ): array {
	$out = [];

	while ( $blocks ) {
		$block = array_shift( $blocks );
		$out[] = $block;

		if ( ! empty( $block['innerBlocks'] ) ) {
			array_unshift( $blocks, ...$block['innerBlocks'] );
		}
	}

	return $out;
}

/**
 * Parse 'attrs' clauses into validators.
 *
 * @throws \Exception If clauses are malformed.
 *
 * @param array $args 'attrs' argument.
 * @return ValidatorInterface
 */
function parse_attrs_clauses( array $args ): ValidatorInterface {
	$relation = 'AND';

	if ( isset( $args['relation'] ) && 'OR' === strtoupper( $args['relation'] ) ) {
		$relation = 'OR';
	}

	unset( $args['relation'] );

	$chain = [];

	foreach ( $args as $clause ) {
		if ( ! \is_array( $clause ) ) {
			continue;
		}

		if ( isset( $clause['relation'] ) || isset( $clause[0] ) ) {
			$chain[] = parse_attrs_clauses( $clause );
			continue;
		}

		$chain[] = new Block_Attribute( $clause );
	}

	if ( \count( $chain ) === 0 ) {
		throw new \Exception();
	}

	if ( 'AND' === $relation ) {
		return new FastFailValidatorChain( $chain );
	}

	// If it's not AND then it's OR.
	return new AnyValidator( $chain );
}

/**
 * Parse the 'nth_of_type' parameter into the matching 1-based indices.
 *
 * @param int|int[]|string|string[] $args 'nth_of_type' argument.
 * @param int                       $max  Total number of available blocks.
 * @return int[] Matching indices within the set of available blocks.
 */
function parse_nth_of_type( $args, int $max ): array {
	if ( \is_int( $args ) ) {
		return [ $args ];
	}

	$args = (array) $args;

	if ( array_filter( $args, 'is_int' ) === $args ) {
		return $args;
	}

	$selectors = $args;
	unset( $selectors['relation'] );

	$matches = [];

	foreach ( $selectors as $selector ) {
		if ( 'odd' === $selector ) {
			$selector = '2n+1';
		}

		if ( 'even' === $selector ) {
			$selector = '2n';
		}

		if ( preg_match( '/^([+-]?\d+)?(-?n)?([+-]\d+)?$/', $selector, $pieces ) ) {
			$a = isset( $pieces[1] ) && \strlen( $pieces[1] ) ? $pieces[1] : null;
			$n = $pieces[2] ?? null;
			$b = $pieces[3] ?? null;

			if ( ! $n ) {
				// Matches '\d'.
				return [ (int) $a ];
			}

			if ( $n && '0' === $a ) {
				// Matches '0n' or '0n+\d'.
				return isset( $b ) ? [ (int) $b ] : [];
			}

			$indices = [];
			$i       = 0;

			while ( true ) {
				$next = $i;

				if ( '-n' === $n ) {
					$next *= -1;
				}

				if ( isset( $a ) ) {
					$next *= (int) $a;
				}

				if ( isset( $b ) ) {
					$next += (int) $b;
				}

				if ( $max < abs( $next ) ) {
					break;
				}

				$indices[] = $next;
				$i++;
			}

			$matches[] = $indices;
		}
	}

	if ( ! $matches ) {
		return [];
	}

	if ( \count( $matches ) === 1 ) {
		return $matches[0];
	}

	if ( isset( $args['relation'] ) && 'OR' === $args['relation'] ) {
		return array_unique( array_merge( ...$matches ) );
	}

	return array_intersect( ...$matches );
}
