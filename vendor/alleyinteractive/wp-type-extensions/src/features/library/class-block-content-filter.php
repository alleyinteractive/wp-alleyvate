<?php
/**
 * Block_Content_Filter class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features\Library;

use Alley\WP\Blocks\Block_Content;
use Alley\WP\Types\Feature;
use Alley\WP\Types\Serialized_Blocks;

/**
 * Filter block markup in 'the_content' for the post being viewed.
 */
final class Block_Content_Filter implements Feature {
	/**
	 * Callback to merge blocks.
	 *
	 * @var callable
	 */
	private $block_merge;

	/**
	 * Constructor.
	 *
	 * @phpstan-param callable(Serialized_Blocks $post_content): Serialized_Blocks $block_merge
	 *
	 * @param callable $block_merge Callback to merge blocks.
	 */
	public function __construct(
		callable $block_merge,
	) {
		$this->block_merge = $block_merge;
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'the_content', [ $this, 'filter_the_content' ], 8 );
	}

	/**
	 * Filters the post content.
	 *
	 * @param string $content Content of the current post.
	 * @return string Updated content.
	 */
	public function filter_the_content( $content ) {
		$post = get_post();

		// Skip if 'the_content' is running on non-block content or on content other than the post being viewed.
		if ( $post && is_single( $post->ID ) && has_blocks( $content ) ) {
			$content = ( $this->block_merge )( new Block_Content( $content ) )->serialized_blocks();
		}

		return $content;
	}
}
