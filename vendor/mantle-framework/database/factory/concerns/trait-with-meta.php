<?php
/**
 * With_Meta trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Concerns;

use Closure;
use InvalidArgumentException;

/**
 * Support model meta within the database factory
 *
 * @mixin \Mantle\Database\Factory\Factory
 */
trait With_Meta {
	/**
	 * Create a new factory instance to create posts with a set of meta.
	 *
	 * @param array<string, mixed>|string $meta Meta to assign to the post.
	 * @param mixed                       $value Optional. Value to assign to the meta key.
	 * @return static
	 */
	public function with_meta( array|string $meta, mixed $value = '' ) {
		if ( is_string( $meta ) ) {
			$meta = [ $meta => $value ];
		}

		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $meta ) {
				$args['meta'] = array_merge_recursive(
					$args['meta'] ?? [],
					$meta
				);

				return $next( $args );
			}
		);
	}
}
