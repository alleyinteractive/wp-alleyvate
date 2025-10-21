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

use Laminas\Validator\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface;

final class DivisibleBy extends ExtendedAbstractValidator
{
    public const NOT_DIVISIBLE_BY = 'notDivisibleBy';

    protected $messageTemplates = [
        self::NOT_DIVISIBLE_BY => 'Must be evenly divisible by %divisor% but %value% is not.',
    ];

    protected $messageVariables = [
        'divisor' => ['options' => 'divisor'],
    ];

    protected $options = [
        'divisor' => 1,
    ];

    protected function testValue($value): void
    {
        $value = (int) $value;
        $actual = $value % $this->options['divisor'];

        if ($actual !== 0) {
            $this->error(self::NOT_DIVISIBLE_BY);
        }
    }

    protected function setDivisor($divisor)
    {
        $divisor = (int) $divisor;

        if ($divisor === 0) {
            throw new InvalidArgumentException("Invalid 'divisor': {$divisor}");
        }

        $this->options['divisor'] = $divisor;
    }
}
