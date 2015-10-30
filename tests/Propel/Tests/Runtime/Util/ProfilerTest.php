<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Util;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Util\Profiler;

class ProfilerTest extends BaseTestCase
{
    public function testGetProfileBetweenAddsSlowTreshold()
    {
        $profiler = new Profiler();
        $profiler->setDetails([]);
        $profiler->setSlowTreshold(1000);
        $res = $profiler->getProfileBetween(['microtime' => 1000], ['microtime' => 1200]);
        $this->assertEquals('     ', $res);
        $res = $profiler->getProfileBetween(['microtime' => 1000], ['microtime' => 2200]);
        $this->assertEquals('SLOW ', $res);
    }

    public function testGetProfileBetweenDoesNotAddSlowTresholdWhenValueIsNull()
    {
        $profiler = new Profiler();
        $profiler->setDetails([]);
        $profiler->setSlowTreshold(0);
        $res = $profiler->getProfileBetween(['microtime' => 1000], ['microtime' => 1200]);
        $this->assertEquals('', $res);
        $res = $profiler->getProfileBetween(['microtime' => 1000], ['microtime' => 2200]);
        $this->assertEquals('', $res);
    }

    public function testGetProfileBetweenAddsTime()
    {
        $profiler = new Profiler();
        $profiler->setDetails(['time' => ['name' => 'Time', 'precision' => 3, 'pad' => 3]]);
        $profiler->setSlowTreshold(0);
        $res = $profiler->getProfileBetween(['microtime' => 1.000], ['microtime' => 1.234]);
        $this->assertEquals('Time: 234ms | ', $res);
        $res = $profiler->getProfileBetween(['microtime' => 1.234], ['microtime' => 2.345]);
        $this->assertEquals('Time: 1.11s  | ', $res);
    }

    public function testGetProfileBetweenAddsMemoryUsage()
    {
        $profiler = new Profiler();
        $profiler->setDetails(['mem' => ['name' => 'Memory', 'precision' => 3, 'pad' => 3]]);
        $profiler->setSlowTreshold(0);
        $res = $profiler->getProfileBetween([], ['memoryUsage' => 343245]);
        $this->assertEquals('Memory: 335kB | ', $res);
        $res = $profiler->getProfileBetween([], ['memoryUsage' => 73456345634]);
        $this->assertEquals('Memory: 68.4GB | ', $res);
    }

    public function testGetProfileBetweenAddsMemoryDeltaUsage()
    {
        $profiler = new Profiler();
        $profiler->setDetails(['memDelta' => ['name' => 'Delta', 'precision' => 3, 'pad' => 3]]);
        $profiler->setSlowTreshold(0);
        $res = $profiler->getProfileBetween(['memoryUsage' => 343245], ['memoryUsage' => 888064]);
        $this->assertEquals('Delta: +532kB | ', $res);
        $res = $profiler->getProfileBetween(['memoryUsage' => 234523523], ['memoryUsage' => 73456345634]);
        $this->assertEquals('Delta: +68.2GB | ', $res);
        $res = $profiler->getProfileBetween(['memoryUsage' => 73456345634], ['memoryUsage' => 234523523]);
        $this->assertEquals('Delta: -68.2GB | ', $res);
    }

    public function testGetProfileBetweenAddsMemoryPeakUsage()
    {
        $profiler = new Profiler();
        $profiler->setDetails(['memPeak' => ['name' => 'Peak', 'precision' => 3, 'pad' => 3]]);
        $profiler->setSlowTreshold(0);
        $res = $profiler->getProfileBetween([], ['memoryPeakUsage' => 343245]);
        $this->assertEquals('Peak: 335kB | ', $res);
        $res = $profiler->getProfileBetween([], ['memoryPeakUsage' => 73456345634]);
        $this->assertEquals('Peak: 68.4GB | ', $res);
    }

    public function testGetProfileBetweenCombinesDetails()
    {
        $profiler = new Profiler();
        $profiler->setDetails([
            'time'     => ['name' => 'Time', 'precision' => 3, 'pad' => 3],
            'mem'      => ['name' => 'Memory', 'precision' => 3, 'pad' => 3],
            'memDelta' => ['name' => 'Delta', 'precision' => 3, 'pad' => 3],
            'memPeak'  => ['name' => 'Peak', 'precision' => 3, 'pad' => 3],
        ]);
        $res = $profiler->getProfileBetween(
            ['microtime' => 1.234, 'memoryUsage' => 343245, 'memoryPeakUsage' => 314357],
            ['microtime' => 2.345, 'memoryUsage' => 888064, 'memoryPeakUsage' => 343245]
        );
        $this->assertEquals('SLOW Time: 1.11s  | Memory: 867kB | Delta: +532kB | Peak: 335kB | ', $res);
        $res = $profiler->getProfileBetween(
            ['microtime' => 1.000, 'memoryUsage' => 343245, 'memoryPeakUsage' => 314357],
            ['microtime' => 1.0345, 'memoryUsage' => 245643, 'memoryPeakUsage' => 343245]
        );
        $this->assertEquals('     Time: 34.5ms | Memory: 240kB | Delta: -95.3kB | Peak: 335kB | ', $res);
    }

