<?php
/**
 * Custom_Post_Permalink trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use Mantle\Database\Model\Permalink_Generator;

use function Mantle\Support\Helpers\add_filter;

/**
 * Define custom permalink structure for post models.
 */
trait Custom_Post_Permalink {
	/**
	 * Flag if permalinks are being used.
	 *
	 * @var bool
	 */
	protected static $using_permalinks;

	/**
	 * Boot the trait and add filters for the post type link single and archive link.
	 */
	public static function boot_custom_post_permalink(): void {
		if ( static::get_route() ) {
			if ( 'post' === static::get_object_name() ) {
				add_filter( 'post_link', [ self::class, 'filter_post_type_link' ] );
			} else {
				add_filter( 'post_type_link', [ self::class, 'filter_post_type_link' ] );
			}
		}

		if ( static::get_archive_route() ) {
			add_filter( 'post_type_archive_link', [ self::class, 'filter_post_type_archive_link' ] );
		}

		static::$using_permalinks = ! empty( get_option( 'permalink_structure' ) );
	}

	/**
	 * Filter the post type link to allow for customization.
	 *
	 * @param string   $post_link The post's permalink.
	 * @param \WP_Post $post     The post in question.
	 */
	public static function filter_post_type_link( string $post_link, \WP_Post $post ): string {
		if ( ! static::$using_permalinks || static::get_object_name() !== $post->post_type ) {
			return $post_link;
		}

		return Permalink_Generator::create( static::get_route(), static::find_or_fail( $post->ID ) );
	}

	/**
	 * Filter the post type archive link.
	 *
	 * @param string $link Post type archive link.
	 * @param string $post_type Post type.
	 */
	public static function filter_post_type_archive_link( string $link, string $post_type ): string {
		if ( 'post' === $post_type || ! static::$using_permalinks || static::get_object_name() !== $post_type ) {
			return $link;
		}

		return Permalink_Generator::create( static::get_archive_route() );
	}
}
