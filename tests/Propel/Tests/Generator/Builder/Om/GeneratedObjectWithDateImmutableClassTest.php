<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

class GeneratedObjectWithDateImmutableClassTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('Foo\SomeTableA')) {
            $schema = <<<EOF
<database name="a_database" namespace="Foo">
    <table name="some_table_a">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="created_at" phpName="CreatedAt" type="TIMESTAMP"/>
    </table>
</database>
EOF;
            // Custom Configuration to use DateTimeImmutable
            $builder = new QuickBuilder();
            $config = new QuickGeneratorConfig([
                'propel' => [
                    'generator' => [
                        'dateTime' => [
                            'dateTimeClass' => 'DateTimeImmutable',
                        ]
                    ]
                ]
            ]);
            $builder->setSchema($schema);
            $builder->setConfig($config);
            $builder->build();
        }

        if (!class_exists('Foo\SomeTableB')) {
            $schema = <<<EOF
<database name="a_database" namespace="Foo">
    <table name="some_table_b">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="created_at" phpName="CreatedAt" type="TIMESTAMP"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testDateTimeInterface()
    {
        $ModelA = new \Foo\SomeTableA();
        $ModelA->setCreatedAt('today');
        $this->assertInstanceOf('\DateTimeInterface', $ModelA->getCreatedAt());

        $ModelB = new \Foo\SomeTableB();
        $ModelB->setCreatedAt('today');
        $this->assertInstanceOf('\DateTimeInterface', $ModelB->getCreatedAt());
    }

    public function testFieldTypes()
    {
        $ModelA = new \Foo\SomeTableA();
        $ModelA->setCreatedAt('today');
        $this->assertInstanceOf('\DateTimeImmutable', $ModelA->getCreatedAt());

        $ModelB = new \Foo\SomeTableB();
        $ModelB->setCreatedAt('today');
        $this->assertInstanceOf('\DateTime', $ModelB->getCreatedAt());
    }

    public function testWithDateTimeToArrayWorks()
    {
        $Date = new DateTime('now');

        $ModelA = new \Foo\SomeTableA();
        $ModelA->setCreatedAt(clone $Date);
        $this->assertSame(['Id' => null, 'CreatedAt' => $Date->format('c')], $ModelA->toArray());

        $ModelB = new \Foo\SomeTableB();
        $ModelB->setCreatedAt(clone $Date);
        $this->assertSame(['Id' => null, 'CreatedAt' => $Date->format('c')], $ModelB->toArray());
    }
}
