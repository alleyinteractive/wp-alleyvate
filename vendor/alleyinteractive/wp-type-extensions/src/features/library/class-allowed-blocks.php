<?php
/**
 * Allowed_Blocks class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features\Library;

use Alley\WP\Types\Feature;
use Laminas\Validator\ValidatorInterface;
use WP_Block_Editor_Context;
use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Limit blocks allowed in the editor to those that are explicitly supported.
 */
final class Allowed_Blocks implements Feature {
	/**
	 * Set up.
	 *
	 * @param string|string[]        $context  Block editor context.
	 * @param ValidatorInterface     $allowed  Allowed block names.
	 * @param WP_Block_Type_Registry $registry Block type registry.
	 */
	public function __construct(
		private readonly string|array $context,
		private readonly ValidatorInterface $allowed,
		private readonly WP_Block_Type_Registry $registry,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'allowed_block_types_all', [ $this, 'filter_allowed_block_types' ], 10, 2 );
	}

	/**
	 * Filters the allowed block types for all editor types.
	 *
	 * @param bool|string[]           $allowed_block_types  Array of block type slugs, or boolean to enable/disable all.
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 * @return bool|string[] Updated block type slugs.
	 */
	public function filter_allowed_block_types( $allowed_block_types, $block_editor_context ) {
		if ( in_array( $block_editor_context->name, (array) $this->context, true ) ) {
			$updated = array_reduce(
				$this->registry->get_all_registered(),
				function ( array $carry, WP_Block_Type $type ) {
					if ( $this->allowed->isValid( $type->name ) ) {
						$carry[] = $type->name;
					}

					return $carry;
				},
				[],
			);

			// If the filter already had an array of names, honor those, but test them.
			if ( is_array( $allowed_block_types ) && count( $allowed_block_types ) > 0 ) {
				array_push( $updated, ...array_filter( $allowed_block_types, [ $this->allowed, 'isValid' ] ) );
			}

			$allowed_block_types = $updated;
		}

		return $allowed_block_types;
	}
}
