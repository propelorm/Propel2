<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Util;

/**
* Profiler for Propel
*/
class Profiler
{
    protected $slowTreshold = 1;
    protected $details = array();
    protected $innerGlue = ': ';
    protected $outerGlue = ' | ';

    protected $snapshot;

    public function __construct($details = null)
    {
        if (null === $details) {
            $details = array(
                'time' => array(
                    'name'      => 'Time',
                    'precision' => 3,
                    'pad'       => 5
                ),
                'mem' => array(
                    'name'      => 'Memory',
                    'precision' => 3,
                    'pad'       => 5
                ),
                'memDelta' => array(
                    'name'      => 'Delta',
                    'precision' => 3,
                    'pad'       => 5
                ),
                'memPeak' => array(
                    'name'      => 'Peak',
                    'precision' => 3,
                    'pad'       => 5
                )
            );
        }
        $this->details = $details;
    }

    /**
     * Set the duration which triggers the 'slow' label on details.
     *
     * @param integer $slowTreshold duration in seconds
     */
    public function setSlowTreshold($slowTreshold)
    {
        $this->slowTreshold = $slowTreshold;
    }

    /**
     * Set the inner glue for the details.
     *
     * @param string $innerGlue
     */
    public function setInnerGlue($innerGlue)
    {
        $this->innerGlue = $innerGlue;
    }

    /**
     * Set the outer glue for the details.
     *
     * @param string $outerGlue
     */
    public function setOuterGlue($outerGlue)
    {
        $this->outerGlue = $outerGlue;
    }

    public function start()
    {
        $this->snapshot = self::getSnapshot();
    }

    public function getProfile()
    {
        return $this->getDetails($this->snapshot, self::getSnapshot());
    }

    /**
     * Returns a prefix that may be prepended to a log line, containing debug information according
     * to the current configuration.
     *
     * Uses a given $debugSnapshot to calculate how much time has passed since the call to self::getDebugSnapshot(),
     * how much the memory consumption by PHP has changed etc.
     *
     * @see       self::getDebugSnapshot()
     *
     * @param     string  $methodName  Name of the method whose execution is being logged.
     * @param     array   $debugSnapshot  A previous return value from self::getDebugSnapshot().
     *
     * @return    string
     */
    public function getDetails($startSnapshot, $endSnapshot)
    {
        $details = '';

        if ($this->slowTreshold) {
            if ($endSnapshot['microtime'] - $startSnapshot['microtime'] >= $this->slowTreshold) {
                $details .= 'SLOW ';
            } else {
                $details .= '     ';
            }
        }

        // Iterate through each detail that has been configured to be enabled
        foreach ($this->details as $detailName => $config) {
            switch ($detailName) {
                case 'time':
                    $value = self::formatDuration($endSnapshot['microtime'] - $startSnapshot['microtime'], $config['precision']);
                    break;
                case 'mem':
                    $value = self::formatMemory($endSnapshot['memoryUsage'], $config['precision']);
                    break;
                case 'memDelta':
                    $value = $endSnapshot['memoryUsage'] - $startSnapshot['memoryUsage'];
                    $value = ($value > 0 ? '+' : '') . self::formatMemory($value, $config['precision']);
                    break;
                case 'memPeak':
                    $value = self::formatMemory($endSnapshot['memoryPeakUsage'], $config['precision']);
                    break;
                default:
                    $value = 'n/a';
                    break;
            }
            $details .= $config['name'] . $this->innerGlue . str_pad($value, $config['pad'], ' ', STR_PAD_LEFT) . $this->outerGlue;

        }

        return $details;
    }

    /**
     * Get a snapshot of the current time and memory consumption.
     *
     * @return    array
     */
    public static function getSnapshot()
    {
        return array(
            'microtime'       => microtime(true),
            'memoryUsage'     => memory_get_usage(),
            'memoryPeakUsage' => memory_get_peak_usage(),
        );
    }

    /**
     * Format a byte count into a human-readable representation.
     *
     * @param     integer  $bytes  Byte count to convert. Can be negative.
     * @param     integer  $precision  How many decimals to include.
     *
     * @return    string
     */
    static public function formatMemory($bytes, $precision = 3)
    {
        $absBytes = abs($bytes);
        $sign = ($bytes == $absBytes) ? 1 : -1;
        $suffix = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $total = count($suffix);

        for ($i = 0; $absBytes > 1024 && $i < $total; $i++) {
            $absBytes /= 1024;
        }

        return self::toPrecision($sign * $absBytes, $precision) . $suffix[$i];
    }

    /**
     * Format a duration into a human-readable representation.
     *
     * @param     double   $duration   Duration to format, in seconds.
     * @param     integer  $precision  How many decimals to include.
     *
     * @return    string
     */
    static public function formatDuration($duration, $precision = 3)
    {
        if ($duration < 1) {
            $duration *= 1000;
            $unit = 'ms';
        } else {
            $unit = 's ';
        }

        return self::toPrecision($duration, $precision) . $unit;
    }

   /**
     * Rounding to significant digits (sort of like JavaScript's toPrecision()).
     *
     *
     * @param float   $number             Value to round
     * @param integer $significantFigures Number of significant figures
     *
     * @return float
     */
    static public function toPrecision($number, $significantFigures = 3)
    {
        if ($number == 0) {
            return 0;
        }
        $significantDecimals = floor($significantFigures - log10(abs($number)));
        $magnitude = pow(10, $significantDecimals);
        $shifted = round($number * $magnitude);

        return number_format($shifted / $magnitude, $significantDecimals);
    }
}
