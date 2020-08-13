<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Foo\MyClassWithInterface;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

class GeneratedObjectWithInterfaceTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('Foo\MyClassWithInterface')) {
              $schema = <<<EOF
<database name="a-database" namespace="Foo">
    <table name="my_class_with_interface" interface="MyInterface">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="name" type="VARCHAR"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testClassHasInterface()
    {
          $this->assertInstanceOf('Foo\MyInterface', new MyClassWithInterface());
    }
}
