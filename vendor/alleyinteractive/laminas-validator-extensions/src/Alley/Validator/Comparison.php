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

final class Comparison extends ExtendedAbstractValidator
{
    private const SUPPORTED_OPERATORS = [
        '==',
        '===',
        '!=',
        '<>',
        '!==',
        '<',
        '>',
        '<=',
        '>=',
    ];

    private const OPERATOR_ERROR_CODES = [
        '==' => 'notEqual',
        '===' => 'notIdentical',
        '!=' => 'isEqual',
        '<>' => 'isEqual',
        '!==' => 'isIdentical',
        '<' => 'notLessThan',
        '>' => 'notGreaterThan',
        '<=' => 'notLessThanOrEqualTo',
        '>=' => 'notGreaterThanOrEqualTo',
    ];

    protected $messageTemplates = [
        'notEqual' => 'Must be equal to %compared% but is %value%.',
        'notIdentical' => 'Must be identical to %compared% but is %value%.',
        'isEqual' => 'Must not be equal to %compared% but is %value%.',
        'isIdentical' => 'Must not be identical to %compared%.',
        'notLessThan' => 'Must be less than %compared% but is %value%.',
        'notGreaterThan' => 'Must be greater than %compared% but is %value%.',
        'notLessThanOrEqualTo' => 'Must be less than or equal to %compared% but is %value%.',
        'notGreaterThanOrEqualTo' => 'Must be greater than or equal to %compared% but is %value%.',
    ];

    protected $messageVariables = [
        'compared' => ['options' => 'compared'],
    ];

    protected $options = [
        'compared' => null,
        'operator' => '===',
    ];

    protected function testValue($value): void
    {
        switch ($this->options['operator']) {
            case '==':
                $result = $value == $this->options['compared'];
                break;
            case '!=':
            case '<>':
                $result = $value != $this->options['compared'];
                break;
            case '!==':
                $result = $value !== $this->options['compared'];
                break;
            case '<':
                $result = $value < $this->options['compared'];
                break;
            case '>':
                $result = $value > $this->options['compared'];
                break;
            case '<=':
                $result = $value <= $this->options['compared'];
                break;
            case '>=':
                $result = $value >= $this->options['compared'];
                break;
            case '===':
            default:
                $result = $value === $this->options['compared'];
                break;
        }

        if (!$result) {
            $this->error(self::OPERATOR_ERROR_CODES[$this->options['operator']]);
        }
    }

    protected function setOperator(string $operator)
    {
        if (!\in_array($operator, self::SUPPORTED_OPERATORS, true)) {
            throw new InvalidArgumentException("Invalid 'operator': {$operator}.");
        }

        $this->options['operator'] = $operator;
    }
}
