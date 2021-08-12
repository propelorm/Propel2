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
use Propel\Tests\TestCase;

/**
 * Regression test for https://github.com/propelorm/Propel2/issues/1420
 *
 * @group database
 */
class Issue1420Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('\Table1420A')) {
            $schema = <<<XML
            <!DOCTYPE database SYSTEM "../../../../resources/dtd/database.dtd">
            <database name="issue_1420" defaultIdMethod="native">
                <table name="table_1420_a">
                    <column name="id" primaryKey="true" type="INTEGER" />
                    <column name="a_field" type="VARCHAR" size="10" />
                </table>
                <table name="table_1420_b">
                    <column name="id" primaryKey="true" type="INTEGER" />
                    <column name="table_1420_a_id" type="INTEGER" />
                    <column name="b_field" type="VARCHAR" size="10" />

                    <foreign-key foreignTable="table_1420_a" name="table_1420_b_fk1">
                        <reference local="table_1420_a_id" foreign="id"/>
                    </foreign-key>
                </table>
                <table name="table_1420_c">
                    <column name="id" primaryKey="true" type="INTEGER" />
                    <column name="table_1420_a_id" type="INTEGER" />
                    <column name="c_field" type="VARCHAR" size="10" />

                    <foreign-key foreignTable="table_1420_a" name="table_1420_c_fk1">
                        <reference local="table_1420_a_id" foreign="id"/>
                    </foreign-key>
                </table>
            </database>
XML;
            QuickBuilder::buildSchema($schema);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Table1420CQuery::create()->deleteAll();
        \Table1420BQuery::create()->deleteAll();
        \Table1420AQuery::create()->deleteAll();
    }

    /*
     * Test whether hydration works properly for all the models
     */
    public function testValidHydration()
    {
        // Set up 3 models in relations A hasMany B, A hasMany C
        // A: id=1, a_field=a_value
        // B: id=2, b_field=b_value (referenced to A:1)
        // C: id=3, c_field=c_value (referenced to A:1)
        (new \Table1420A)->setId(1)->setAField('a_value')->save();
        (new \Table1420B)->setId(2)->setTable1420AId(1)->setBField('b_value')->save();
        (new \Table1420C)->setId(3)->setTable1420AId(1)->setCField('c_value')->save();

        // querying for A models together with B models hydrated
        $aQuery = (new \Table1420AQuery)->leftJoinWith('Table1420B');

        // merged criteria has with model and adds self columns (because it's primary criteria)
        $mergeWith = (new \Table1420AQuery)->leftJoinWith('Table1420C');

        // merging queries together results produces these columns in SELECT part:
        // A columns, B columns (base criteria), A columns again, C columns
        // "A columns again" causes the further models be hydrated with wrong data
        // (here C is hydrated partially from A columns)
        $aQuery->mergeWith($mergeWith);

        $a = $aQuery->find()->getFirst();

        $this->assertSame(1, $a->getId());
        $this->assertSame('a_value', $a->getAField());

        $b = $a->getTable1420Bs()->getFirst();
        $this->assertSame(2, $b->getId());
        $this->assertSame(1, $b->getTable1420AId());
        $this->assertSame('b_value', $b->getBField());

        $c = $a->getTable1420Cs()->getFirst();
        $this->assertSame(3, $c->getId());
        $this->assertSame(1, $c->getTable1420AId());
        $this->assertSame('c_value', $c->getCField());
    }
}
