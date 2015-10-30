<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Helpers\Namespaces\NamespacesTestBase;

use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * Test class for ModelCriteria with namespaces.
 *
 * @author Pierre-Yves LEBECQ <py.lebecq@gmail.com>
 */
class ModelCriteriaWithNamespaceTest extends NamespacesTestBase
{
    public static function conditionsForTestReplaceNamesWithNamespaces()
    {
        return [
            ['Foo\\Bar\\NamespacedBook.Title = ?', 'Title', 'namespaced_book.title = ?'], // basic case
            ['Foo\\Bar\\NamespacedBook.Title=?', 'Title', 'namespaced_book.title=?'], // without spaces
            ['Foo\\Bar\\NamespacedBook.Id<= ?', 'Id', 'namespaced_book.id<= ?'], // with non-equal comparator
            ['Foo\\Bar\\NamespacedBook.AuthorId LIKE ?', 'AuthorId', 'namespaced_book.author_id LIKE ?'], // with SQL keyword separator
            ['(Foo\\Bar\\NamespacedBook.AuthorId) LIKE ?', 'AuthorId', '(namespaced_book.author_id) LIKE ?'], // with parenthesis
            ['(Foo\\Bar\\NamespacedBook.Id*1.5)=1', 'Id', '(namespaced_book.id*1.5)=1'], // ignore numbers
            // dealing with quotes
            ["Foo\\Bar\\NamespacedBook.Id + ' ' + Foo\\Bar\\NamespacedBook.AuthorId", null, "namespaced_book.id + ' ' + namespaced_book.author_id"],
            ["'Foo\\Bar\\NamespacedBook.Id' + Foo\\Bar\\NamespacedBook.AuthorId", null, "'Foo\\Bar\\NamespacedBook.Id' + namespaced_book.author_id"],
            ["Foo\\Bar\\NamespacedBook.Id + 'Foo\\Bar\\NamespacedBook.AuthorId'", null, "namespaced_book.id + 'Foo\\Bar\\NamespacedBook.AuthorId'"],
        ];
    }

    /**
     * @dataProvider conditionsForTestReplaceNamesWithNamespaces
     */
    public function testReplaceNamesWithNamespaces($origClause, $columnPhpName = false, $modifiedClause)
    {
        $c = new TestableModelCriteriaWithNamespace('bookstore_namespaced', 'Foo\\Bar\\NamespacedBook');
        $this->doTestReplaceNames($c, \Foo\Bar\Map\NamespacedBookTableMap::getTableMap(),  $origClause, $columnPhpName = false, $modifiedClause);
    }

    public function doTestReplaceNames($c, $tableMap, $origClause, $columnPhpName = false, $modifiedClause)
    {
        $c->replaceNames($origClause);
        $columns = $c->replacedColumns;
        if ($columnPhpName) {
            $this->assertEquals([$tableMap->getColumnByPhpName($columnPhpName)], $columns);
        }
        $this->assertEquals($modifiedClause, $origClause);
    }

}

class TestableModelCriteriaWithNamespace extends ModelCriteria
{
    public $joins = [];

    public function replaceNames(&$sql)
    {
        return parent::replaceNames($sql);
    }
}
