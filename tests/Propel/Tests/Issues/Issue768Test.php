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
 * Regression test for https://github.com/propelorm/Propel2/issues/768
 *
 * @group database
 */
class Issue768Test extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if (!class_exists('\Timing')) {
            $schema = '
            <database name="issue_768" defaultIdMethod="native">
                <table name="timing">
                    <column name="id" primaryKey="true" type="INTEGER" />
                    <column name="date" type="DATE" />
                    <column name="time" type="TIME" />
                    <column name="date_time" type="TIMESTAMP" />
                </table>
            </database>
            ';
            QuickBuilder::buildSchema($schema);
        }
    }

    /*
     * Test if the time was set correctly and in which case it will be modified
     */
    public function testTimeModified()
    {
        $time = "17:37:19";

        $timeObject = new \DateTime($time);

        $test = new \Timing();

        $test
            ->setId(1)
            ->setTime($time)
        ;

        $this->asserttrue($test->isModified(), "The time has been set the first time and need to be modified!");

        $test->save();

        $test->setTime($time);

        $this->assertfalse($test->isModified(), "The time has been marked as modified, although it was provided with the same time string as before!");

        $timeObject->modify('+1 days');

        $test->setTime($timeObject);

        $this->assertfalse($test->isModified(), "The date was modified, but not the time!");

        $timeObject->modify('+7 hours');

        $test->setTime($timeObject);

        $this->asserttrue($test->isModified(), "The time has not been modified!");

        $test->save();

        $test->setTime(null);

        $this->asserttrue($test->isModified(), "The time has not been modified and not set to null!");

        $test->save();

        $this->assertnull($test->getTime(), "The time was not set to null!");

        $test->setTime($timeObject);

        $this->asserttrue($test->isModified(), "The time has not been modified!");

        $test->save();
    }

    /*
     * Test if the date was set correctly and in which case it will be modified
     */
    public function testDateModified()
    {
        $date = "2015-03-28";

        $dateObject = new \DateTime($date);

        $test = new \Timing();

        $test
            ->setId(2)
            ->setDate($date)
        ;

        $this->asserttrue($test->isModified(), "The date has been set the first time and need to be modified!");

        $test->save();

        $test->setDate($date);

        $this->assertfalse($test->isModified(), "The date has been marked as modified, although it was provided with the same date string as before!");

        $dateObject->modify('+4 hours');

        $test->setDate($dateObject);

        $this->assertfalse($test->isModified(), "The time was modified, but not the date!");

        $dateObject->modify('+7 days');

        $test->setDate($dateObject);

        $this->asserttrue($test->isModified(), "The date has been not modified!");

        $test->save();

        $test->setDate(null);

        $this->asserttrue($test->isModified(), "The date has been not modified and not set to null!");

        $test->save();

        $this->assertnull($test->getDate(), "The date was not set to null!");

        $test->setDate($dateObject);

        $this->asserttrue($test->isModified(), "The date has not been modified!");

        $test->save();
    }

    /*
     * Test if the time and date was set correctly and in which case it will be modified
     */
    public function testDateTimeModified()
    {
        $dateTime = "2015-09-27 17:37:19";

        $dateTimeObject = new \DateTime($dateTime);

        $test = new \Timing();

        $test
            ->setId(3)
            ->setDateTime($dateTime)
        ;

        $this->asserttrue($test->isModified(), "The datetime has been set the first time and need to be modified!");

        $test->save();

        $test->setDateTime($dateTime);

        $this->assertfalse($test->isModified(), "The datetime has been marked as modified, although it was provided with the same datetime string as before!");

        $dateTimeObject->modify('+4 hours');

        $test->setDateTime($dateTimeObject);

        $this->asserttrue($test->isModified(), "The time has been not modified!");

        $test->save();

        $dateTimeObject->modify('+7 days');

        $test->setDateTime($dateTimeObject);

        $this->asserttrue($test->isModified(), "The date has been not modified!");

        $test->save();

        $test->setDateTime(null);

        $this->asserttrue($test->isModified(), "The datetime has been not modified and not set to null!");

        $test->save();

        $this->assertnull($test->getDateTime(), "The datetime was not set to null!");

        $test->setDateTime($dateTimeObject);

        $this->asserttrue($test->isModified(), "The datetime has not been modified!");

        $test->save();
    }
}
