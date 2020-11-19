<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCaseFixtures;

/**
 * Test : Propel should not allow incomplete foreign key references when foreign table has a composite primary key
 * Issue described in https://github.com/propelorm/Propel2/issues/675.
 * Originally described in http://stackoverflow.com/questions/7947085/are-incomplete-key-references-in-propel-useful
 *
 * @group model
 */
class Issue1659Test extends TestCaseFixtures
{
    /**
     * Test non-primary foreign key references
     * foreign table have composite primary key
     *
     * @return void
     */
    public function testFkNonPrimaryForeignCompositeReference()
    {
        $schema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="test" defaultIdMethod="native" namespace="FkNonPrimary">
    <table name="event">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="name" type="varchar" size="50" required="true"/>
        <column name="Event" type="varchar" size="50" required="true"/>
        <column name="organiser_id" type="integer" required="true"/>
        <!-- This FK is incomplete -->
        <foreign-key foreignTable="organiser" name="test_event_fk_Event_organiser_name">
            <reference local="Event" foreign="name"/>
        </foreign-key>
    </table>

    <table name="organiser">
        <!-- Has composite PK -->
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="secondary" type="integer" required="true" primaryKey="true"/>
        <column name="name" type="varchar" size="50" required="true"/>
        <unique>
            <unique-column name="name"/>
        </unique>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses(null, true);

        //should build without exception
        $this->assertTrue(class_exists('FkNonPrimary\Event'));
        $this->assertTrue(class_exists('FkNonPrimary\Organiser'));
    }

    /**
     * Test non-primary foreign key references
     * foreign table have single primary key
     *
     * @return void
     */
    public function testFkNonPrimaryForeignNonCompositeReference()
    {
        $schema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="test" defaultIdMethod="native" namespace="FkNonPrimaryFNonComposite">
    <table name="event">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="name" type="varchar" size="50" required="true"/>
        <column name="Event" type="varchar" size="50" required="true"/>
        <column name="organiser_id" type="integer" required="true"/>
        <!-- This FK is incomplete -->
        <foreign-key foreignTable="organiser" name="test_event_fk_Event_organiser_name">
            <reference local="Event" foreign="name"/>
        </foreign-key>
    </table>

    <table name="organiser">
        <!-- Has composite PK -->
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="name" type="varchar" size="50" required="true"/>
        <unique>
            <unique-column name="name"/>
        </unique>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses(null, true);

        //should build without exception
        $this->assertTrue(class_exists('FkNonPrimaryFNonComposite\Event'));
        $this->assertTrue(class_exists('FkNonPrimaryFNonComposite\Organiser'));
    }
}
