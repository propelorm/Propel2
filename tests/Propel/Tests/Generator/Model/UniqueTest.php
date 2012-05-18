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
     * @dataProvider provideTableSpecificAttributes
     *
     */
    public function testCreateDefaultUniqueIndexName($tableName, $maxColumnNameLength, $indexName)
    {
        $table = $this->getTableMock($tableName, array(
            'common_name' => $tableName,
            'unices'      => array(new Unique(), new Unique()),
            'database'    => $this->getDatabaseMock('bookstore', array(
                'platform' => $this->getPlatformMock(true, array(
                    'max_column_name_length' => $maxColumnNameLength,
                )),
            )),
        ));

        $index = new Unique();
        $index->setTable($table);

        $this->assertTrue($index->isUnique());
        $this->assertSame($indexName, $index->getName());
    }

    public function provideTableSpecificAttributes()
    {
        return array(
            array('books', 64, 'books_U_3'),
            array('super_long_table_name', 16, 'super_long_t_U_3'),
        );
    }
}