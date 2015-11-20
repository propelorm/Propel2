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
 * This test makes sure that DateTime as Primary Key can be inserted without a failure. It also covers that
 * the toArray() method of the ObjectCollection returns a valid array when a Date(time) object is used as a Primary Key.
 * For more information see https://github.com/propelorm/Propel2/issues/646
 *
 * @group database
 */
class Issue646Test extends TestCaseFixtures
{

    protected function setUp()
    {
        parent::setUp();
        if (!class_exists('\PkDate')) {
            $schema = '
            <database name="test" defaultIdMethod="native"
             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
           <table name="pk_date">
           <column name="created_at" type="DATE" primaryKey="true" />
           <column name="name" type="VARCHAR"/>
            </table>
            <table name="pk_time">
           <column name="created_at" type="TIME" primaryKey="true" />
           <column name="name" type="VARCHAR"/>
            </table>
            <table name="pk_timestamp">
           <column name="created_at" type="TIMESTAMP" primaryKey="true" />
           <column name="name" type="VARCHAR"/>
            </table>';
            QuickBuilder::buildSchema($schema);
        }

    }

    protected function tearDown()
    {
        parent::tearDown();
        \PkDateQuery::create()->deleteAll();
        \PkTimeQuery::create()->deleteAll();
        \PkTimestampQuery::create()->deleteAll();
    }

    public function testInsertRowWithPkDate()
    {
        //make sure that DateTime can be inserted when used as Primary Key
        $date = new \PkDate();
        $date->setName("First")
            ->setCreatedAt(new \DateTime('2014-01-01'));

        $time = new \PkTime();
        $time->setName("First")
            ->setCreatedAt(new \DateTime('20:00:10'));

        $timestamp = new \PkTimestamp();
        $timestamp->setName("First")
            ->setCreatedAt(new \DateTime('2014-01-01 20:00:10'));

        $this->assertEquals(1, $date->save());
        $this->assertEquals(1, $time->save());
        $this->assertEquals(1, $timestamp->save());
    }

    public function testToArrayWithPkDate()
    {
        //makes sure that ObjectCollection returns a valid array when Primar Key is a DateTime object.

        $date1 = new \PkDate();
        $date1->setName("First")
            ->setCreatedAt(new \DateTime('2014-01-01'))
            ->save();

        $date2 = new \PkDate();
        $date2->setName("Second")
            ->setCreatedAt(new \DateTime('2014-02-01'))
            ->save();

        $dates = \PkDateQuery::create()->find();

        $this->assertInternalType('array', $dates->toArray());

    }
}
