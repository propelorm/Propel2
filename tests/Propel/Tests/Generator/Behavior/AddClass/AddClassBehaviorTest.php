<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\AddClass;

use Propel\Tests\Bookstore\Behavior\AddClassTableFooClass;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the generated classes by behaviors.
 *
 * @author Francois Zaninotto
 */
class AddClassBehaviorTest extends TestCaseFixtures
{
    /**
     * @return void
     */
    public function testClassExists()
    {
        $t = new AddClassTableFooClass();
        $this->assertTrue($t instanceof AddClassTableFooClass, 'behaviors can generate classes that are autoloaded');
    }
}
