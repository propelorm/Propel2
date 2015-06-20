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
 * Regression test for https://github.com/propelorm/Propel2/issues/949
 *
 * @group database
 */
class Issue949Test extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if (!class_exists('\Timing')) {
            $schema = '
            <database name="issue_949" defaultIdMethod="native">
                <table name="timing">
                    <column name="id" primaryKey="true" type="INTEGER" />
                    <column name="date_time" type="TIMESTAMP" />
                </table>
            </database>
            ';
            QuickBuilder::buildSchema($schema);
        }
    }

    /*
     * Test if the time and date was set correctly and in which case it will be modified
     */
    public function testDateTimeModified()
    {
        $dateTime = "2015-09-27 17:37:19";

        $test = new \Timing();

        $test
            ->setId(1)
            ->setDateTime($dateTime)
        ;

        $this->asserttrue($test->isModified(), "The datetime has been set for the first time and has to be marked as modified!");

        $test->save();

	    $dateTimeObject = $test->getDateTime();

        $test->setDateTime($dateTimeObject);

        $this->assertfalse($test->isModified(), "The datetime has been marked as modified, although it was provided with the same unchanged DateTime object we received when querying the column!");

        $dateTimeObject->modify('+4 hours');

        $test->setDateTime($dateTimeObject);

        $this->asserttrue($test->isModified(), "The time has not been marked as modified, although the DateTime object, which was passed previously and then saved has been changed!");

        $test->save();

        $dateTimeObject->modify('+7 days');

        $test->setDateTime($dateTimeObject);

        $this->asserttrue($test->isModified(), "The date has not been marked as modified, although the DateTime object, which was passed previously and then saved has been changed!");

        $test->save();

        $this->asserttrue($test->isModified(), "The datetime has not been marked as modified when setting it to null!");

        $test->save();

        $this->assertnull($test->getDateTime(), "The datetime has not been set to null!");

        $test->setDateTime($dateTimeObject);

        $this->asserttrue($test->isModified(), "The datetime has not been marked as modified, even though it was set to null before!");

        $test->save();
    }
}
