<?php
/**
 * Faker_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Faker;

use Faker\Provider\Base;
use Faker\Provider\Lorem;

/**
 * Faker Block Provider
 */
class Faker_Provider extends Base {
	/**
	 * Compile a set of blocks.
	 *
	 * @param array $blocks Blocks to compile.
	 */
	public static function blocks( array $blocks ): string {
		return implode( "\n\n", $blocks );
	}

	/**
	 * Build a heading block.
	 *
	 * @param string|int|null $text Heading text.
	 * @param int             $level Heading level.
	 */
	public static function heading_block( string|null|int $text = null, int $level = 2 ): string {
		if ( is_int( $text ) ) {
			$level = $text;
			$text  = null;
		}

		return static::block(
			'heading',
			sprintf( '<h%d>%s</h%d>', $level, $text ?: Lorem::sentence(), $level ),
			[
				'level' => $level,
			],
		);
	}

	/**
	 * Build a paragraph block.
	 *
	 * @param string|int|null $text Text for the block.
	 * @param int             $sentences Number of sentences in the block.
	 */
	public static function paragraph_block( string|null|int $text = null, int $sentences = 3 ): string {
		if ( is_int( $text ) ) {
			$sentences = $text;
			$text      = null;
		}

		return static::block(
			'paragraph',
			sprintf( '<p>%s</p>', $text ?: Lorem::sentences( $sentences, true ) )
		);
	}

	/**
	 * Generate a set of paragraph blocks.
	 *
	 * @param int  $count Number of paragraph blocks to generate.
	 * @param bool $as_text Return as text or an array of blocks.
	 * @return string|array
	 */
	public function paragraph_blocks( int $count = 3, bool $as_text = true ) {
		$paragraphs = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$paragraphs[] = static::paragraph_block();
		}

		return $as_text ? implode( "\n\n", $paragraphs ) : $paragraphs;
	}

	/**
	 * Build an image block.
	 *
	 * @param string|null $url Image URL.
	 * @param string|null $alt Image alt text.
	 * @param array       $attributes Additional attributes for the block.
	 */
	public function image_block( ?string $url = null, ?string $alt = null, array $attributes = [] ) {
		$image = sprintf(
			'<figure class="wp-block-image"><img src="%s"%s/></figure>',
			$url ?? 'https://picsum.photos/' . wp_rand( 100, 1000 ) . '/' . wp_rand( 100, 1000 ),
			$alt ? ' alt="' . esc_attr( $alt ) . '"' : '',
		);

		return static::block( 'image', $image, $attributes );
	}

	/**
	 * Build a block for Gutenberg.
	 *
	 * @param string $block_name Block name.
	 * @param string $content Content for the block.
	 * @param array  $attributes Attributes for the block.
	 */
	public static function block( string $block_name, string $content = '', array $attributes = [] ): string {
		// Add a newline before and after the content.
		if ( ! empty( $content ) ) {
			$content = "\n{$content}\n";
		}

		return get_comment_delimited_block_content( $block_name, $attributes, $content );
	}
}
