<?php
/**
 * File class file.
 *
 * @package Mantle
 */

namespace Mantle\Filesystem;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * A file in the filesystem.
 */
class File extends SymfonyFile {
	use File_Helpers;
}
