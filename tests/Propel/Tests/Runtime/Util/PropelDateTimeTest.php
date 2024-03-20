<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Util;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Test for DateTime subclass to support serialization.
 *
 * @author Alan Pinstein
 * @author Soenke Ruempler
 */
class PropelDateTimeTest extends TestCase
{
    /**
     * Assert that two dates are identical (equal and have same time zone).
     *
     * @return void
     */
    protected function assertDatesIdentical(DateTime $dt1, DateTime $dt2, $msg = 'Expected DateTime1 IDENTICAL to DateTime2: %s')
    {
        $this->assertEquals($dt1->format('Y-m-d H:i:s'), $dt1->format('Y-m-d H:i:s'), sprintf($msg, 'Dates w/ no timezone resolution were not the same.'));
        $this->assertEquals($dt1->getTimeZone()->getName(), $dt2->getTimeZone()->getName(), sprintf($msg, 'timezones were not the same.'));

        // We do this last, because a PHP bug will make this true while the dates
        // may not truly be equal.
        // See: http://bugs.php.net/bug.php?id=40743
        $this->assertTrue($dt1 == $dt2, sprintf($msg, 'dates did not pass equality check (==).'));
    }

    /**
     * Assert that two dates are equal.
     *
     * @return void
     */
    protected function assertDatesEqual(DateTime $dt1, DateTime $dt2, $msg = 'Expected DateTime1 == DateTime2: %s')
    {
        if ($dt1 != $dt2) {
            if ($dt1->getTimeZone()->getName() != $dt2->getTimeZone()->getName()) {
                $this->fail(sprintf($msg, 'Timezones were not the same.'));
            } else {
                $this->fail(sprintf($msg, 'Timezones were the same, but date values were different.'));
            }
        }
    }

    /**
     * Assert that two dates are not equal.
     *
     * @return void
     */
    protected function assertDatesNotEqual(DateTime $dt1, DateTime $dt2, $msg = 'Expected DateTime1 != DateTime2: %s')
    {
        $this->assertTrue($dt1 != $dt2, $msg);
    }

