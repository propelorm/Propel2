<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\Bookstore\Map\AuthorEntityMap;
use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\Bookstore\Map\PublisherEntityMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for Criteria.
 *
 * @author Christopher Elkins <celkins@scardini.com>
 * @author Sam Joseph <sam@neurogrid.com>
 */
class CriteriaMergeTest extends TestCaseFixtures
{
    protected function assertCriteriaTranslation(Criteria $criteria, $expectedSql, $message = '')
    {
        $params = array();
        $result = $criteria->createSelectSql($params);
        $this->assertEquals($expectedSql, $result, $message);
    }

    public function testMergeWithLimit()
    {
        $c1 = new ModelCriteria();
        $c1->setLimit(123);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(123, $c1->getLimit(), 'mergeWith() does not remove an existing limit');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->setLimit(123);
        $c1->mergeWith($c2);
        $this->assertEquals(123, $c1->getLimit(), 'mergeWith() merges the limit');
        $c1 = new ModelCriteria();
        $c1->setLimit(456);
        $c2 = new ModelCriteria();
        $c2->setLimit(123);
        $c1->mergeWith($c2);
        $this->assertEquals(456, $c1->getLimit(), 'mergeWith() does not merge the limit in case of conflict');
    }

    public function testMergeWithOffset()
    {
        $c1 = new ModelCriteria();
        $c1->setOffset(123);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(123, $c1->getOffset(), 'mergeWith() does not remove an existing offset');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->setOffset(123);
        $c1->mergeWith($c2);
        $this->assertEquals(123, $c1->getOffset(), 'mergeWith() merges the offset');
        $c1 = new ModelCriteria();
        $c1->setOffset(456);
        $c2 = new ModelCriteria();
        $c2->setOffset(123);
        $c1->mergeWith($c2);
        $this->assertEquals(456, $c1->getOffset(), 'mergeWith() does not merge the offset in case of conflict');
    }

    public function testMergeWithSelectModifiers()
    {
        $c1 = new ModelCriteria();
        $c1->setDistinct();
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(array(Criteria::DISTINCT), $c1->getSelectModifiers(), 'mergeWith() does not remove an existing select modifier');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->setDistinct();
        $c1->mergeWith($c2);
        $this->assertEquals(array(Criteria::DISTINCT), $c1->getSelectModifiers(), 'mergeWith() merges the select modifiers');
        $c1 = new ModelCriteria();
        $c1->setDistinct();
        $c2 = new ModelCriteria();
        $c2->setDistinct();
        $c1->mergeWith($c2);
        $this->assertEquals(array(Criteria::DISTINCT), $c1->getSelectModifiers(), 'mergeWith() does not duplicate select modifiers');
        $c1 = new ModelCriteria();
        $c1->setAll();
        $c2 = new ModelCriteria();
        $c2->setDistinct();
        $c1->mergeWith($c2);
        $this->assertEquals(array(Criteria::ALL), $c1->getSelectModifiers(), 'mergeWith() does not merge the select modifiers in case of conflict');
    }

