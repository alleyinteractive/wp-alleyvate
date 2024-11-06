<?php
/**
 * Http_Client class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

/**
 * Http Request Client
 *
 * @deprecated Use \Mantle\Http_Client\Factory instead.
 */
class Http_Client {
	/**
	 * Create an instance of the Http Client
	 *
	 * @return Factory
	 */
	public static function create() {
		return new Factory();
	}
}
