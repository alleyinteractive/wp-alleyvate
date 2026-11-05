<?php

/*
 * This file is part of the laminas-validator-extensions package.
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Alley\Validator;

use Laminas\Validator\ValidatorInterface;

final class AlwaysValid implements ValidatorInterface
{
    public function isValid($value)
    {
        return true;
    }

    public function getMessages()
    {
        return [];
    }
}
