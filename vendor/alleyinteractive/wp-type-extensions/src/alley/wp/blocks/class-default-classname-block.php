<?php
/**
 * Default_Classname_Block class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Single_Block;

/**
 * Wrap block markup in the block default classname.
 */
final class Default_Classname_Block implements Single_Block {
	/**
	 * Set up.
	 *
	 * @param string       $element Wrapper element.
	 * @param Single_Block $origin  Inner block.
	 */
	public function __construct(
		private readonly string $element,
		private readonly Single_Block $origin,
	) {}

	/**
	 * Block name.
	 *
	 * @return string|null
	 */
	public function block_name(): ?string {
		return $this->origin->block_name();
	}

	/**
	 * Parsed block.
	 *
	 * @return mixed[]
	 */
	public function parsed_block(): array {
		$out = $this->origin->parsed_block();

		$before = sprintf(
			'<%s class="%s">',
			tag_escape( $this->element ),
			wp_get_block_default_classname( (string) $this->block_name() ),
		);
		$after  = sprintf( '</%s>', tag_escape( $this->element ) );

		$out['innerHTML']    = $before . $out['innerHTML'] . $after;
		$out['innerContent'] = [ $before, ...$out['innerContent'], $after ];

		return $out;
	}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		return serialize_block( $this->parsed_block() );
	}
}
