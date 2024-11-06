<?php
/**
 * Tappable trait.
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

use function Mantle\Support\Helpers\tap;

/**
 * Tappable Trait.
 */
trait Tappable {
	/**
	 * Call the given Closure with this instance then return the instance.
	 *
	 * @param  callable|null $callback
	 * @return $this|\Mantle\Support\Higher_Order_Tap_Proxy
	 */
	public function tap( $callback = null ) {
			return tap( $this, $callback );
	}
}
