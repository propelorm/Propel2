<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testModifyTable()
    {
        $t = Table3TableMap::getTableMap();
        $this->assertTrue($t->hasColumn('test'), 'modifyTable hook is called when building the model structure');
    }
}
