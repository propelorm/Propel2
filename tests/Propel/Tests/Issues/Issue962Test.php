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
 * Regression test for https://github.com/propelorm/Propel2/issues/962
 *
 * @group mysql
 * @group database
 */
class Issue962Test extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if (!class_exists('\Timing')) {
            $schema = '
            <database name="issue_962" defaultIdMethod="native">
                <table name="timing" reloadOnInsert="true" reloadOnUpdate="true">
                    <column name="id" primaryKey="true" type="INTEGER" />
                    <column name="date_time" type="TIMESTAMP" />
                </table>
            </database>
            ';
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testTimeStampIsStoredCorrectly()
    {
        $dateTimeString = "2015-07-02T15:26:00Z";

        $refDateTime = new \DateTime($dateTimeString);

        $test1 = new \Timing();

        $test1
            ->setId(1)
            ->setDateTime($refDateTime);

        $this->asserttrue($test1->isModified(), "The datetime has been set the first time and need to be modified!");

        $test1->save();

        $gotDateTime1 = $test1->getDateTime();

        $this->assertequals(
            $refDateTime->getTimestamp(), $gotDateTime1->getTimestamp(),
            'The DateTime value which was persisted does not match the value later retrieved!');

        $refDateTime->setTimezone(new \DateTimeZone('-0600'));

        $test2 = new \Timing();
        $test2
            ->setId(2)
            ->setDateTime($refDateTime);

        $this->asserttrue($test2->isModified(), "The datetime has been set the first time and need to be modified!");
        
        $test2->save();

        $gotDateTime2 = $test2->getDateTime();

        $this->assertequals(
            $refDateTime->getTimestamp(), $gotDateTime2->getTimestamp(),
            'The DateTime value which was persisted does not match the value later retrieved!');
    }
}
