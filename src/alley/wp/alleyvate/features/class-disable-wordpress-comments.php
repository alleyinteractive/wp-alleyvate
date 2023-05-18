<?php
/**
 * Class file for Disable_WordPress_Comments
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

/**
 * Disable WordPress comments.
 */
final class Disable_WordPress_Comments implements Feature {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {

		add_filter( 'pings_open', '__return_false', 20, 2 );

		add_action( 'init', [ $this, 'remove_comment_support' ], 100 );
		add_action( 'admin_menu', [ $this, 'remove_comments_admin_menu' ] );

		/**
		 * Disable comment feeds.
		 */
		add_action( 'do_feed_rss2_comments', [ $this, 'disable_comment_feed' ], 1, 1 );
		add_action( 'do_feed_atom_comments', [ $this, 'disable_comment_feed' ], 1, 1 );
		add_action( 'do_feed_rss_comments', [ $this, 'disable_comment_feed' ], 1, 1 );
		add_action( 'do_feed_rdf_comments', [ $this, 'disable_comment_feed' ], 1, 1 );

		add_action( 'template_redirect', [ $this, 'remove_comments_template' ] );
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'comments_array', '__return_empty_array', 10, 2 );
		add_action( 'comments_template', [ $this, 'remove_comments_template' ], 11 );
		add_filter( 'comment_link', '__return_empty_string' );
		add_filter( 'comment_form_defaults', [ $this, 'remove_comment_form' ] );
	}

	/**
	 * Remove comment support.
	 */
	public function remove_comment_support() {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/**
	 * Remove comments admin menu.
	 */
	public function remove_comments_admin_menu() {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Remove Comments Template.
	 */
	public function remove_comments_template() {
		global $wp_query;
		if ( is_singular() ) {
			$wp_query->comments             = [];
			$wp_query->comment_count        = 0;
			$wp_query->post->comment_count  = 0;
			$wp_query->post->comment_status = 'closed';
		}
	}

	/**
	 * Remove Comment Form.
	 *
	 * @param array $defaults Defaults.
	 *
	 * @return array
	 */
	public function remove_comment_form( array $defaults ) {
		$defaults['comment_notes_after'] = '';

		return $defaults;
	}
}
