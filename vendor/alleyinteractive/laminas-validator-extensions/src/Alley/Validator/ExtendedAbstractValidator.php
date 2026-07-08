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

use Laminas\Validator\AbstractValidator;

abstract class ExtendedAbstractValidator extends AbstractValidator
{
    final public function isValid($value): bool
    {
        $this->setValue($value);
        $this->testValue($this->value);
        return \count($this->getMessages()) === 0;
    }

    /**
     * Apply validation logic and add any validation errors.
     *
     * @param mixed $value
     * @return void
     */
    abstract protected function testValue($value): void;
}
