<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Util;

use Propel\Runtime\Util\UuidConverter;
use Propel\Tests\Helpers\BaseTestCase;

class UuidConverterTest extends BaseTestCase
{
    public function uuidDataProvider(): array
    {
        return [
            // uuid, hex, hexWithSwap
            ['11112222-3333-4444-5555-666677778888', '11112222333344445555666677778888', '44443333111122225555666677778888'],
            ['aab5d5fd-70c1-11e5-a4fb-b026b977eb28', 'aab5d5fd70c111e5a4fbb026b977eb28', '11e570c1aab5d5fda4fbb026b977eb28'],
        ]; 
    }

    /**
     * @dataProvider uuidDataProvider
     * @return void
     */
    public function testUuidToBinWithSwap($uuid, $hex, $hexWithSwap)
    {
        $result = UuidConverter::uuidToBin($uuid, true);
        $this->assertBinaryEquals($hexWithSwap, $result);
    }

    /**
     * @dataProvider uuidDataProvider
     * @return void
     */
    public function testUuidToBinWithoutSwap($uuid, $hex, $hexWithSwap)
    {
        $result = UuidConverter::uuidToBin($uuid, false);
        $this->assertBinaryEquals($hex, $result);
    }

    /**
     */
    public function assertBinaryEquals(string $expected, $result)
    {
        $expected = hex2bin($expected);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider uuidDataProvider
     * @return void
     */
    public function testBinToUuidWithSwap($uuid, $hex, $hexWithSwap)
    {
        $bin = hex2bin($hexWithSwap);
        $result = UuidConverter::binToUuid($bin, true);
        $this->assertEquals($uuid, $result);
    }

    /**
     * @dataProvider uuidDataProvider
     * @return void
     */
    public function testBinToUuidWithoutSwap($uuid, $hex, $hexWithSwap)
    {
        $bin = hex2bin($hex);
        $result = UuidConverter::binToUuid($bin, false);
        $this->assertEquals($uuid, $result);
    }

    public function testFasterUuidToBin(){
        $this->markTestSkipped();
        $uuid = [];

        for($i = 0; $i < 100000; $i++){
            $uuids[] = $this->guidv4();
        }
        $swapFlag = true;
        $regexDuration =  $this->measure([UuidConverter::class, 'uuidToBinRegex'], $uuids, $swapFlag);
        $regularDuration = $this->measure([UuidConverter::class, 'uuidToBin'], $uuids, $swapFlag);

        echo "regular took $regularDuration, regex took $regexDuration";
        $this->assertLessThanOrEqual($regexDuration, $regularDuration, "regular took $regularDuration, regex took $regexDuration");
    }

}
