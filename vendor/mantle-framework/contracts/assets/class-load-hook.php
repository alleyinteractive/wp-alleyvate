<?php
/**
 * Load_Hook class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Assets;

/**
 * Asset Load Hooks
 */
class Load_Hook {
	/**
	 * Header load method.
	 *
	 * @var string
	 */
	public const HEADER = 'wp_head';

	/**
	 * Footer load method.
	 *
	 * @var string
	 */
	public const FOOTER = 'wp_footer';
}
