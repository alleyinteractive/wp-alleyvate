<?php
/**
 * Custom_Term_Link trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use Mantle\Database\Model\Permalink_Generator;

use function Mantle\Support\Helpers\add_filter;

/**
 * Define custom permalink structure for post models.
 */
trait Custom_Term_Link {
	/**
	 * Boot the trait and add filters for the post type link single and archive link.
	 */
	public static function boot_custom_term_link(): void {
		if ( static::get_route() ) {
			add_filter( 'term_link', [ self::class, 'filter_term_link' ], 99 );
		}
	}

	/**
	 * Filter the term link to use the model's route.
	 *
	 * @param string   $term_link Term link to filter.
	 * @param \WP_Term $term Term object.
	 * @param string   $taxonomy Taxonomy name.
	 */
	public static function filter_term_link( string $term_link, \WP_Term $term, string $taxonomy ): string {
		if ( static::get_object_name() !== $taxonomy ) {
			return $term_link;
		}

		return Permalink_Generator::create( static::get_route(), static::find_or_fail( $term->term_id ) );
	}
}
