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
 *
 * @psalm-consistent-constructor (instantiated by class name in StandardServiceContainer without arguments)
 */
class Profiler
{
    /**
     * @var float
     */
    protected $slowThreshold;

    /**
     * @var string
     */
    protected $innerGlue;

    /**
     * @var string
     */
    protected $outerGlue;

    /**
     * @var array|null
     */
    protected $snapshot;

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
     * @param float $slowThreshold
     * @param string $innerGlue
     * @param string $outerGlue
     */
    public function __construct(float $slowThreshold = 0.1, string $innerGlue = ': ', string $outerGlue = ' | ')
    {
        $this->slowThreshold = $slowThreshold;
        $this->innerGlue = $innerGlue;
        $this->outerGlue = $outerGlue;
    }

    /**
     * Set the duration which triggers the 'slow' label on details.
     *
     * @param int $slowThreshold duration in seconds
     *
     * @return void
     */
    public function setSlowThreshold(int $slowThreshold): void
    {
        $this->slowThreshold = $slowThreshold;
    }

    /**
     * Set the list of details to be included in a profile.
     *
     * @param array $details
     *
     * @return void
     */
    public function setDetails(array $details): void
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
    public function setInnerGlue(string $innerGlue): void
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
    public function setOuterGlue(string $outerGlue): void
    {
        $this->outerGlue = $outerGlue;
    }

    /**
     * Configure the profiler from an array.
     *
     * @example
     * <code>
     * $profiler->setConfiguration(array(
     *   'slowThreshold' => 0.1,
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
    public function setConfiguration(array $profilerConfiguration): void
    {
        if (isset($profilerConfiguration['slowThreshold'])) {
            $this->setSlowThreshold($profilerConfiguration['slowThreshold']);
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
    public function getConfiguration(): array
    {
        return [
            'slowThreshold' => $this->slowThreshold,
            'details' => $this->details,
            'innerGlue' => $this->innerGlue,
            'outerGlue' => $this->outerGlue,
        ];
    }

    /**
     * @return void
     */
    public function start(): void
    {
        $this->snapshot = self::getSnapshot();
    }

    /**
     * @return bool
     */
    public function isSlow(): bool
    {
        return microtime(true) - $this->snapshot['microtime'] > $this->slowThreshold;
    }

    /**
     * @return string
     */
    public function getProfile(): string
    {
        $endSnapshot = self::getSnapshot();
        $startSnapshot = ($this->snapshot === null) ? $endSnapshot : $this->snapshot;
        $this->snapshot = null;

        return $this->getProfileBetween($startSnapshot, $endSnapshot);
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
    public function getProfileBetween(array $startSnapshot, array $endSnapshot): string
    {
        $profile = '';

        if ($this->slowThreshold) {
            if ($endSnapshot['microtime'] - $startSnapshot['microtime'] >= $this->slowThreshold) {
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
    public static function getSnapshot(): array
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
     * @param float|int $bytes Byte count to convert. Can be negative.
     * @param int $precision How many decimals to include.
     *
     * @return string
     */
    public static function formatMemory($bytes, int $precision = 3): string
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
    public static function formatDuration(float $duration, int $precision = 3): string
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
    public static function toPrecision($number, int $significantFigures = 3): string
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
