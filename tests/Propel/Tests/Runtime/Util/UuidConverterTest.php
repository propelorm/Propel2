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
            [null, null, null],
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
    public function assertBinaryEquals(?string $expected, ?string $result)
    {
        $expected = $expected ? hex2bin($expected) : $expected;
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider uuidDataProvider
     * @return void
     */
    public function testBinToUuidWithSwap($uuid, $hex, $hexWithSwap)
    {
        $bin = $hex ? hex2bin($hexWithSwap) : null;
        $result = UuidConverter::binToUuid($bin, true);
        $this->assertEquals($uuid, $result);
    }

    /**
     * @dataProvider uuidDataProvider
     * 
     * @return void
     */
    public function testBinToUuidWithoutSwap($uuid, $hex, $hexWithSwap)
    {
        $bin = $hex ? hex2bin($hex) : null;
        $result = UuidConverter::binToUuid($bin, false);
        $this->assertEquals($uuid, $result);
    }
}
