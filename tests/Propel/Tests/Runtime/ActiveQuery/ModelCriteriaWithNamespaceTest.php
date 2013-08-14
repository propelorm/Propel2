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
        return array(
            array('Foo\\Bar\\NamespacedBook.Title = ?', 'Title', 'namespaced_book.TITLE = ?'), // basic case
            array('Foo\\Bar\\NamespacedBook.Title=?', 'Title', 'namespaced_book.TITLE=?'), // without spaces
            array('Foo\\Bar\\NamespacedBook.Id<= ?', 'Id', 'namespaced_book.ID<= ?'), // with non-equal comparator
            array('Foo\\Bar\\NamespacedBook.AuthorId LIKE ?', 'AuthorId', 'namespaced_book.AUTHOR_ID LIKE ?'), // with SQL keyword separator
            array('(Foo\\Bar\\NamespacedBook.AuthorId) LIKE ?', 'AuthorId', '(namespaced_book.AUTHOR_ID) LIKE ?'), // with parenthesis
            array('(Foo\\Bar\\NamespacedBook.Id*1.5)=1', 'Id', '(namespaced_book.ID*1.5)=1'), // ignore numbers
            // dealing with quotes
            array("Foo\\Bar\\NamespacedBook.Id + ' ' + Foo\\Bar\\NamespacedBook.AuthorId", null, "namespaced_book.ID + ' ' + namespaced_book.AUTHOR_ID"),
            array("'Foo\\Bar\\NamespacedBook.Id' + Foo\\Bar\\NamespacedBook.AuthorId", null, "'Foo\\Bar\\NamespacedBook.Id' + namespaced_book.AUTHOR_ID"),
            array("Foo\\Bar\\NamespacedBook.Id + 'Foo\\Bar\\NamespacedBook.AuthorId'", null, "namespaced_book.ID + 'Foo\\Bar\\NamespacedBook.AuthorId'"),
        );
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
            $this->assertEquals(array($tableMap->getColumnByPhpName($columnPhpName)), $columns);
        }
        $this->assertEquals($modifiedClause, $origClause);
    }

}

class TestableModelCriteriaWithNamespace extends ModelCriteria
{
    public $joins = array();

    public function replaceNames(&$clause)
    {
        return parent::replaceNames($clause);
    }
}
