<?php
/**
 * Class file for Disable_Yoast_Indexables.
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare(strict_types=1);

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;

/**
 * Performance enhancements for Yoast SEO.
 */
final class Yoast_Performance_Enhancements implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Disable the Yoast Indexables feature.
		add_filter( 'Yoast\WP\SEO\should_index_indexables', '__return_false' );

		// Disable the Yoast SEO Premium Prominent Words feature.
		add_filter( 'Yoast\WP\SEO\prominent_words_post_types', '__return_empty_array' );
		add_filter( 'Yoast\WP\SEO\prominent_words_taxonomies', '__return_empty_array' );
	}
}
