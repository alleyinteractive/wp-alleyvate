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

use Countable;
use Laminas\Validator\ValidatorInterface;
use ReturnTypeWillChange;

final class AnyValidator implements Countable, ValidatorInterface
{
    /**
     * Validator chain.
     *
     * @var ValidatorInterface[]
     */
    protected array $validators = [];

    /**
     * Array of validation failure messages.
     *
     * @var string[]
     */
    protected $messages = [];

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators)
    {
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
        $this->validators[] = $validator;

        return $this;
    }

    public function isValid($value)
    {
        $this->messages = [];

        if ($this->count() === 0) {
            // Consistent with `\Laminas\Validator\ValidatorChain()`.
            return true;
        }

        foreach ($this->validators as $validator) {
            if ($validator->isValid($value)) {
                return true;
            }
        }

        foreach ($this->validators as $validator) {
            $this->messages = array_replace($this->messages, $validator->getMessages());
        }

        return false;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    #[ReturnTypeWillChange]
    public function count()
    {
        return \count($this->validators);
    }
}
