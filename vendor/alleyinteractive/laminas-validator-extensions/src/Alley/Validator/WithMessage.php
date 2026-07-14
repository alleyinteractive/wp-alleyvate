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

final class WithMessage implements ValidatorInterface
{
    private string $code;

    private string $message;

    private ValidatorInterface $origin;

    public function __construct(string $code, string $message, ValidatorInterface $origin)
    {
        $this->origin = $origin;
        $this->code = $code;
        $this->message = $message;
    }

    public function isValid($value)
    {
        return $this->origin->isValid($value);
    }

    public function getMessages()
    {
        $messages = [];

        if (\count($this->origin->getMessages()) > 0) {
            $messages[$this->code] = $this->message;
        }

        return $messages;
    }
}
