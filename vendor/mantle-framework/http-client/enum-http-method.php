<?php
/**
 * Http_Method enum file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

/**
 * Http Request Methods
 */
enum Http_Method: string {
	case HEAD    = 'HEAD';
	case GET     = 'GET';
	case POST    = 'POST';
	case PUT     = 'PUT';
	case PATCH   = 'PATCH';
	case DELETE  = 'DELETE';
	case PURGE   = 'PURGE';
	case OPTIONS = 'OPTIONS';
	case TRACE   = 'TRACE';
	case CONNECT = 'CONNECT';
}
