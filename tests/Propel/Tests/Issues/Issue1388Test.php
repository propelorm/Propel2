<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Issues;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCaseFixtures;

/**
 * Test : Propel should allow marked to be skipped foreign key references on table with composite primary key
 * @group model
 */
class Issue1388Test extends TestCaseFixtures
{
    public function testSkippedIncompleteForeignReference()
    {
        $schema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="test" defaultIdMethod="native">
    <table name="event">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="50" required="true" />
        <column name="organiser_type_id" type="integer" required="true" />
        <!-- This FK is incomplete -->
        <foreign-key foreignTable="organiser" skipSql="true">
            <reference local="organiser_type_id" foreign="type_id" />
        </foreign-key>
    </table>

    <table name="organiser">
        <!-- Has composite PK -->
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="secondary" type="integer" required="true" primaryKey="true" />
        <column name="type_id" type="integer" required="true" />
        <column name="name" type="varchar" size="50" required="true" />
    </table>
</database>
EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        
        try {
            $builder->buildClasses(null, true);
            $noExceptionThrown = true;
        } catch (\Exception $e) {
            $noExceptionThrown = false;
        }

        $this->assertTrue($noExceptionThrown);

    }
}