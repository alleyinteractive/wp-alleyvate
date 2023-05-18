<?php

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

/**
 * Disable Comments Class
 *
 * This class will disable all core features related to commenting and discussion in WordPress.
 *
 * @implements Feature
 */
class Disable_Comments implements Feature
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Disable comments on all posts and pages
        \add_filter('comments_open', '__return_false');

        // \remove the comments menu item from the admin menu
        \add_action('admin_menu', function() {
            \remove_menu_page('edit-comments.php');
        });

        // \remove the comments meta box from the post editor
        \add_action('add_meta_boxes', function() {
            \remove_meta_box('commentstatusdiv', 'post', 'normal');
            \remove_meta_box('commentstatusdiv', 'page', 'normal');
        });

        // \remove the comments link from the post permalink
        \add_filter('post_link', function ($permalink) {
            if (is_singular()) {
                $permalink = \remove_query_arg('comments_popup', $permalink);
            }

            return $permalink;
        });

        // Disable the Comments quick-edit box
        \add_filter('quick_edit_custom_box', function($actions, $post) {
            if ($post->post_type === 'post') {
                unset($actions['comment']);
            }
            return $actions;
        }, 10, 2);

        // \remove the Comments link from the admin bar
        \add_action('admin_bar_menu', function(\WP_Admin_Bar $wp_admin_bar) {
            $wp_admin_bar->remove_menu('comments');
        });

        // Disable the Discussion settings page
        \add_filter('views_edit-post', function($views) {
            unset($views['discussion']);
            return $views;
        });

        // Disable the Discussion settings metabox
        \add_filter('add_meta_boxes', function() {
            \remove_meta_box('advanced_view', 'post', 'side');
        });

        // \remove the admin link to wp-admin/options-discussion.php
        \add_action('admin_menu', function() {
            \remove_submenu_page('options-general.php', 'options-discussion.php');
        });

        // \remove the Discussion panel in the Block Editor (Gutenberg) sidebar
        \add_filter('block_editor_panels', function( $panels ) {
            unset( $panels['discussion'] );
            unset( $panels['discussion']['options']['comment_status'] );
            unset( $panels['discussion']['options']['ping_status'] );
            return $panels;
        } );
    }

    /**
     * Boot the feature.
     */
    public function boot(): void
    {
        // Do nothing.
    }
}