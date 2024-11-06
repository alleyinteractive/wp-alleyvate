<?php
/**
 * This file contains the WordPress_State trait
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Carbon\Carbon;
use DateTimeInterface;
use Mantle\Database\Model\Post;
use Mantle\Testing\Utils;
use WP_Post;

/**
 * This trait includes functionality for controlling WordPress state during
 * testing.
 */
trait WordPress_State {

	/**
	 * Cleans the global scope (e.g `$_GET` and `$_POST`).
	 */
	public function clean_up_global_scope(): void {
		$_GET  = [];
		$_POST = [];
		self::flush_cache();
	}

	/**
	 * Flushes the WordPress object cache.
	 */
	public static function flush_cache(): void {
		global $wp_object_cache;
		$wp_object_cache->group_ops      = [];
		$wp_object_cache->stats          = [];
		$wp_object_cache->memcache_debug = [];
		$wp_object_cache->cache          = [];
		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset();
		}
		wp_cache_flush();
		wp_cache_add_global_groups(
			[
				'users',
				'userlogins',
				'usermeta',
				'user_meta',
				'useremail',
				'userslugs',
				'site-transient',
				'site-options',
				'blog-lookup',
				'blog-details',
				'rss',
				'global-posts',
				'blog-id-cache',
				'networks',
				'sites',
				'site-details',
				'blog_meta',
			]
		);
		wp_cache_add_non_persistent_groups( [ 'comment', 'counts', 'plugins' ] );
	}

	/**
	 * Unregister existing post types and register defaults.
	 *
	 * Run before each test in order to clean up the global scope, in case
	 * a test forgets to unregister a post type on its own, or fails before
	 * it has a chance to do so.
	 */
	protected function reset_post_types() {
		foreach ( get_post_types( [], 'objects' ) as $pt ) {
			if ( empty( $pt->tests_no_auto_unregister ) ) {
				unregister_post_type( $pt->name );
			}
		}
		create_initial_post_types();
	}

	/**
	 * Unregister existing taxonomies and register defaults.
	 *
	 * Run before each test in order to clean up the global scope, in case
	 * a test forgets to unregister a taxonomy on its own, or fails before
	 * it has a chance to do so.
	 */
	protected function reset_taxonomies() {
		foreach ( get_taxonomies() as $tax ) {
			unregister_taxonomy( $tax );
		}
		create_initial_taxonomies();
	}

	/**
	 * Unregister non-built-in post statuses.
	 */
	protected function reset_post_statuses() {
		foreach ( get_post_stati( [ '_builtin' => false ] ) as $post_status ) {
			Utils::unregister_post_status( $post_status );
		}
	}

	/**
	 * Clean up any registered meta keys.
	 *
	 * @since 5.1.0
	 *
	 * @global array $wp_meta_keys
	 */
	public function unregister_all_meta_keys(): void {
		global $wp_meta_keys;
		if ( ! is_array( $wp_meta_keys ) ) {
			return;
		}
		foreach ( $wp_meta_keys as $object_type => $type_keys ) {
			foreach ( $type_keys as $object_subtype => $subtype_keys ) {
				foreach ( $subtype_keys as $key => $value ) {
					unregister_meta_key( $object_type, $key, $object_subtype );
				}
			}
		}
	}

	/**
	 * Deletes a user from the database in a Multisite-agnostic way.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id User ID.
	 * @return bool True if the user was deleted.
	 */
	public static function delete_user( $user_id ) {
		if ( is_multisite() ) {
			return wpmu_delete_user( $user_id );
		}

		return wp_delete_user( $user_id );
	}

	/**
	 * Resets permalinks and flushes rewrites.
	 *
	 * @since 4.4.0
	 *
	 * @global \WP_Rewrite $wp_rewrite
	 *
	 * @param string $structure Optional. Permalink structure to set. Default empty.
	 */
	public function set_permalink_structure( $structure = '' ): void {
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions
	}

	/**
	 * Updates the modified and modified GMT date of a post in the database.
	 *
	 * @param WP_Post|Post|int         $post Post ID or post object.
	 * @param DateTimeInterface|string $date Date object or string to update the
	 *                                       post with. If a string is passed it
	 *                                       is assumed to be local timezone.
	 */
	protected function update_post_modified( WP_Post|Post|int $post, DateTimeInterface|string $date ): bool {
		$post = match ( true ) {
			$post instanceof WP_Post => Post::for( $post->post_type )->find( $post->ID ),
			$post instanceof Post    => $post,
			default                  => Post::for( get_post_type( $post ) )->find( $post ),
		};

		return $post->save(
			[
				'post_modified' => $date instanceof DateTimeInterface ? $date->format( 'Y-m-d H:i:s' ) : $date,
			]
		);
	}
}
