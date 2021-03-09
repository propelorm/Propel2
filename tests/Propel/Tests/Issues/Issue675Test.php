<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCaseFixtures;

/**
 * Test : Propel should not allow incomplete foreign key references when foreign table has a composite primary key
 * Issue described in https://github.com/propelorm/Propel2/issues/675.
 * Originally described in http://stackoverflow.com/questions/7947085/are-incomplete-key-references-in-propel-useful
 *
 * @group model
 */
class Issue675Test extends TestCaseFixtures
{
    /**
     * Test incomplete foreign key references
     *
     * @return void
     */
    public function testIncompleteForeignReference()
    {
        $this->expectException(BuildException::class);

        $schema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="test" defaultIdMethod="native">
    <table name="event">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="name" type="varchar" size="50" required="true"/>
        <column name="organiser_id" type="integer" required="true"/>
        <!-- This FK is incomplete -->
        <foreign-key foreignTable="organiser">
            <reference local="organiser_id" foreign="id"/>
        </foreign-key>
    </table>

    <table name="organiser">
        <!-- Has composite PK -->
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="secondary" type="integer" required="true" primaryKey="true"/>
        <column name="name" type="varchar" size="50" required="true"/>
    </table>
</database>
EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses(null, true);

        // Propel should disallow incomplete foreign reference
        $this->expectException('\Propel\Generator\Exception\BuildException');

        // Explanation
        $this->fail('Expected to throw a BuildException due to incomplete foreign key reference.');
    }
}
