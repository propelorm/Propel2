<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests the generated queries for enum column types filters
 *
 * @group database
 */
class GeneratedQueryTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testBulkLoadTable()
    {
        \Propel\Tests\Bookstore\ContestQuery::create()->deleteAll();
        \Propel\Tests\Bookstore\CountryTranslationQuery::create()->deleteAll();
        \Propel\Tests\Bookstore\CountryQuery::create()->deleteAll();

        $stmt = $this->con->prepare('INSERT INTO country VALUES (?, ?)');
        $stmt->execute(['cd', 'Kinshasa']);
        $stmt->execute(['ht', 'Port-au-Prince']);
        $stmt->execute(['lb', 'Beirut']);

        \Propel\Tests\Bookstore\Map\CountryTableMap::clearInstancePool();
        \Propel\Tests\Bookstore\CountryQuery::create()->findPk('cd', $this->con);
        $pool = $this->getProperty(\Propel\Tests\Bookstore\Map\CountryTableMap::class, 'instances')->getValue();
        
        $this->assertEqualsCanonicalizing(['cd', 'ht', 'lb'], array_keys($pool), 'findPk() should load full table into instance pool.');
    }
}
