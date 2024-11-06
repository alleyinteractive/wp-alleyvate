<?php
/**
 * Block_Factory class file
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Faker\Generator;
use InvalidArgumentException;

use function Mantle\Support\Helpers\collect;

/**
 * Block Factory
 *
 * Used to generate blocks and create presets of blocks for testing.
 *
 * @method string block(string $name = 'paragraph', string $content = '', array $attributes = [])
 * @method string image(?string $url = null, ?string $alt = null, array $attributes = [])
 * @method string heading(?string $text = null, int $level = 2)
 * @method string paragraph(?string $text = null, int $sentences = 3)
 * @method string paragraphs(int $count = 3, bool $as_text = true)
 */
class Block_Factory {
	/**
	 * Presets of blocks.
	 *
	 * @var array<string, array|string|callable>
	 */
	public static array $presets = [];

	/**
	 * Register a preset of blocks.
	 *
	 * @param string                $name Name of the preset.
	 * @param array|string|callable $preset Preset to register.
	 */
	public static function register_preset( string $name, array|string|callable $preset ): void {
		static::$presets[ $name ] = $preset;
	}

	/**
	 * Clear all presets.
	 */
	public static function clear_presets(): void {
		static::$presets = [];
	}


	/**
	 * Constructor.
	 *
	 * @param Generator $faker Faker generator.
	 */
	public function __construct( protected Generator $faker ) {}

	/**
	 * Apply a preset.
	 *
	 * @throws InvalidArgumentException If the preset is not found.
	 *
	 * @param string $name Name of the preset.
	 * @param array  $arguments Arguments to pass to the preset.
	 */
	public function preset( string $name, array $arguments = [] ): string {
		if ( ! isset( static::$presets[ $name ] ) ) {
			throw new InvalidArgumentException( "Unknown block factory preset: {$name}" );
		}

		$result = static::$presets[ $name ];

		if ( is_callable( $result ) ) {
			$result = $result( $this, ...$arguments );
		}

		return is_array( $result ) ? serialize_blocks( $result ) : $result;
	}

	/**
	 * Magic method to generate blocks from a preset.
	 *
	 * @throws InvalidArgumentException If the block method is not found.
	 *
	 * @param string $name Name of the preset.
	 * @param array  $arguments Arguments to pass to the preset.
	 */
	public function __call( string $name, array $arguments ): string {
		if ( isset( static::$presets[ $name ] ) ) {
			return static::preset( $name, $arguments );
		}

		$method = match ( $name ) {
			'paragraphs' => 'paragraph_blocks',
			'block' => 'block',
			default => "{$name}_block",
		};

		try {
			return $this->faker->$method( ...$arguments );
		} catch ( InvalidArgumentException ) {
			throw new InvalidArgumentException( "Unknown block factory method: {$name}" );
		}
	}

	/**
	 * Generate a collection of blocks.
	 *
	 * Blocks can be passed as an array or as individual arguments.
	 *
	 * @param array|string ...$blocks Blocks to generate.
	 */
	public function blocks( array|string ...$blocks ): string {
		$blocks = isset( $blocks[0] ) && is_array( $blocks[0] ) ? $blocks[0] : $blocks;

		if ( ! is_array( $blocks ) ) {
			$blocks = [ $blocks ];
		}

		return collect( $blocks )
			->map( fn ( $block ) => is_array( $block ) ? serialize_blocks( $block ) : $block )
			->implode( "\n\n" );
	}
}
