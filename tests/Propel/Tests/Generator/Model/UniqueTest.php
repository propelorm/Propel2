<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Unique;

/**
 * Unit test suite for the Unique model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class UniqueTest extends ModelTestCase
{
    /**
     * @dataProvider provideEntitySpecificAttributes
     *
     */
    public function testCreateDefaultUniqueIndexName($entityName, $maxFieldNameLength, $indexSqlName, $indexName)
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('getMaxFieldNameLength')
            ->will($this->returnValue($maxFieldNameLength))
        ;

        $entity = $this->getEntityMock($entityName, [
            'unices'      => [ new Unique(), new Unique() ],
            'database'    => $database,
        ]);

        $index = new Unique();
        $index->setEntity($entity);

        $this->assertTrue($index->isUnique());
        $this->assertSame($indexSqlName, $index->getSqlName());
        $this->assertSame($indexName, $index->getName());
    }

    public function provideEntitySpecificAttributes()
    {
        return [
            [ 'books', 64, 'books_u_no_fields', 'booksUNoFields' ],
            [ 'super_long_entity_name', 17, 'super_long_entity', 'superLongEntity' ],
        ];
    }
}
