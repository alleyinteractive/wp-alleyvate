<?php
/**
 * Binding_Resolution_Exception class file.
 *
 * @package Mantle
 */

namespace Mantle\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Binding Resolution Error
 */
class Binding_Resolution_Exception extends Exception implements ContainerExceptionInterface { }
