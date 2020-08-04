<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for RelatedMap::getSymmetricalRelation.
 *
 * @author François Zaninotto
 */
class RelatedMapSymmetricalTest extends TestCaseFixtures
{
    protected $databaseMap;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseMap = Propel::getServiceContainer()->getDatabaseMap('bookstore');
    }

    /**
     * @return void
     */
    public function testOneToMany()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $bookToAuthor = $bookTable->getRelation('Author');
        $authorTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Author');
        $authorToBook = $authorTable->getRelation('Book');
        $this->assertEquals($authorToBook, $bookToAuthor->getSymmetricalRelation());
        $this->assertEquals($bookToAuthor, $authorToBook->getSymmetricalRelation());
    }

    /**
     * @return void
     */
    public function testOneToOne()
    {
        $accountTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $accountToEmployee = $accountTable->getRelation('BookstoreEmployee');
        $employeeTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployee');
        $employeeToAccount = $employeeTable->getRelation('BookstoreEmployeeAccount');
        $this->assertEquals($accountToEmployee, $employeeToAccount->getSymmetricalRelation());
        $this->assertEquals($employeeToAccount, $accountToEmployee->getSymmetricalRelation());
    }

    /**
     * @return void
     */
    public function testSeveralRelationsOnSameTable()
    {
        $authorTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Author');
        $authorToEssay = $authorTable->getRelation('EssayRelatedByFirstAuthorId');
        $essayTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Essay');
        $essayToAuthor = $essayTable->getRelation('FirstAuthor');
        $this->assertEquals($authorToEssay, $essayToAuthor->getSymmetricalRelation());
        $this->assertEquals($essayToAuthor, $authorToEssay->getSymmetricalRelation());
    }

    /**
     * @return void
     */
    public function testCompositeForeignKey()
    {
        $favoriteTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\ReaderFavorite');
        $favoriteToOpinion = $favoriteTable->getRelation('BookOpinion');
        $opinionTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookOpinion');
        $opinionToFavorite = $opinionTable->getRelation('ReaderFavorite');
        $this->assertEquals($favoriteToOpinion, $opinionToFavorite->getSymmetricalRelation());
        $this->assertEquals($opinionToFavorite, $favoriteToOpinion->getSymmetricalRelation());
    }
}
