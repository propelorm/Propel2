<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Propel;
use \Propel\Tests\TestCase;

class GeneratedObjectWithInterfaceTest extends TestCase
{
      public function setUp()
      {
            if (!class_exists('Foo\MyClassWithInterface')) {
                  $schema = <<<EOF
<database name="a-database" namespace="Foo">
    <table name="my_class_with_interface" interface="MyInterface">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="name" type="VARCHAR" />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            }
      }

      public function testClassHasInterface()
      {
            $this->assertInstanceOf('Foo\MyInterface', new \Foo\MyClassWithInterface());
      }
}
