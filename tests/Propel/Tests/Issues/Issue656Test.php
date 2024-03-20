<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Issue656TestObject;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\TestCase;

/**
 * Regression test for https://github.com/propelorm/Propel2/issues/656
 */
class Issue656Test extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\Issue656TestObject')) {
            $schema = <<<EOF
<database>
    <table name="issue_656_test_object">
        <column name="ID" type="INTEGER" size="10" sqlType="int(10) unsigned" primaryKey="true" required="true" autoIncrement="true"/>
        <column name="Name" type="VARCHAR" size="45" required="true"/>
    </table>
    <table name="issue_656_test_object_from" isCrossRef="true">
        <column name="From" type="INTEGER" size="10" sqlType="int(10) unsigned" primaryKey="true" required="true"/>
        <column name="To" type="INTEGER" size="10" sqlType="int(10) unsigned" primaryKey="true" required="true"/>
        <foreign-key name="fk_test_object_from" foreignTable="issue_656_test_object">
            <reference local="From" foreign="ID"/>
        </foreign-key>
        <foreign-key name="fk_test_object_to" foreignTable="issue_656_test_object">
            <reference local="To" foreign="ID"/>
        </foreign-key>
    </table>
</database>
EOF;
            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            $builder->buildClasses(null, true);
        }
    }

    /**
     * @return void
     */
    public function testGetGetterRelatedBy()
    {
        $objectA = new Issue656TestObject();
        $objectA->setName('A');

        $objectB = new Issue656TestObject();
        $objectB->setName('B');

        $collection = new ObjectCollection();
        $collection->push($objectB);

        $objectA->setIssue656TestObjectsRelatedByTo($collection);

        $this->assertEquals($collection, $objectA->getIssue656TestObjectsRelatedByTo());
    }
}
