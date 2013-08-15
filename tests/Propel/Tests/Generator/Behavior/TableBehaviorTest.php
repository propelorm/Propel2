<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Tests\Bookstore\Behavior\Map\Table3TableMap;
use Propel\Tests\TestCase;

/**
 * Tests the table structure behavior hooks.
 *
 * @author Francois Zaninotto
 */
class TableBehaviorTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testModifyTable()
    {
        $t = Table3TableMap::getTableMap();
        $this->assertTrue($t->hasColumn('test'), 'modifyTable hook is called when building the model structure');
    }
}
