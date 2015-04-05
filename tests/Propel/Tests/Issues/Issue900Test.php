<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Issues;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\Bookstore\DateTimeMicroseconds;
use Propel\Tests\Bookstore\DateTimeMicrosecondsQuery;
use Propel\Tests\Bookstore\Map\DateTimeMicrosecondsTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;


/**
 * Regression test for https://github.com/propelorm/Propel2/issues/900
 *
 * @group database
 */
class Issue900Test extends BookstoreEmptyTestBase
{
    public function testMicrosecondsPersisted()
    {
        $test = new DateTimeMicroseconds();

        // Insert date/time with microsecond, precision.
        $dtString = '2015-03-04 05:06:07.123456';
        $test->setId(1)
            ->setDateTime($dtString);
        $test->save();

        // assert that the date/time is stored correclty in the object.
        $dt1 = $test->getDateTime();
        $this->assertEquals($dt1->format('Y-m-d H:i:s.u'), $dtString);

        // unset object and clean cache.
        unset($test);
        DateTimeMicrosecondsTableMap::clearInstancePool();

        // retrieve previously stored row from the database.
        $test = \Propel\Tests\Bookstore\DateTimeMicrosecondsQuery::create()
            ->findPK(1);
        $dt2 = $test->getDateTime();

        // assert that the date/time is indeed with microsecond precision.
        $this->assertEquals($dt2->format('Y-m-d H:i:s.u'), $dtString);

        // cleanup
        DateTimeMicrosecondsQuery::create()->filterById(1)->delete();
    }
}

