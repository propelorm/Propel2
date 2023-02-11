<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Generator\Model\Behavior;

class AddClassBehavior extends Behavior
{
    protected $additionalBuilders = ['Propel\Tests\Helpers\Bookstore\Behavior\AddClassBehaviorBuilder'];
}
