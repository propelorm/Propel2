<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers;

use Propel\Generator\Model\Behavior;

class MultipleBehavior extends Behavior
{
    /**
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return true;
    }
}
