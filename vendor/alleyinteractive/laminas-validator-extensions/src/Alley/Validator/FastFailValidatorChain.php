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

use Laminas\Validator\ValidatorChain;
use Laminas\Validator\ValidatorInterface;

final class FastFailValidatorChain implements ValidatorInterface
{
    private ValidatorChain $origin;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators)
    {
        $this->origin = new ValidatorChain();

        foreach ($validators as $validator) {
            $this->attach($validator);
        }
    }

    /**
     * Attach a validator to the end of the chain.
     *
     * @param ValidatorInterface $validator
     * @return self
     */
    public function attach(ValidatorInterface $validator)
    {
        $this->origin->attach($validator, true);

        return $this;
    }

    public function isValid($value): bool
    {
        return $this->origin->isValid($value);
    }

    public function getMessages(): array
    {
        return $this->origin->getMessages();
    }
}
