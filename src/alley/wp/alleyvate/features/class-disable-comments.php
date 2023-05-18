<?php
namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

class Disable_Comments implements Feature
{
    public function boot(): void
    {
        // Disable support for comments and trackbacks in post types
        $this->disable_support();

        // Close comments on the front-end
        $this->close_comments();

        // Hide existing comments
        $this->hide_existing_comments();

        // Remove comments page in menu
        $this->remove_menu_pages();

        // Redirect any user trying to access comments page
        $this->redirect_comment_page();

        // Remove comments icon from admin bar
        $this->remove_admin_comment_icon();

    }


    /**
     * Disable support for comments and trackbacks in post types.
     */
    private function disable_support()
    {
        add_action('admin_init', function() {
            $post_types = get_post_types();
            foreach ($post_types as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        });
    }

    /**
     * Close comments on the front-end.
     */
    private function close_comments()
    {
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
    }

    /**
     * Hide existing comments.
     */
    private function hide_existing_comments()
    {
        add_filter('comments_array', '__return_empty_array', 10, 2);
    }

    /**
     * Remove comments page in menu.
     */
    private function remove_menu_pages()
    {
        add_action('admin_menu', function() {
            remove_menu_page('edit-comments.php');
        });
    }

    /**
     * Redirect any user trying to access comments page.
     */
    private function redirect_comment_page()
    {
        add_action('admin_init', function() {
            global $pagenow;

            if ($pagenow === 'edit-comments.php') {
                wp_redirect(admin_url());
                exit;
            }
        });
    }

    /**
     * Remove comments icon from admin bar.
     */
    private function remove_admin_comment_icon(): void
    {
        add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
            $wp_admin_bar->remove_node( 'comments' );
        }, 999 );
    }
}
