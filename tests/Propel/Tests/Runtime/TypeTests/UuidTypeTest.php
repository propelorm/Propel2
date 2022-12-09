<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\TypeTest;

use Propel\Tests\Bookstore\Base\Book2Query;
use Propel\Tests\Bookstore\Book2;
use Propel\Tests\Bookstore\Map\Book2TableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class UuidTypeTest extends BookstoreTestBase
{
    /**
     * @var string
     */
    protected $uuid = '28a8d3a6-83d8-4d8a-aa79-a6581e88d5d9';

    /**
     * @var \Propel\Tests\Bookstore\Book2
     */
    protected $book;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->book) {
            Book2Query::create()->deleteAll();
            $this->book = new Book2();
            $this->book->setUuid($this->uuid)->save();
        }
        Book2TableMap::clearInstancePool();
    }

    /**
     * @return void
     */
    public function testModelRestoresUuid()
    {
        $retrievedBook = Book2Query::create()->findOneById($this->book->getId());
        $this->assertSame($this->uuid, $retrievedBook->getUuid());
    }

    /**
     * @return void
     */
    public function testModelCanUpdateUuid()
    {
        $book = new Book2();
        $book->save();

        $updateUuid = '42a79e51-511a-4662-8956-cc89cf43f764';

        $book->setUuid($updateUuid)->save();
        $book->reload();

        $this->assertSame($updateUuid, $book->getUuid());
    }
}
