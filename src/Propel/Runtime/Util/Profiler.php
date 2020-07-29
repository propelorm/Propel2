<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Util;

use Propel\Common\Config\Exception\InvalidConfigurationException;

/**
 * Profiler for Propel
 */
class Profiler
{
    /**
     * @var float
     */
    protected $slowTreshold;

    /**
     * @var string
     */
    protected $innerGlue;

    /**
     * @var string
     */
    protected $outerGlue;

    /**
     * @var array
     */
    protected $snapshot = [];

    /**
     * @var array
     */
    protected $details = [
        'time' => [
            'name' => 'Time',
            'precision' => 3,
            'pad' => 8,
        ],
        'mem' => [
            'name' => 'Memory',
            'precision' => 3,
            'pad' => 8,
        ],
        'memDelta' => [
            'name' => 'Memory Delta',
            'precision' => 3,
            'pad' => 8,
        ],
        'memPeak' => [
            'name' => 'Memory Peak',
            'precision' => 3,
            'pad' => 8,
        ],
    ];

    /**
     * @param float $slowTreshold
     * @param string $innerGlue
     * @param string $outerGlue
     */
    public function __construct($slowTreshold = 0.1, $innerGlue = ': ', $outerGlue = ' | ')
    {
        $this->slowTreshold = $slowTreshold;
        $this->innerGlue = $innerGlue;
        $this->outerGlue = $outerGlue;
    }

    /**
     * Set the duration which triggers the 'slow' label on details.
     *
     * @param int $slowTreshold duration in seconds
     *
     * @return void
     */
    public function setSlowTreshold($slowTreshold)
    {
        $this->slowTreshold = $slowTreshold;
    }

    /**
     * Set the list of details to be included in a profile.
     *
     * @param array $details
     *
     * @return void
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * Set the inner glue for the details.
     *
     * @param string $innerGlue
     *
     * @return void
     */
    public function setInnerGlue($innerGlue)
    {
        $this->innerGlue = $innerGlue;
    }

    /**
     * Set the outer glue for the details.
     *
     * @param string $outerGlue
     *
     * @return void
     */
    public function setOuterGlue($outerGlue)
    {
        $this->outerGlue = $outerGlue;
    }

    /**
     * Configure the profiler from an array.
     *
     * @example
     * <code>
     * $profiler->setConfiguration(array(
     *   'slowTreshold' => 0.1,
     *   'details' => array(
     *       'time' => array(
     *           'name' => 'Time',
     *           'precision' => '3',
     *           'pad' => '8',
     *        ),
     *        'mem' => array(
     *            'name' => 'Memory',
     *            'precision' => '3',
     *            'pad' => '8',
     *        ),
     *        'memDelta' => array(
     *            'name' => 'Memory Delta',
     *            'precision' => '3',
     *            'pad' => '8',
     *        ),
     *        'memPeak' => array(
     *            'name' => 'Memory Peak',
     *            'precision' => '3',
     *            'pad' => '8',
     *        ),
     *   ),
     *   'outerGlue' => ': ',
     *   'innerGlue' => ' | '
     * ));
     * </code>
     *
     * @param array $profilerConfiguration
     *
     * @return void
     */
    public function setConfiguration($profilerConfiguration)
    {
        if (isset($profilerConfiguration['slowTreshold'])) {
            $this->setSlowTreshold($profilerConfiguration['slowTreshold']);
        }
        if (isset($profilerConfiguration['details'])) {
            $this->setDetails($profilerConfiguration['details']);
        }
        if (isset($profilerConfiguration['innerGlue'])) {
            $this->setInnerGlue($profilerConfiguration['innerGlue']);
        }
        if (isset($profilerConfiguration['outerGlue'])) {
            $this->setOuterGlue($profilerConfiguration['outerGlue']);
        }
    }

    /**
     * Get an array representing the configuration of the profiler.
     *
     * This array can be used as an input for self::setConfiguration().
     *
     * @return array
     */
    public function getConfiguration()
    {
        return [
            'slowTreshold' => $this->slowTreshold,
            'details' => $this->details,
            'innerGlue' => $this->innerGlue,
            'outerGlue' => $this->outerGlue,
        ];
    }

    /**
     * @return void
     */
    public function start()
    {
        $this->snapshot = self::getSnapshot();
    }

    /**
     * @return bool
     */
    public function isSlow()
    {
        return microtime(true) - $this->snapshot['microtime'] > $this->slowTreshold;
    }

    /**
     * @return string
     */
    public function getProfile()
    {
        return $this->getProfileBetween($this->snapshot, self::getSnapshot());
    }

    /**
     * Returns a string that may be prepended to a log line, containing debug information
     * according to the current configuration.
     *
     * Uses two debug snapshots to calculate how much time has passed since the call to
     * self::start(), how much the memory consumption by PHP has changed etc.
     *
     * @see self::getSnapshot()
     *
     * @param array $startSnapshot A snapshot, as returned by self::getSnapshot().
     * @param array $endSnapshot A snapshot, as returned by self::getSnapshot().
     *
     * @throws \Propel\Common\Config\Exception\InvalidConfigurationException
     *
     * @return string
     */
    public function getProfileBetween($startSnapshot, $endSnapshot)
    {
        $profile = '';

        if ($this->slowTreshold) {
            if ($endSnapshot['microtime'] - $startSnapshot['microtime'] >= $this->slowTreshold) {
                $profile .= 'SLOW ';
            } else {
                $profile .= '     ';
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
                    throw new InvalidConfigurationException("`$detailName` isn't a valid profiler key (Section: propel.runtime.profiler).");
            }
            $profile .= $config['name'] . $this->innerGlue . str_pad($value, $config['pad'], ' ', STR_PAD_LEFT) . $this->outerGlue;
        }

        return $profile;
    }

    /**
     * Get a snapshot of the current time and memory consumption.
     *
     * @return array
     */
    public static function getSnapshot()
    {
        return [
            'microtime' => microtime(true),
            'memoryUsage' => memory_get_usage(),
            'memoryPeakUsage' => memory_get_peak_usage(),
        ];
    }

    /**
     * Format a byte count into a human-readable representation.
     *
     * @param int|float $bytes Byte count to convert. Can be negative.
     * @param int $precision How many decimals to include.
     *
     * @return string
     */
    public static function formatMemory($bytes, $precision = 3)
    {
        $absBytes = abs($bytes);
        $sign = ($bytes == $absBytes) ? 1 : -1;
        $suffix = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $total = count($suffix);

        for ($i = 0; $absBytes > 1024 && $i < $total; $i++) {
            $absBytes /= 1024;
        }

        return self::toPrecision($sign * $absBytes, $precision) . $suffix[$i];
    }

    /**
     * Format a duration into a human-readable representation.
     *
     * @param float $duration Duration to format, in seconds.
     * @param int $precision How many decimals to include.
     *
     * @return string
     */
    public static function formatDuration($duration, $precision = 3)
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
     * @param float|int $number Value to round
     * @param int $significantFigures Number of significant figures
     *
     * @return string
     */
    public static function toPrecision($number, $significantFigures = 3)
    {
        if ((float)$number === 0.0) {
            return '0';
        }

        $significantDecimals = (int)floor($significantFigures - log10(abs($number)));
        $magnitude = pow(10, $significantDecimals);
        $shifted = round($number * $magnitude);

        return number_format($shifted / $magnitude, $significantDecimals);
    }
}
