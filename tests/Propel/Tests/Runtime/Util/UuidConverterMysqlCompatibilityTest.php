<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Util;

use PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\Util\UuidConverter;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Helpers\CheckMysql8Trait;

/**
 * @group mysql
 * @group database
 */
class UuidConverterMysqlCompatibilityTest extends BookstoreTestBase
{
    use CheckMysql8Trait;

    protected function setUp(): void
    {
        parent::setUp();
        if(!$this->checkMysqlVersionAtLeast8()){
            $this->markTestSkipped('Test can only be run on MySQL version >= 8');
            return;
        }
    }

    public function operationsDataProvider(): array
    {
        return [
            // description, mysql function , converter callback, input value, input bin
            ['uuid to bin without swap', 'SELECT UUID_TO_BIN(?, false)', fn($uuid) => UuidConverter::uuidToBin($uuid, false), false],
            ['uuid to bin with swap', 'SELECT UUID_TO_BIN(?, true)', fn($uuid) => UuidConverter::uuidToBin($uuid, true), false],

            ['bin to uuid without swap', 'SELECT BIN_TO_UUID(?, false)', fn($uuid) => UuidConverter::binToUuid($uuid, false), true],
            ['bin to uuid with swap', 'SELECT BIN_TO_UUID(?, true)', fn($uuid) => UuidConverter::binToUuid($uuid, true), true],
            
        ];
    }

    /**
     * @dataProvider operationsDataProvider
     */
    public function testBinToUuidBehavesLikeInMysql($description, $sqlStatement, $callback, $inputBin)
    {
        $value = ($inputBin)
            ? hex2bin('aab5d5fd70c111e5a4fbb026b977eb28')
            : 'aab5d5fd-70c1-11e5-a4fb-b026b977eb28'
            ;
        $mysqlBin = $this->executeStatement($sqlStatement, $value);

        $propelBin = $callback($value);
        $this->assertSame($mysqlBin, $propelBin, $description . ' should match between Propel and MySQL');
    }

    protected function executeStatement(string $statement, string $value)
    {
        $con = Propel::getServiceContainer()->getConnection();
        $ps = $con->prepare($statement);
        $ps->bindParam(1, $value, PDO::PARAM_STR);
        $ps->execute();
        $result = $ps->fetch()[0];
        $ps->closeCursor();

        return $result;
    }

    
}

