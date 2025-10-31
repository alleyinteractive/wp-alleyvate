<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnsignedUriException extends SignedUriException
{
    /**
     * @internal
     */
    public function __construct()
    {
        parent::__construct('The URI is not signed.');
    }
}
