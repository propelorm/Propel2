<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om\Fixtures;

class ComplexColumnTypeEntityWithConstructor extends \MyNameSpace\Base\ComplexColumnTypeEntityWithConstructor
{
    public function __construct()
    {
        parent::__construct();

        $this->setTags(
            array('foo', 'bar')
        );
    }
}