    /**
     * Ensure that our constructor matches DateTime constructor signature.
     *
     * @return void
     */
    public function testConstruct()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM has issues with overwritten DateTime classes. facebook/hhvm#1960');
        }

        // Because of a PHP bug ()
        // we cannot use a timestamp format that includes a timezone.   It gets weird. :)
        $now = date('Y-m-d H:i:s');

        $dt = new DateTime($now);
        $pdt = new PropelDateTime($now);
        $this->assertDatesEqual($dt, $pdt, 'Expected DateTime == PropelDateTime: %s');

        $dt = new DateTime($now, new DateTimeZone('UTC'));
        $pdt = new PropelDateTime($now, new DateTimeZone('America/New_York'));
        $this->assertDatesNotEqual($dt, $pdt, 'Expected DateTime != PropelDateTime: %s');
    }

    /**
     * Tests the ability to serialize() a PropelDateTime object.
     *
     * @return void
     */
    public function testSerialize_NoTZ()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM has issues with overwritten DateTime classes. facebook/hhvm#1960');
        }

        $now = date('Y-m-d H:i:s');
        $dt = new DateTime($now);
        $pdt = new PropelDateTime($now);

        $this->assertDatesIdentical($dt, $pdt);

        // We expect these to be the same -- there's no time zone info
        $ser = serialize($pdt);
        unset($pdt);

        $pdt = unserialize($ser);
        $this->assertDatesIdentical($dt, $pdt);
    }

    /**
     * Tests the ability to serialize() a PropelDateTime object.
     *
     * @return void
     */
    public function testSerialize_SameTZ()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM has issues with overwritten DateTime classes. facebook/hhvm#1960');
        }

        $now = date('Y-m-d H:i:s');
        $dt = new DateTime($now, new DateTimeZone('America/New_York'));
        $pdt = new PropelDateTime($now, new DateTimeZone('America/New_York'));

        $this->assertDatesIdentical($dt, $pdt);

        // We expect these to be the same -- there's no time zone info
        $ser = serialize($pdt);
        unset($pdt);

        $pdt = unserialize($ser);
        $this->assertDatesIdentical($dt, $pdt);
    }

    /**
     * Tests the ability to serialize() a PropelDateTime object.
     *
     * @return void
     */
    public function testSerialize_DiffTZ()
    {
        $now = date('Y-m-d H:i:s');
        $dt = new DateTime($now, new DateTimeZone('UTC'));
        $pdt = new PropelDateTime($now, new DateTimeZone('America/New_York'));

        $this->assertDatesNotEqual($dt, $pdt);

        // We expect these to be the same -- there's no time zone info
        $ser = serialize($pdt);
        unset($pdt);

        $pdt = unserialize($ser);
        $this->assertDatesNotEqual($dt, $pdt);
    }

    /**
     * @dataProvider provideValidNewInstanceValues
     *
     * @return void
     */
    public function testNewInstance($value, $expected)
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $dt = PropelDateTime::newInstance($value);
        $this->assertEquals($expected, $dt->format('Y-m-d H:i:s'));

        date_default_timezone_set($originalTimezone);
    }

    /**
     * @dataProvider provideValidNewInstanceValuesGmt1
     *
     * @return void
     */
    public function testNewInstanceGmt1($value, $expected)
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        $dt = PropelDateTime::newInstance($value);
        $this->assertEquals($expected, $dt->format('Y-m-d H:i:s'));

        date_default_timezone_set($originalTimezone);
    }

    /**
     * @return void
     */
    public function testNewInstanceInvalidValue()
    {
        $this->expectException(PropelException::class);

        $dt = PropelDateTime::newInstance('some string');
    }

    public function provideValidNewInstanceValues()
    {
        return [
            'Y-m-d' => ['2011-08-10', '2011-08-10 00:00:00'],
            // 1312960848 : Wed, 10 Aug 2011 07:20:48 GMT
            'unix_timestamp' => ['1312960848', '2011-08-10 07:20:48'],
            'Y-m-d H:is' => ['2011-08-10 10:22:15', '2011-08-10 10:22:15'],
            'Ymd' => ['20110810', '2011-08-10 00:00:00'],
            'Ymd2' => ['20110720', '2011-07-20 00:00:00'],
            'datetime_object' => [new DateTime('2011-08-10 10:23:10'), '2011-08-10 10:23:10'],
            'datetimeimmutable_object' => [new DateTimeImmutable('2011-08-10 10:23:10'), '2011-08-10 10:23:10'],
        ];
    }

    public function provideValidNewInstanceValuesGmt1()
    {
        return [
            // "1312960848" : Wed, 10 Aug 2011 07:20:48 GMT
            // "2011-08-10 09:20:48" : GMT+1 DST (= GMT +2)
            'unix_timestamp' => ['1312960848', '2011-08-10 09:20:48'],
            // "1323517115" : Sat, 10 Dec 2011 11:38:35 GMT
            // "2011-12-10 12:38:35" : GMT +1
            'unix_timestamp2' => ['1323517115', '2011-12-10 12:38:35'],
        ];
    }

    /**
     * @return void
     */
    public function testIsTimestamp()
    {
        $this->assertEquals(false, TestPropelDateTime::isTimestamp('20110325'));
        $this->assertEquals(true, TestPropelDateTime::isTimestamp(1319580000));
        $this->assertEquals(true, TestPropelDateTime::isTimestamp('1319580000'));
        $this->assertEquals(false, TestPropelDateTime::isTimestamp('2011-07-20 00:00:00'));
    }

    /**
     * @return void
     */
    public function testCreateHighPrecision()
    {
        $createHP = PropelDateTime::createHighPrecision();
        $this->assertInstanceOf(DateTime::class, $createHP);

        setlocale(LC_ALL, 'de_DE.UTF-8');
        $createHP = PropelDateTime::createHighPrecision();
        $this->assertInstanceOf(DateTime::class, $createHP);
    }

    /**
     * @return void
     */
    public function testCreateHighPrecisioniTz()
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        $createHP = PropelDateTime::createHighPrecision(PropelDateTime::getMicrotime());

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('America/New_York'));

        $this->assertEquals(date_timezone_get($dt), date_timezone_get($createHP));

        date_default_timezone_set($originalTimezone);
    }
}

class TestPropelDateTime extends PropelDateTime
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function isTimestamp($value): bool
    {
        return parent::isTimestamp($value);
    }
}
