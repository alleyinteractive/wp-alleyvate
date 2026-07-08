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

use Laminas\Validator\Regex;
use Laminas\Validator\ValidatorInterface;

final class ValidatorByOperator implements ValidatorInterface
{
    private ValidatorInterface $final;

    public function __construct(string $operator, $param)
    {
        // Build validator now so that its constructor runs, just as if the validator had been instantiated directly.
        $this->final = $this->validator($operator, $param);
    }

    public function isValid($value)
    {
        return $this->final->isValid($value);
    }

    public function getMessages()
    {
        return $this->final->getMessages();
    }

    private function validator(string $operator, $param)
    {
        switch ($operator) {
            case 'CONTAINS':
            case 'NOT CONTAINS':
                $validator = new ContainsString([
                    'needle' => $param,
                    'ignoreCase' => false,
                ]);
                break;

            case 'IN':
            case 'NOT IN':
                $validator = new OneOf([
                    'haystack' => $param,
                ]);
                break;

            case 'LIKE':
            case 'NOT LIKE':
                $validator = new ContainsString([
                    'needle' => $param,
                    'ignoreCase' => true,
                ]);
                break;

            case 'REGEX':
            case 'NOT REGEX':
                $validator = new Regex([
                    'pattern' => $param,
                ]);
                break;

            default:
                $validator = new Comparison([
                    'operator' => $operator,
                    'compared' => $param,
                ]);
        }

        if (str_starts_with($operator, 'NOT ')) {
            $validator = new Not($validator, 'Invalid comparison.');
        }

        return $validator;
    }
}
