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

final class Not implements ValidatorInterface
{
    public const NOT_VALID = 'notValid';

    private ValidatorInterface $origin;

    private string $message;

    private bool $ran = false;

    public function __construct(ValidatorInterface $origin, string $message)
    {
        $this->origin = $origin;
        $this->message = $message;
    }

    public function isValid($value)
    {
        $this->ran = true;
        return !$this->origin->isValid($value);
    }

    public function getMessages()
    {
        $messages = [];

        if ($this->ran && \count($this->origin->getMessages()) === 0) {
            $messages[self::NOT_VALID] = $this->message;
        }

        return $messages;
    }
}
