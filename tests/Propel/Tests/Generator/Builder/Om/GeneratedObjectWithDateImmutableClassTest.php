<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use DateTime;
use Foo\SomeTableA;
use Foo\SomeTableB;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

class GeneratedObjectWithDateImmutableClassTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
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
                        ],
                    ],
                ],
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

    /**
     * @return void
     */
    public function testDateTimeInterface()
    {
        $ModelA = new SomeTableA();
        $ModelA->setCreatedAt('today');
        $this->assertInstanceOf('\DateTimeInterface', $ModelA->getCreatedAt());

        $ModelB = new SomeTableB();
        $ModelB->setCreatedAt('today');
        $this->assertInstanceOf('\DateTimeInterface', $ModelB->getCreatedAt());
    }

    /**
     * @return void
     */
    public function testFieldTypes()
    {
        $ModelA = new SomeTableA();
        $ModelA->setCreatedAt('today');
        $this->assertInstanceOf('\DateTimeImmutable', $ModelA->getCreatedAt());

        $ModelB = new SomeTableB();
        $ModelB->setCreatedAt('today');
        $this->assertInstanceOf('\DateTime', $ModelB->getCreatedAt());
    }

    /**
     * @return void
     */
    public function testWithDateTimeToArrayWorks()
    {
        $Date = new DateTime('now');

        $ModelA = new SomeTableA();
        $ModelA->setCreatedAt(clone $Date);
        $this->assertSame(['Id' => null, 'CreatedAt' => $Date->format('Y-m-d H:i:s.u')], $ModelA->toArray());

        $ModelB = new SomeTableB();
        $ModelB->setCreatedAt(clone $Date);
        $this->assertSame(['Id' => null, 'CreatedAt' => $Date->format('Y-m-d H:i:s.u')], $ModelB->toArray());
    }
}
