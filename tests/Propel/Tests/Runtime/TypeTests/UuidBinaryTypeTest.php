<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\TypeTest;

use Propel\Runtime\Util\UuidConverter;
use Propel\Tests\Bookstore\Base\Book2Query;
use Propel\Tests\Bookstore\Book2;
use Propel\Tests\Bookstore\Map\Book2TableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class UuidBinaryTypeTest extends BookstoreTestBase
{
    /** @var string */
    protected $uuid = 'ffb35e14-6bd9-409b-a3f5-f176bfe54ebb';

    /** @var \Propel\Tests\Bookstore\Book2 */
    protected $book;

    protected function setUp(): void
    {
        parent::setUp();
    
        if(!$this->book){
            Book2Query::create()->deleteAll();
            $this->book = new Book2();
            $this->book->setUuidBin($this->uuid)->save();
        }
        Book2TableMap::clearInstancePool();
    }

    /**
     * @return void
     */
    public function testModelRestoresUuid()
    {
        $retrievedBook = Book2Query::create()->findOneById($this->book->getId());
        $this->assertSame($this->uuid, $retrievedBook->getUuidBin());
    }

    public function uuidFilterDataProvider(): array
    {
        return [
            // description, uuid value
            ['single uuid',
                'b41a29db-cf78-4d43-83a9-4cd3e1e1b41a'
            ],
            ['uuid array', [
                'b41a29db-cf78-4d43-83a9-4cd3e1e1b41a',
                '5875b237-21a2-4e7c-a976-73c6f0f6af4e', 
                'b1b838f9-0212-4638-b065-9c1ba291f55f']
            ],
            ['uuid array with null', [
                'b41a29db-cf78-4d43-83a9-4cd3e1e1b41a',
                null,
                '5875b237-21a2-4e7c-a976-73c6f0f6af4e', 
                'b1b838f9-0212-4638-b065-9c1ba291f55f']
            ],
        ];
    }

    /**
     * @dataProvider uuidFilterDataProvider
     * @return void
     */
    public function testQueryConvertsUuidParamToBin(string $description, $uuidValue)
    {
        $params = [];
        Book2Query::create()->filterByUuidBin($uuidValue)->createSelectSql($params);
        $paramValue = $params[0]['value'];

        $expectedBin = UuidConverter::uuidToBinRecursive($uuidValue, true);
        $this->assertSame($expectedBin, $paramValue, $description . ' - Uuid query params should be converted');
    }

    public function queryConfiguratorDataProvider(){
        $uuidBin = UuidConverter::uuidToBin($this->uuid, true);

        return [
            // description, configurator
            //['where string', fn(Book2Query $query) => $query->where("book2.uuid_bin = '$uuidBin'")],
            ['where with param', fn(Book2Query $query) => $query->where("book2.uuid_bin = ?", $uuidBin, \PDO::PARAM_LOB)],
            ['filterBy', fn(Book2Query $query) => $query->filterByUuidBin($this->uuid)],
        ];
    }

    /**
     * @dataProvider queryConfiguratorDataProvider
     *
     * @return void
     */
    public function testQueryResolvesUuidFilter(string $description, $queryConfigurator)
    {
        $bookQuery = Book2Query::create();
        $queryConfigurator($bookQuery);
        $result = $bookQuery->find();

        $this->assertEquals(1, $result->count());
        $loadedBook = $result[0];
        $this->assertSame($this->book->getId(), $loadedBook->getId(), 'should retrive data trough '.$description);
    }

    /**
     * @return void
     */
    public function testModelCanUpdateUuid()
    {
        $book = new Book2();
        $book->save();

        $updateUuid = 'a6afa354-27d1-458c-aee1-7118d08ab063';

        $book->setUuidBin($updateUuid)->save();
        $book->reload();

        $this->assertSame($updateUuid, $book->getUuidBin());
    }
}
