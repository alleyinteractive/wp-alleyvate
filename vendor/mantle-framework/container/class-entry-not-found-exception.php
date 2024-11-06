<?php
/**
 * Entry_Not_Found_Exception class file.
 *
 * @package Mantle
 */

namespace Mantle\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception for Entry Not Found
 */
class Entry_Not_Found_Exception extends Exception implements NotFoundExceptionInterface { }
