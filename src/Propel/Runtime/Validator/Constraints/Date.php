<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Date as SymfonyDateConstraint;

class Date extends SymfonyDateConstraint
{
    /**
     * @var string
     */
    public $column = '';

    /**
     * @param string|null $message
     * @param string|null $column
     * @param array|null $groups
     * @param mixed $payload
     */
    public function __construct(?string $message = null, ?string $column = null, ?array $groups = null, $payload = null)
    {
        parent::__construct(null, $message, $groups, $payload);

        $this->column = $column ?? $this->column;
    }
}
