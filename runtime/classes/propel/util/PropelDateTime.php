<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

/**
 * DateTime subclass which supports serialization.
 *
 * Currently Propel is not using this for storing date/time objects
 * within model objeects; however, we are keeping it in the repository
 * because it is useful if you want to store a DateTime object in a session.
 *
 * @author     Alan Pinstein
 * @author     Soenke Ruempler
 * @author     Hans Lellelid
 * @package    propel.util
 */
class PropelDateTime extends DateTime
{

	/**
	 * A string representation of the date, for serialization.
	 * @var        string
	 */
	private $dateString;

	/**
	 * A string representation of the time zone, for serialization.
	 * @var        string
	 */
	private $tzString;

	/**
	 * Convenience method to enable a more fluent API.
	 * @param      string $date Date/time value.
	 * @param      DateTimeZone $tz (optional) timezone
	 */
	public static function newInstance($date, DateTimeZone $tz = null)
	{
		if ($tz) {
			return new DateTime($date, $tz);
		} else {
			return new DateTime($date);
		}
	}

	/**
	 * PHP "magic" function called when object is serialized.
	 * Sets an internal property with the date string and returns properties
	 * of class that should be serialized.
	 * @return     array string[]
	 */
	function __sleep()
	{
		// We need to use a string without a time zone, due to
		// PHP bug: http://bugs.php.net/bug.php?id=40743
		$this->dateString = $this->format('Y-m-d H:i:s');
		$this->tzString = $this->getTimeZone()->getName();
		return array('dateString', 'tzString');
	}

	/**
	 * PHP "magic" function called when object is restored from serialized state.
	 * Calls DateTime constructor with previously stored string value of date.
	 */
	function __wakeup()
	{
		parent::__construct($this->dateString, new DateTimeZone($this->tzString));
	}

}
