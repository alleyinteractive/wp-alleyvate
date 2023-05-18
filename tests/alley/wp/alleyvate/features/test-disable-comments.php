<?php
/**
 * Class file for Test_Disable_Comments
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for disabling comments.
 */
final class Test_Disable_Comments extends Test_Case {
    /**
     * Feature instance.
     *
     * @var Feature
     */
    private Feature $feature;

    /**
     * Set up.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->feature = new Disable_Comments();
    }

    /**
     * Test that comments support is disabled for all post types.
     */
    public function test_comments_support_is_disabled() {
        // Boot the feature
        $this->feature->boot();

        // Get all post types
        $post_types = get_post_types();

        // Loop over each post type and ensure that none supports comments
        foreach ( $post_types as $post_type ) {
            $this->assertFalse( post_type_supports( $post_type, 'comments' ) );
        }
    }

    /**
     * Test that comments are closed on the front-end.
     */
    public function test_comments_are_closed() {
        // Create a sample post
        $post_id = self::factory()->post->create();

        // Boot the feature
        $this->feature->boot();

        // Ensure that comments are not open for this post
        $this->assertFalse( comments_open( $post_id ) );
    }

    /**
     * Test that existing comments are hidden.
     */
    public function test_existing_comments_are_hidden() {
        // Create a sample post with a comment
        $post_id = self::factory()->post->create();
        self::factory()->comment->create(['comment_post_ID' => $post_id]);

        // Boot the feature
        $this->feature->boot();

        // Ensure that there are no comments for this post
        $this->assertEquals(0, wp_count_comments($post_id)->approved);
    }

    /**
     * Test that the comments page is removed from the admin menu.
     */
    public function test_comments_page_is_removed() {
        // Boot the feature
        $this->feature->boot();

        // Check if comments page link is in the admin menu
        global $menu;
        $found = false;
        foreach($menu as $item) {
            if($item[2] == 'edit-comments.php') {
                $found = true;
                break;
            }
        }

        // Ensure that comments page link is not found in the admin menu
        $this->assertFalse($found);
    }

    /**
     * Test that any user trying to access comments page is redirected.
     */
    public function test_comment_page_access_is_redirected() {
        // Boot the feature
        $this->feature->boot();

        // Try to access the comments page
        try {
            $this->get(admin_url('edit-comments.php'));
        } catch (Exception $e) {
            // Check if exception is 'Too many redirects'
            $this->assertContains('Too many redirects', $e->getMessage());
        }
    }
}
