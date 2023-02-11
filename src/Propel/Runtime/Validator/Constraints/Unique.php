<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Unique extends Constraint
{
    /**
     * @var string
     */
    public $message = 'This value is already stored in your database';

    /**
     * @var string
     */
    public $column = '';
}
