<?php
/**
 * This file contains the Hooks Trait
 *
 * @package Mantle
 */

// phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.SelfOutsideClass

namespace Mantle\Testing\Concerns;

trait Hooks {

	/**
	 * Saved hooks.
	 *
	 * @var array
	 */
	protected static $hooks_saved = [];

	/**
	 * Routines to run during setUp().
	 */
	public function hooks_set_up(): void {
		if ( ! self::$hooks_saved ) {
			$this->backup_hooks();
		}
	}

	/**
	 * Routines to run during tearDown().
	 */
	public function hooks_tear_down(): void {
		$this->restore_hooks();
	}

	/**
	 * Saves the action and filter-related globals so they can be restored later.
	 *
	 * Stores $wp_actions, $wp_current_filter, and $wp_filter on a class variable
	 * so they can be restored on tearDown() using _restore_hooks().
	 *
	 * @global array $wp_actions
	 * @global array $wp_current_filter
	 * @global array $wp_filter
	 */
	protected function backup_hooks() {
		$globals = [ 'wp_actions', 'wp_current_filter' ];
		foreach ( $globals as $global ) {
			self::$hooks_saved[ $global ] = $GLOBALS[ $global ];
		}
		self::$hooks_saved['wp_filter'] = [];
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $hook_object ) {
			self::$hooks_saved['wp_filter'][ $hook_name ] = clone $hook_object;
		}
	}

	/**
	 * Restores the hook-related globals to their state at setUp()
	 * so that future tests aren't affected by hooks set during this last test.
	 *
	 * @global array $wp_actions
	 * @global array $wp_current_filter
	 * @global array $wp_filter
	 */
	protected function restore_hooks() {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride,WordPress.NamingConventions.PrefixAllGlobals
		$globals = [ 'wp_actions', 'wp_current_filter' ];
		foreach ( $globals as $global ) {
			if ( isset( self::$hooks_saved[ $global ] ) ) {
				$GLOBALS[ $global ] = self::$hooks_saved[ $global ];
			}
		}
		if ( isset( self::$hooks_saved['wp_filter'] ) ) {
			$GLOBALS['wp_filter'] = [];
			foreach ( self::$hooks_saved['wp_filter'] as $hook_name => $hook_object ) {
				$GLOBALS['wp_filter'][ $hook_name ] = clone $hook_object;
			}
		}
		// phpcs:enable
	}
}
