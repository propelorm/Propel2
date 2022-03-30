<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Fixtures\Generator\Builder\Om;

use MyNameSpace\Base\ComplexColumnTypeEntityWithConstructor as MyNameSpaceComplexColumnTypeEntityWithConstructor;

class ComplexColumnTypeEntityWithConstructor extends MyNameSpaceComplexColumnTypeEntityWithConstructor
{
    public function __construct()
    {
        parent::__construct();

        $this->setTags(
            ['foo', 'bar']
        );
    }
}