    public function testMergeWithSelectFields()
    {
        $c1 = new ModelCriteria();
        $c1->addSelectField(BookEntityMap::FIELD_TITLE);
        $c1->addSelectField(BookEntityMap::FIELD_ID);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_ID), $c1->getSelectFields(), 'mergeWith() does not remove an existing select fields');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->addSelectField(BookEntityMap::FIELD_TITLE);
        $c2->addSelectField(BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_ID), $c1->getSelectFields(), 'mergeWith() merges the select fields to an empty select');
        $c1 = new ModelCriteria();
        $c1->addSelectField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addSelectField(BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_ID), $c1->getSelectFields(), 'mergeWith() merges the select fields after the existing select fields');
        $c1 = new ModelCriteria();
        $c1->addSelectField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addSelectField(BookEntityMap::FIELD_TITLE);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_TITLE), $c1->getSelectFields(), 'mergeWith() merges the select fields to an existing select, even if duplicated');
    }

    public function testMergeWithAsFields()
    {
        $c1 = new ModelCriteria();
        $c1->addAsField('foo', BookEntityMap::FIELD_TITLE);
        $c1->addAsField('bar', BookEntityMap::FIELD_ID);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(array('foo' => BookEntityMap::FIELD_TITLE, 'bar' => BookEntityMap::FIELD_ID), $c1->getAsFields(), 'mergeWith() does not remove an existing as fields');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->addAsField('foo', BookEntityMap::FIELD_TITLE);
        $c2->addAsField('bar', BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array('foo' => BookEntityMap::FIELD_TITLE, 'bar' => BookEntityMap::FIELD_ID), $c1->getAsFields(), 'mergeWith() merges the select fields to an empty as');
        $c1 = new ModelCriteria();
        $c1->addAsField('foo', BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addAsField('bar', BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array('foo' => BookEntityMap::FIELD_TITLE, 'bar' => BookEntityMap::FIELD_ID), $c1->getAsFields(), 'mergeWith() merges the select fields after the existing as fields');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     */
    public function testMergeWithAsFieldsThrowsException()
    {
        $c1 = new ModelCriteria();
        $c1->addAsField('foo', BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addAsField('foo', BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
    }

    public function testMergeWithOrderByFields()
    {
        $c1 = new ModelCriteria();
        $c1->addAscendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c1->addAscendingOrderByField(BookEntityMap::FIELD_ID);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE . ' ASC', BookEntityMap::FIELD_ID . ' ASC'), $c1->getOrderByFields(), 'mergeWith() does not remove an existing orderby fields');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->addAscendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c2->addAscendingOrderByField(BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE . ' ASC', BookEntityMap::FIELD_ID . ' ASC'), $c1->getOrderByFields(), 'mergeWith() merges the select fields to an empty order by');
        $c1 = new ModelCriteria();
        $c1->addAscendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addAscendingOrderByField(BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE . ' ASC', BookEntityMap::FIELD_ID . ' ASC'), $c1->getOrderByFields(), 'mergeWith() merges the select fields after the existing orderby fields');
        $c1 = new ModelCriteria();
        $c1->addAscendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addAscendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE . ' ASC'), $c1->getOrderByFields(), 'mergeWith() does not merge duplicated orderby fields');
        $c1 = new ModelCriteria();
        $c1->addAscendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addDescendingOrderByField(BookEntityMap::FIELD_TITLE);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE . ' ASC', BookEntityMap::FIELD_TITLE . ' DESC'), $c1->getOrderByFields(), 'mergeWith() merges duplicated orderby fields with inverse direction');
    }

    public function testMergeWithGroupByFields()
    {
        $c1 = new ModelCriteria();
        $c1->addGroupByField(BookEntityMap::FIELD_TITLE);
        $c1->addGroupByField(BookEntityMap::FIELD_ID);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_ID), $c1->getGroupByFields(), 'mergeWith() does not remove an existing groupby fields');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->addGroupByField(BookEntityMap::FIELD_TITLE);
        $c2->addGroupByField(BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_ID), $c1->getGroupByFields(), 'mergeWith() merges the select fields to an empty groupby');
        $c1 = new ModelCriteria();
        $c1->addGroupByField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addGroupByField(BookEntityMap::FIELD_ID);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_ID), $c1->getGroupByFields(), 'mergeWith() merges the select fields after the existing groupby fields');
        $c1 = new ModelCriteria();
        $c1->addGroupByField(BookEntityMap::FIELD_TITLE);
        $c2 = new ModelCriteria();
        $c2->addGroupByField(BookEntityMap::FIELD_TITLE);
        $c1->mergeWith($c2);
        $this->assertEquals(array(BookEntityMap::FIELD_TITLE), $c1->getGroupByFields(), 'mergeWith() does not merge duplicated groupby fields');
    }

    public function testMergeWithWhereConditions()
    {
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() does not remove an existing where condition');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to an empty condition');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_ID, 123);
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE book.id=:p1 AND book.title=:p2');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'bar');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE (book.title=:p1 AND book.title=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions on the same field');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID, Criteria::LEFT_JOIN);
        $c2 = new ModelCriteria();
        $c2->add(AuthorEntityMap::FIELD_FIRST_NAME, 'bar');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE book.title=:p1 AND author.first_name=:p2');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions on the different tables');
    }

    public function testMergeOrWithWhereConditions()
    {
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2, Criteria::LOGICAL_OR);
        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() does not remove an existing where condition');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->mergeWith($c2, Criteria::LOGICAL_OR);
        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to an empty condition');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_ID, 123);
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->mergeWith($c2, Criteria::LOGICAL_OR);
        $sql = $this->getSql('SELECT  FROM book WHERE (book.id=:p1 OR book.title=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'bar');
        $c1->mergeWith($c2, Criteria::LOGICAL_OR);
        $sql = $this->getSql('SELECT  FROM book WHERE (book.title=:p1 OR book.title=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions on the same field');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID, Criteria::LEFT_JOIN);
        $c2 = new ModelCriteria();
        $c2->add(AuthorEntityMap::FIELD_FIRST_NAME, 'bar');
        $c1->mergeWith($c2, Criteria::LOGICAL_OR);
        $sql = $this->getSql('SELECT  FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE (book.title=:p1 OR author.first_name=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions on the different tables');
    }

    public function testMerge_OrWithWhereConditions()
    {
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria();
        $c1->_or();
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() does not remove an existing where condition');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->_or();
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to an empty condition');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_ID, 123);
        $c1->_or();
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE (book.id=:p1 OR book.title=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->_or();
        $c2 = new ModelCriteria();
        $c2->add(BookEntityMap::FIELD_TITLE, 'bar');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book WHERE (book.title=:p1 OR book.title=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions on the same field');
        $c1 = new ModelCriteria();
        $c1->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID, Criteria::LEFT_JOIN);
        $c1->_or();
        $c2 = new ModelCriteria();
        $c2->add(AuthorEntityMap::FIELD_FIRST_NAME, 'bar');
        $c1->mergeWith($c2);
        $sql = $this->getSql('SELECT  FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE (book.title=:p1 OR author.first_name=:p2)');
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges where condition to existing conditions on the different tables');
    }

    public function testMergeWithHavingConditions()
    {
        $c1 = new ModelCriteria();
        $cton = $c1->getNewCriterion(BookEntityMap::FIELD_TITLE, 'foo', Criteria::EQUAL);
        $c1->addHaving($cton);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $sql = 'SELECT  FROM  HAVING book.title=:p1';
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() does not remove an existing having condition');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $cton = $c2->getNewCriterion(BookEntityMap::FIELD_TITLE, 'foo', Criteria::EQUAL);
        $c2->addHaving($cton);
        $c1->mergeWith($c2);
        $sql = 'SELECT  FROM  HAVING book.title=:p1';
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() merges having condition to an empty having');
        $c1 = new ModelCriteria();
        $cton = $c1->getNewCriterion(BookEntityMap::FIELD_TITLE, 'foo', Criteria::EQUAL);
        $c1->addHaving($cton);
        $c2 = new ModelCriteria();
        $cton = $c2->getNewCriterion(BookEntityMap::FIELD_TITLE, 'bar', Criteria::EQUAL);
        $c2->addHaving($cton);
        $c1->mergeWith($c2);
        $sql = 'SELECT  FROM  HAVING (book.title=:p1 AND book.title=:p2)';
        $this->assertCriteriaTranslation($c1, $sql, 'mergeWith() combines having with AND');
    }

    public function testMergeWithAliases()
    {
        $c1 = new ModelCriteria();
        $c1->addAlias('b', BookEntityMap::TABLE_NAME);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $this->assertEquals(array('b' => BookEntityMap::TABLE_NAME), $c1->getAliases(), 'mergeWith() does not remove an existing alias');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->addAlias('a', AuthorEntityMap::TABLE_NAME);
        $c1->mergeWith($c2);
        $this->assertEquals(array('a' => AuthorEntityMap::TABLE_NAME), $c1->getAliases(), 'mergeWith() merge aliases to an empty alias');
        $c1 = new ModelCriteria();
        $c1->addAlias('b', BookEntityMap::TABLE_NAME);
        $c2 = new ModelCriteria();
        $c2->addAlias('a', AuthorEntityMap::TABLE_NAME);
        $c1->mergeWith($c2);
        $this->assertEquals(array('b' => BookEntityMap::TABLE_NAME, 'a' => AuthorEntityMap::TABLE_NAME), $c1->getAliases(), 'mergeWith() merge aliases to an existing alias');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     */
    public function testMergeWithAliasesThrowsException()
    {
        $c1 = new ModelCriteria();
        $c1->addAlias('b', BookEntityMap::TABLE_NAME);
        $c2 = new ModelCriteria();
        $c2->addAlias('b', AuthorEntityMap::TABLE_NAME);
        $c1->mergeWith($c2);
    }

    public function testMergeWithJoins()
    {
        $c1 = new ModelCriteria();
        $c1->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID, Criteria::LEFT_JOIN);
        $c2 = new ModelCriteria();
        $c1->mergeWith($c2);
        $joins = $c1->getJoins();
        $this->assertEquals(1, count($joins), 'mergeWith() does not remove an existing join');
        $this->assertEquals('LEFT JOIN Propel\Tests\Bookstore\Author ON (Propel\Tests\Bookstore\Book.authorId=Propel\Tests\Bookstore\Author.id)', $joins[0]->toString(), 'mergeWith() does not remove an existing join');
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID, Criteria::LEFT_JOIN);
        $c1->mergeWith($c2);
        $joins = $c1->getJoins();
        $this->assertEquals(1, count($joins), 'mergeWith() merge joins to an empty join');
        $this->assertEquals('LEFT JOIN Propel\Tests\Bookstore\Author ON (Propel\Tests\Bookstore\Book.authorId=Propel\Tests\Bookstore\Author.id)', $joins[0]->toString(), 'mergeWith() merge joins to an empty join');
        $c1 = new ModelCriteria();
        $c1->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID, Criteria::LEFT_JOIN);
        $c2 = new ModelCriteria();
        $c2->addJoin(BookEntityMap::FIELD_PUBLISHER_ID, PublisherEntityMap::FIELD_ID, Criteria::INNER_JOIN);
        $c1->mergeWith($c2);
        $joins = $c1->getJoins();
        $this->assertEquals(2, count($joins), 'mergeWith() merge joins to an existing join');
        $this->assertEquals('LEFT JOIN Propel\Tests\Bookstore\Author ON (Propel\Tests\Bookstore\Book.authorId=Propel\Tests\Bookstore\Author.id)', $joins[0]->toString(), 'mergeWith() merge joins to an empty join');
        $this->assertEquals('INNER JOIN Propel\Tests\Bookstore\Publisher ON (Propel\Tests\Bookstore\Book.publisherId=Propel\Tests\Bookstore\Publisher.id)', $joins[1]->toString(), 'mergeWith() merge joins to an empty join');
    }

    public function testMergeWithFurtherModified()
    {
        $c1 = new ModelCriteria();
        $c2 = new ModelCriteria();
        $c2->setLimit(123);
        $c1->mergeWith($c2);
        $this->assertEquals(123, $c1->getLimit(), 'mergeWith() makes the merge');
        $c2->setLimit(456);
        $this->assertEquals(123, $c1->getLimit(), 'further modifying a merged criteria does not affect the merger');
    }

}
