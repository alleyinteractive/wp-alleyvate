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
use Stringable;

abstract class FreeformValidator implements ValidatorInterface
{
    private array $messages = [];

    /**
     * Apply validation logic and add any validation errors.
     *
     * @param mixed $value
     * @return void
     */
    abstract protected function testValue($value): void;

    final public function isValid($value): bool
    {
        $this->messages = [];
        $this->testValue($value);
        return \count($this->getMessages()) === 0;
    }

    final public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Add validation error.
     *
     * @param string $key Error key.
     * @param string $message Message text.
     * @return void
     */
    final protected function error(string $key, $message): void
    {
        $this->messages[$key] = (string) $message;
    }
}
