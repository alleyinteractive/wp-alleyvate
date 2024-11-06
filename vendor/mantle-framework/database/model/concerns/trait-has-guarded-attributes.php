<?php
/**
 * Has_Guarded_Attributes class file.
 *
 * @package Mantle
 */

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
// phpcs:ignoreFile: WordPressVIPMinimum.Variables.VariableAnalysis.StaticInsideClosure

namespace Mantle\Database\Model\Concerns;

/**
 * Guard Specific Attributes from being set.
 */
trait Has_Guarded_Attributes {
	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [];

	/**
	 * Flag if the model is being guarded.
	 *
	 * @var bool
	 */
	protected $guarded = true;

	/**
	 * Indicates if all mass assignment is enabled.
	 *
	 * @var bool
	 */
	protected static $unguarded = false;

	/**
	 * Check if the model is guarded.
	 */
	public function is_model_guarded(): bool {
		return $this->guarded;
	}

	/**
	 * Set if a model is or is not being guarded.
	 *
	 * @param bool $guarded Flag if the model is being guarded.
	 */
	public function set_model_guard( bool $guarded ): void {
		$this->guarded = $guarded;
	}

	/**
	 * Check if a model attribute is guarded.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function is_guarded( string $attribute ): bool {
		if ( ! $this->guarded ) {
			return false;
		}

		return in_array( $attribute, $this->guarded_attributes, true );
	}

	/**
	 * Check if the model is currently guarded.
	 *
	 * @param bool $guarded Flag if the model is guarded.
	 */
	public function guard( bool $guarded ): void {
		$this->guarded = $guarded;
	}

	/**
	 * Run the given callable while being unguarded.
	 *
	 * @param callable $callback
	 *
	 * @return mixed
	 */
	public static function unguarded( callable $callback ) {
		if ( static::$unguarded ) {
			return $callback();
		}

		static::unguard();

		try {
			return $callback();
		} finally {
			static::reguard();
		}
	}

	/**
	 * Disable all mass assignable restrictions.
	 *
	 * @param bool $state
	 */
	public static function unguard( $state = true ): void {
		static::$unguarded = $state;
	}

	/**
	 * Enable the mass assignment restrictions.
	 */
	public static function reguard(): void {
		static::$unguarded = false;
	}
}