    public function providerForTestFormatMemory()
    {
        return [
            [1234567890, number_format(1.15, 2) . 'GB'],
            [123456789.0, number_format(118) . 'MB'],
            [12345678.90, number_format(11.8, 1) . 'MB'],
            [1234567.890, number_format(1.18, 2) . 'MB'],
            [123456.7890, number_format(121) . 'kB'],
            [12345.67890, number_format(12.1, 1) . 'kB'],
            [1234.567890, number_format(1.21, 2) . 'kB'],
            [123.4567890, number_format(123) . 'B'],
            [12.34567890, number_format(12.3, 1) . 'B'],
            [1.234567890, number_format(1.23, 2) . 'B'],
        ];
    }

    /**
     * @dataProvider providerForTestFormatMemory
     */
    public function testFormatMemory($input, $output)
    {
        $this->assertEquals(Profiler::formatMemory($input), $output);
    }

    public function providerForTestFormatMemoryPrecision()
    {
        return [
            [1, number_format(10) . 'kB'],
            [2, number_format(12) . 'kB'],
            [3, number_format(12.1, 1) . 'kB'],
            [4, number_format(12.06, 2) . 'kB'],
            [5, number_format(12.056, 3) . 'kB'],
            [6, number_format(12.0563, 4) . 'kB'],
        ];
    }

    /**
     * @dataProvider providerForTestFormatMemoryPrecision
     */
    public function testFormatMemoryPrecision($input, $output)
    {
        $this->assertEquals(Profiler::formatMemory(12345.6789, $input), $output);
    }

    public function providerForTestFormatDuration()
    {
        return [
            [1234567890, number_format(1230000000) . 's '],
            [123456789.0, number_format(123000000) . 's '],
            [12345678.90, number_format(12300000) . 's '],
            [1234567.890, number_format(1230000) . 's '],
            [123456.7890, number_format(123000) . 's '],
            [12345.67890, number_format(12300) . 's '],
            [1234.567890, number_format(1230) . 's '],
            [123.4567890, number_format(123) . 's '],
            [12.34567890, number_format(12.3, 1) . 's '],
            [1.234567890, number_format(1.23, 2) . 's '],
            [0.123456789, number_format(123) . 'ms'],
            [0.012345678, number_format(12.3, 1) . 'ms'],
            [0.001234567, number_format(1.23, 2) . 'ms'],
        ];
    }

    /**
     * @dataProvider providerForTestFormatDuration
     */
    public function testFormatDuration($input, $output)
    {
        $this->assertEquals(Profiler::formatDuration($input), $output);
    }

    public function providerForTestFormatDurationPrecision()
    {
        return [
            [1, number_format(100) . 's '],
            [2, number_format(120) . 's '],
            [3, number_format(123) . 's '],
            [4, number_format(123.5, 1) . 's '],
            [5, number_format(123.46, 2) . 's '],
            [6, number_format(123.457, 3) . 's '],
        ];
    }

    /**
     * @dataProvider providerForTestFormatDurationPrecision
     */
    public function testFormatDurationPrecision($input, $output)
    {
        $this->assertEquals(Profiler::formatDuration(123.456789, $input), $output);
    }

    public function providerForTestToPrecision()
    {
        return [
            [1234567890, number_format(1230000000)],
            [123456789.0, number_format(123000000)],
            [12345678.90, number_format(12300000)],
            [1234567.890, number_format(1230000)],
            [123456.7890, number_format(123000)],
            [12345.67890, number_format(12300)],
            [1234.567890, number_format(1230)],
            [123.4567890, number_format(123)],
            [12.34567890, number_format(12.3, 1)],
            [1.234567890, number_format(1.23, 2)],
            [0, 0],
            [0.123456789, number_format(0.123, 3)],
            [0.012345678, number_format(0.0123, 4)],
            [0.001234567, number_format(0.00123, 5)],
            [0.000123456, number_format(0.000123, 6)],
            [0.000012345, number_format(0.0000123, 7)],
            [0.000001234, number_format(0.00000123, 8)],
            [-1234567.890, number_format(-1230000)],
        ];
    }

    /**
     * @dataProvider providerForTestToPrecision
     */
    public function testToPrecision($input, $output)
    {
        $this->assertEquals(Profiler::toPrecision($input), $output);
    }

    public function providerForTestToPrecisionPrecision()
    {
        return [
            [0, 0],
            [1, number_format(100)],
            [2, number_format(120)],
            [3, number_format(123)],
            [4, number_format(123.5, 1)],
            [5, number_format(123.46, 2)],
            [6, number_format(123.457, 3)],
        ];
    }

    /**
     * @dataProvider providerForTestToPrecisionPrecision
     */
    public function testToPrecisionPrecision($input, $output)
    {
        $this->assertEquals(Profiler::toPrecision(123.456789, $input), $output);
    }
}
