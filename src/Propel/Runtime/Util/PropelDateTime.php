<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Util;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Propel\Runtime\Exception\PropelException;

/**
 * DateTime subclass which supports serialization.
 *
 * @author Alan Pinstein
 * @author Soenke Ruempler
 * @author Hans Lellelid
 */
class PropelDateTime extends DateTime
{
    /**
     * A string representation of the date, for serialization.
     *
     * @var string
     */
    private $dateString;

    /**
     * A string representation of the time zone, for serialization.
     *
     * @var string
     */
    private $tzString;

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected static function isTimestamp($value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        if (strlen((string)$value) === 8) {
            return false;
        }

        $stamp = strtotime((string)$value);
        if ($stamp === false) {
            return true;
        }

        $month = (int)date('m', (int)$value);
        $day = (int)date('d', (int)$value);
        $year = (int)date('Y', (int)$value);

        return checkdate($month, $day, $year);
    }

    /**
     * Creates a new DateTime object with milliseconds resolution.
     *
     * Usually `new \Datetime()` does not contain milliseconds so you need a method like this.
     *
     * @param string|null $time Optional, in seconds. Floating point allowed.
     *
     * @throws \InvalidArgumentException
     *
     * @return \DateTime
     */
    public static function createHighPrecision(?string $time = null): DateTime
    {
        $dateTime = DateTime::createFromFormat('U.u', $time ?: self::getMicrotime());
        if ($dateTime === false) {
            throw new InvalidArgumentException('Cannot create a datetime object from `' . $time . '`');
        }

        $dateTime->setTimeZone(new DateTimeZone(date_default_timezone_get()));

        return $dateTime;
    }

    /**
     * Get the current microtime with milliseconds. Making sure that the decimal point separator is always ".", ignoring
     * what is set with the current locale. Otherwise, self::createHighPrecision would return false.
     *
     * @return string
     */
    public static function getMicrotime(): string
    {
        $mtime = microtime(true);

        return number_format($mtime, 6, '.', '');
    }

    /**
     * Factory method to get a DateTime object from a temporal input
     *
     * @param mixed $value The value to convert (can be a string, a timestamp, or another DateTime)
     * @param \DateTimeZone|null $timeZone (optional) timezone
     * @param string $dateTimeClass The class of the object to create, defaults to DateTime
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return mixed|null An instance of $dateTimeClass
     */
    public static function newInstance($value, ?DateTimeZone $timeZone = null, string $dateTimeClass = 'DateTime')
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }
        if (!$value) {
            // '' is seen as NULL for temporal objects
            // because DateTime('') == DateTime('now') -- which is unexpected
            return null;
        }

        try {
            $dateTimeObject = static::createDateTime($value, $timeZone, $dateTimeClass);
        } catch (Exception $e) {
            $value = var_export($value, true);

            throw new PropelException('Error parsing date/time value `' . $value . '`: ' . $e->getMessage(), 0, $e);
        }

        return $dateTimeObject;
    }

    /**
     * @param mixed $value
     * @param \DateTimeZone|null $timeZone
     * @param string $dateTimeClass
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected static function createDateTime($value, ?DateTimeZone $timeZone = null, string $dateTimeClass = 'DateTime')
    {
        if (static::isTimestamp($value)) { // if it's a unix timestamp
            $format = 'U';
            if (strpos($value, '.')) {
                //with milliseconds
                $format = 'U.u';
            }

            $dateTimeObject = DateTime::createFromFormat($format, $value, new DateTimeZone('UTC'));
            if ($dateTimeObject === false) {
                throw new Exception(sprintf('Cannot create DateTime from format `%s`', $format));
            }

            // timezone must be explicitly specified and then changed
            // because of a DateTime bug: http://bugs.php.net/bug.php?id=43003
            $dateTimeObject->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        } else {
            if ($timeZone === null) {
                // stupid DateTime constructor signature
                $dateTimeObject = new $dateTimeClass($value);
            } else {
                $dateTimeObject = new $dateTimeClass($value, $timeZone);
            }
        }

        return $dateTimeObject;
    }

    /**
     * PHP "magic" function called when object is serialized.
     * Sets an internal property with the date string and returns properties
     * of class that should be serialized.
     *
     * @return array<string>
     */
    public function __sleep(): array
    {
        // We need to use a string without a time zone, due to
        // PHP bug: http://bugs.php.net/bug.php?id=40743
        $this->dateString = $this->format('Y-m-d H:i:s');
        $this->tzString = $this->getTimeZone()->getName();

        return ['dateString', 'tzString'];
    }

    /**
     * PHP "magic" function called when object is restored from serialized state.
     * Calls DateTime constructor with previously stored string value of date.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        // @TODO I don't think we can call the constructor from within this method
        parent::__construct($this->dateString, new DateTimeZone($this->tzString));
    }
}
