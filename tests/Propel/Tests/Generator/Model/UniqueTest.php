<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @dataProvider provideTableSpecificAttributes
     *
     * @param string $tableName
     * @param int $maxColumnNameLength
     * @param string $indexName
     *
     * @return void
     */
    public function testCreateDefaultUniqueIndexName($tableName, $maxColumnNameLength, $indexName)
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('getMaxColumnNameLength')
            ->will($this->returnValue($maxColumnNameLength));

        $table = $this->getTableMock($tableName, [
            'common_name' => $tableName,
            'unices' => [ new Unique(), new Unique() ],
            'database' => $database,
        ]);

        $index = new Unique();
        $index->setTable($table);

        $this->assertTrue($index->isUnique());
        $this->assertSame($indexName, $index->getName());
    }

    /**
     * @return array
     */
    public function provideTableSpecificAttributes()
    {
        return [
            [ 'books', 64, 'books_u_no_columns' ],
            [ 'super_long_table_name', 16, 'super_long_table' ],
        ];
    }
}
