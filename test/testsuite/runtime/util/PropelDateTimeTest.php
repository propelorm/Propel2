<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/BaseTestCase.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/util/PropelDateTime.php';

/**
 * Test for DateTime subclass to support serialization.
 *
 * @author     Alan Pinstein
 * @author     Soenke Ruempler
 * @package    runtime.util
 */
class PropelDateTimeTest extends BaseTestCase
{

	/**
	 * Assert that two dates are identical (equal and have same time zone).
	 */
	protected function assertDatesIdentical(DateTime $dt1, DateTime $dt2, $msg = "Expected DateTime1 IDENTICAL to DateTime2: %s")
	{
		$this->assertEquals($dt1->format('Y-m-d H:i:s'), $dt1->format('Y-m-d H:i:s'), sprintf($msg, "Dates w/ no timezone resolution were not the same."));
		$this->assertEquals($dt1->getTimeZone()->getName(), $dt2->getTimeZone()->getName(), sprintf($msg, "timezones were not the same."));


		// We do this last, because a PHP bug will make this true while the dates
		// may not truly be equal.
		// See: http://bugs.php.net/bug.php?id=40743
		$this->assertTrue($dt1 == $dt2, sprintf($msg, "dates did not pass equality check (==)."));
	}

	/**
	 * Assert that two dates are equal.
	 */
	protected function assertDatesEqual(DateTime $dt1, DateTime $dt2, $msg = "Expected DateTime1 == DateTime2: %s")
	{
		if ($dt1 != $dt2) {
			if ($dt1->getTimeZone()->getName() != $dt2->getTimeZone()->getName()) {
				$this->fail(sprintf($msg, "Timezones were not the same."));
			} else {
				$this->fail(sprintf($msg, "Timezones were the same, but date values were different."));
			}
		}
	}

	/**
	 * Assert that two dates are not equal.
	 */
	protected function assertDatesNotEqual(DateTime $dt1, DateTime $dt2, $msg = "Expected DateTime1 != DateTime2: %s")
	{
		$this->assertTrue($dt1 != $dt2, $msg);
	}

	/**
	 * Ensure that our constructor matches DateTime constructor signature.
	 */
	public function testConstruct()
	{

		// Because of a PHP bug ()
		// we cannot use a timestamp format that includes a timezone.  It gets weird. :)
		$now = date('Y-m-d H:i:s');

		$dt = new DateTime($now);
		$pdt = new PropelDateTime($now);
		$this->assertDatesEqual($dt, $pdt, "Expected DateTime == PropelDateTime: %s");

		$dt = new DateTime($now, new DateTimeZone('UTC'));
		$pdt = new PropelDateTime($now, new DateTimeZone('America/New_York'));
		$this->assertDatesNotEqual($dt, $pdt, "Expected DateTime != PropelDateTime: %s");

	}

	/**
	 * Tests the ability to serialize() a PropelDateTime object.
	 */
	public function testSerialize_NoTZ()
	{
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
	 */
	public function testSerialize_SameTZ()
	{
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


}
