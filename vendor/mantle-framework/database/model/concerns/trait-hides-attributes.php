<?php
/**
 * Hides_Attributes trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use Closure;
use function Mantle\Support\Helpers\value;

/**
 * Concern to hide attributes from serialization.
 */
trait Hides_Attributes {

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var string[]
	 */
	protected $hidden = [];

	/**
	 * The attributes that should be visible in serialization.
	 *
	 * @var string[]
	 */
	protected $visible = [];

	/**
	 * Get the hidden attributes for the model.
	 *
	 * @return string[]
	 */
	public function get_hidden(): array {
		return $this->hidden;
	}

	/**
	 * Set the hidden attributes for the model.
	 *
	 * @param string|string[] ...$hidden Hidden attributes.
	 * @return static
	 */
	public function set_hidden( ...$hidden ) {
		$this->hidden = $hidden;

		return $this;
	}

	/**
	 * Get the visible attributes for the model.
	 */
	public function get_visible(): array {
		return $this->visible;
	}

	/**
	 * Set the visible attributes for the model.
	 *
	 * @param string|string[] ...$visible Visible attributes.
	 * @return static
	 */
	public function set_visible( ...$visible ) {
		$this->visible = $visible;

		return $this;
	}

	/**
	 * Make the given, typically hidden, attributes visible.
	 *
	 * @param array|string ...$attributes Attributes to make visible.
	 * @return $this
	 */
	public function make_visible( ...$attributes ) {
		$this->hidden = array_diff( $this->hidden, $attributes );

		if ( ! empty( $this->visible ) ) {
			$this->visible = array_merge( $this->visible, $attributes );
		}

		return $this;
	}

	/**
	 * Make the given, typically hidden, attributes visible if the given truth test passes.
	 *
	 * @param  bool|Closure         $condition Condition to check.
	 * @param  string[]|string|null ...$attributes Attributes to make visible.
	 * @return static
	 */
	public function make_visible_if( $condition, ...$attributes ) {
		$condition = $condition instanceof Closure ? $condition( $this ) : $condition;

		return $condition ? $this->make_visible( ...$attributes ) : $this;
	}

	/**
	 * Make the given, typically visible, attributes hidden.
	 *
	 * @param  array|string|null ...$attributes Attributes to make hidden.
	 * @return static
	 */
	public function make_hidden( ...$attributes ) {
		$this->hidden = array_merge( $this->hidden, $attributes );

		return $this;
	}

	/**
	 * Make the given, typically visible, attributes hidden if the given truth test passes.
	 *
	 * @param  bool|Closure         $condition Condition to check.
	 * @param  string[]|string|null ...$attributes Attributes to make hidden.
	 * @return static
	 */
	public function make_hidden_if( $condition, ...$attributes ) {
		$condition = $condition instanceof Closure ? $condition( $this ) : $condition;

		return value( $condition ) ? $this->make_hidden( $attributes ) : $this;
	}
}
