<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\Configuration;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Book2;
use Propel\Tests\Bookstore\Book2Query;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\Map\AuthorEntityMap;
use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\Bookstore\Map\PublisherEntityMap;
use Propel\Tests\Bookstore\Map\ReviewEntityMap;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Formatter\StatementFormatter;
use Propel\Runtime\Formatter\ArrayFormatter;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Util\PropelModelPager;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Exception\UnknownFieldException;
use Propel\Runtime\ActiveQuery\Exception\UnknownRelationException;
use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * Test class for ModelCriteria.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ModelCriteriaTest extends BookstoreTestBase
{
    protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
    {
        $params = array();
        $result = $criteria->createSelectSql($params);

        $this->assertEquals($expectedSql, $result, $message);
        $this->assertEquals($expectedParams, $params, $message);
    }

    public function testGetModelName()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $c->getEntityName(), 'getEntityName() returns the name of the class associated to the model class');

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $c->getEntityName(), 'getEntityName() returns the name of the class associated to the model class');
    }

    public function testGetFullyQualifiedModelName()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $this->assertEquals('\Propel\Tests\Bookstore\Book', $c->getFullyQualifiedModelName(), 'getFullyQualifiedModelName() returns the name of the class associated to the model class');

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $this->assertEquals('\Propel\Tests\Bookstore\Book', $c->getFullyQualifiedModelName(), 'getFullyQualifiedModelName() returns the name of the class associated to the model class');
    }

    public function testFormatter()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $this->assertTrue($c->getFormatter() instanceof AbstractFormatter, 'getFormatter() returns a PropelFormatter instance');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_STATEMENT);
        $this->assertTrue($c->getFormatter() instanceof StatementFormatter, 'setFormatter() accepts the name of a AbstractFormatter class');

        try {
            $c->setFormatter('Propel\Tests\Bookstore\Book');
            $this->fail('setFormatter() throws an exception when passed the name of a class not extending AbstractFormatter');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, 'setFormatter() throws an exception when passed the name of a class not extending AbstractFormatter');
        }
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $formatter = new StatementFormatter();
        $c->setFormatter($formatter);
        $this->assertTrue($c->getFormatter() instanceof StatementFormatter, 'setFormatter() accepts a AbstractFormatter instance');

        try {
            $formatter = new Book();
            $c->setFormatter($formatter);
            $this->fail('setFormatter() throws an exception when passed an object not extending AbstractFormatter');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, 'setFormatter() throws an exception when passed an object not extending AbstractFormatter');
        }

    }

    public static function conditionsForTestReplaceNames()
    {
        return array(
            array('Propel\Tests\Bookstore\Book.Title = ?', 'Title', 'book.title = ?'), // basic case
            array('Propel\Tests\Bookstore\Book.Title=?', 'Title', 'book.title=?'), // without spaces
            array('Propel\Tests\Bookstore\Book.Id<= ?', 'Id', 'book.id<= ?'), // with non-equal comparator
            array('Propel\Tests\Bookstore\Book.authorId LIKE ?', 'AuthorId', 'book.author_id LIKE ?'), // with SQL keyword separator
            array('(Propel\Tests\Bookstore\Book.authorId) LIKE ?', 'AuthorId', '(book.author_id) LIKE ?'), // with parenthesis
            array('(Propel\Tests\Bookstore\Book.Id*1.5)=1', 'Id', '(book.id*1.5)=1'), // ignore numbers
            // dealing with quotes
            array("Propel\Tests\Bookstore\Book.Id + ' ' + Propel\Tests\Bookstore\Book.authorId", null, "book.id + ' ' + book.author_id"),
            array("'Propel\Tests\Bookstore\Book.Id' + Propel\Tests\Bookstore\Book.authorId", null, "'Propel\Tests\Bookstore\Book.Id' + book.author_id"),
            array("Propel\Tests\Bookstore\Book.Id + 'Propel\Tests\Bookstore\Book.authorId'", null, "book.id + 'Propel\Tests\Bookstore\Book.authorId'"),
            array('1=1', null, '1=1'), // with no name
            array('', null, ''), // with empty string

            //without NS
            array('Book.Title = ?', 'Title', 'book.title = ?'), // basic case
            array('Book.Title=?', 'Title', 'book.title=?'), // without spaces
            array('Book.Id<= ?', 'Id', 'book.id<= ?'), // with non-equal comparator
            array('Book.authorId LIKE ?', 'AuthorId', 'book.author_id LIKE ?'), // with SQL keyword separator
            array('(Book.authorId) LIKE ?', 'AuthorId', '(book.author_id) LIKE ?'), // with parenthesis
            array('(Book.Id*1.5)=1', 'Id', '(book.id*1.5)=1'), // ignore numbers
            // dealing with quotes
            array("Book.Id + ' ' + Book.authorId", null, "book.id + ' ' + book.author_id"),
            array("'Book.Id' + Book.authorId", null, "'Book.Id' + book.author_id"),
            array("Book.Id + 'Book.authorId'", null, "book.id + 'Book.authorId'"),

        );
    }

    /**
     * @dataProvider conditionsForTestReplaceNames
     */
    public function testReplaceNames($origClause, $fieldPhpName = false, $modifiedClause)
    {
        $c = new TestableModelCriteria('bookstore', \Propel\Tests\Bookstore\Book::class);
        $this->doTestReplaceNames($c, Configuration::getCurrentConfiguration()->getEntityMap(\Propel\Tests\Bookstore\Book::class), $origClause, $fieldPhpName = false, $modifiedClause);
    }

    public function doTestReplaceNames(Criteria $c, $tableMap, $origClause, $fieldPhpName = false, $modifiedClause)
    {
        $c->replaceNames($origClause);
        $fields = $c->replacedFields;
        if ($fieldPhpName) {
            $this->assertEquals(array($tableMap->getField($fieldPhpName)), $fields);
        }
        $this->assertEquals($modifiedClause, $origClause);
    }

    public static function conditionsForTestReplaceMultipleNames()
    {
        return array(
            array('(Propel\Tests\Bookstore\Book.Id+Book.Id)=1', array('Id', 'Id'), '(book.id+book.id)=1'), // match multiple names
            array('CONCAT(Propel\Tests\Bookstore\Book.Title,"Book.Id")= ?', array('Title', 'Id'), 'CONCAT(book.title,"Book.Id")= ?'), // ignore names in strings
            array('CONCAT(Propel\Tests\Bookstore\Book.Title," Book.Id ")= ?', array('Title', 'Id'), 'CONCAT(book.title," Book.Id ")= ?'), // ignore names in strings
            array('MATCH (Propel\Tests\Bookstore\Book.Title,Book.isbn) AGAINST (?)', array('Title', 'ISBN'), 'MATCH (book.title,book.isbn) AGAINST (?)'),
        );
    }

    /**
     * @dataProvider conditionsForTestReplaceMultipleNames
     */
    public function testReplaceMultipleNames($origClause, $expectedFields, $modifiedClause)
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->replaceNames($origClause);
        $foundFields = $c->replacedFields;
        foreach ($foundFields as $field) {
            $expectedField = BookEntityMap::getEntityMap()->getField(array_shift($expectedFields));
            $this->assertEquals($expectedField, $field);
        }
        $this->assertEquals($modifiedClause, $origClause);
    }

    public function testTableAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b');
        $c->where('b.Title = ?', 'foo');

        $sql = $this->getSql("SELECT  FROM book WHERE book.title = :p1");
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'setEntityAlias() allows the definition of the alias after construction');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');

        $sql = $this->getSql("SELECT  FROM book WHERE book.title = :p1");
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'A ModelCriteria accepts a model name with an alias');
    }

    public function testTrueTableAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', true);
        $c->where('b.Title = ?', 'foo');
        $c->join('b.author a');
        $c->where('a.FirstName = ?', 'john');

        $sql = $this->getSql("SELECT  FROM book b INNER JOIN author a ON (b.author_id=a.id) WHERE b.title = :p1 AND a.first_name = :p2");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Author', 'field' => 'firstName', 'value' => 'john'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'setEntityAlias() allows the definition of a true SQL alias after construction');
    }

    public function testCondition()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->combine(array('cond1', 'cond2'), 'or');

        $sql = $this->getSql("SELECT  FROM book WHERE (book.title <> :p1 OR book.title like :p2)");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => '%bar%'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'condition() can store condition for later combination');
    }

    public function testConditionCustomOperator()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->withField('SUBSTRING(Book.Title, 1, 4)', 'title_start');
        $c->condition('cond1', 'Book.Title <> ?', 'foo');
        $c->condition('cond2', 'title_start like ?', '%bar%', \PDO::PARAM_STR);
        $c->combine(array('cond1', 'cond2'), 'or');

        $sql = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, SUBSTRING(book.title, 1, 4) AS title_start FROM book WHERE (book.title <> :p1 OR title_start like :p2)");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => null, 'type' => \PDO::PARAM_STR, 'value' => '%bar%'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'condition() accepts RAW sql parameters');
    }

    public static function conditionsForTestWhere()
    {
        return array(
            array(
                'Propel\Tests\Bookstore\Book.Title = ?',
                'foo',
                'book.title = :p1',
                array(array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'))
            ),
            array(
                'Propel\Tests\Bookstore\Book.authorId = ?',
                12,
                'book.author_id = :p1',
                array(array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'authorId', 'value' => 12))
            ),
            array('Propel\Tests\Bookstore\Book.authorId IS NULL', null, 'book.author_id IS NULL', array()),
            array(
                'Propel\Tests\Bookstore\Book.Id BETWEEN ? AND ?',
                array(3, 4),
                'book.id BETWEEN :p1 AND :p2',
                array(
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 3),
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 4)
                )
            ),
            array(
                'Propel\Tests\Bookstore\Book.Id betWEen ? and ?',
                array(3, 4),
                'book.id betWEen :p1 and :p2',
                array(
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 3),
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 4)
                )
            ),
            array(
                'Propel\Tests\Bookstore\Book.Id IN ?',
                array(1, 2, 3),
                'book.id IN (:p1,:p2,:p3)',
                array(
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 1),
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 2),
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 3)
                )
            ),
            array(
                'Propel\Tests\Bookstore\Book.Id in ?',
                array(1, 2, 3),
                'book.id in (:p1,:p2,:p3)',
                array(
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 1),
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 2),
                    array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 3)
                )
            ),
            array('Propel\Tests\Bookstore\Book.Id IN ?', array(), '1<>1', array()),
            array('Propel\Tests\Bookstore\Book.Id not in ?', array(), '1=1', array()),
            array(
                'UPPER(Propel\Tests\Bookstore\Book.Title) = ?',
                'foo',
                'UPPER(book.title) = :p1',
                array(array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'))
            ),
            array(
                'MATCH (Propel\Tests\Bookstore\Book.Title,Propel\Tests\Bookstore\Book.isbn) AGAINST (?)',
                'foo',
                'MATCH (book.title,book.isbn) AGAINST (:p1)',
                array(array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'))
            ),
        );
    }

    /**
     * @dataProvider conditionsForTestWhere
     */
    public function testWhere($clause, $value, $sql, $params)
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where($clause, $value);

        $sql = $this->getSql('SELECT  FROM book WHERE ' . $sql);

        $this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts a string clause');
    }

    public function testWhereUsesDefaultOperator()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Id = ?', 12);
        $c->_or();
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'foo');

        $sql = $this->getSql('SELECT  FROM book WHERE (book.id = :p1 OR book.title = :p2)');

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => '12'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'where() uses the default operator');
    }

    public function testWhereTwiceSameField()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Id IN ?', array(1, 2, 3));
        $c->where('Propel\Tests\Bookstore\Book.Id <> ?', 5);
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => '1'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => '2'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => '3'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => '5'),
        );

        $sql = $this->getSql('SELECT  FROM book WHERE (book.id IN (:p1,:p2,:p3) AND book.id <> :p4)');

        $this->assertCriteriaTranslation($c, $sql, $params, 'where() adds clauses on the same field correctly');
    }

    public function testWhereConditions()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->where(array('cond1', 'cond2'));

        $sql = $this->getSql("SELECT  FROM book WHERE (book.title <> :p1 AND book.title like :p2)");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => '%bar%'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts an array of named conditions');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->where(array('cond1', 'cond2'), Criteria::LOGICAL_OR);

        $sql = $this->getSql("SELECT  FROM book WHERE (book.title <> :p1 OR book.title like :p2)");

        $this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts an array of named conditions with operator');
    }

    public function testWhereNoReplacement()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $c->where('1=1');

        $sql = $this->getSql("SELECT  FROM book WHERE book.title = :p1 AND 1=1");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'where() results in a Criteria::CUSTOM if no field name is matched');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        try {
            $c->where('b.Title = ?', 'foo');
            $this->fail('where() throws an exception when it finds a ? but cannot determine a field');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'where() throws an exception when it finds a ? but cannot determine a field');
        }
    }

    public function testWhereFunction()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('UPPER(b.Title) = ?', 'foo');

        $sql = $this->getSql("SELECT  FROM book WHERE UPPER(book.title) = :p1");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts a complex calculation');
    }

    /**
     * @group mysql
     */
    public function testWhereTypeValue()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('LOCATE(\'foo\', b.Title) = ?', true, \PDO::PARAM_BOOL);

        $sql = $this->getSql("SELECT  FROM book WHERE LOCATE('foo', book.title) = :p1");

        $params = array(
            array('entity' => null, 'type' => \PDO::PARAM_BOOL, 'value' => true),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts a complex calculation');
        $c->find();

        $expected = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE LOCATE('foo', book.title) = true");

        $this->assertEquals($expected, Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection()->getLastExecutedQuery());
    }

    public function testOrWhere()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->_or()->where('Propel\Tests\Bookstore\Book.Title like ?', '%bar%');

        $sql = $this->getSql("SELECT  FROM book WHERE (book.title <> :p1 OR book.title like :p2)");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => '%bar%'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'orWhere() combines the clause with the previous one using  OR');
    }

    public function testOrWhereConditions()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Id = ?', 12);
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->_or()->where(array('cond1', 'cond2'));

        $sql = $this->getSql("SELECT  FROM book WHERE (book.id = :p1 OR (book.title <> :p2 AND book.title like :p3))");

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 12),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => '%bar%'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'orWhere() accepts an array of named conditions');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Id = ?', 12);
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->_or()->where(array('cond1', 'cond2'), Criteria::LOGICAL_OR);

        $sql = $this->getSql("SELECT  FROM book WHERE (book.id = :p1 OR (book.title <> :p2 OR book.title like :p3))");

        $this->assertCriteriaTranslation($c, $sql, $params, 'orWhere() accepts an array of named conditions with operator');
    }

    public function testMixedCriteria()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'foo');
        $c->add(BookEntityMap::FIELD_ID, array(1, 2), Criteria::IN);

        $sql = $this->getSql('SELECT  FROM book WHERE book.title = :p1 AND book.id IN (:p2,:p3)');

        $params =  array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 1),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'id', 'value' => 2)
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'ModelCriteria accepts Criteria operators');
    }

    public function testFilterBy()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterBy('Title', 'foo');

        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'filterBy() accepts a simple field name');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterBy('Title', 'foo', Criteria::NOT_EQUAL);

        $sql = $this->getSql('SELECT  FROM book WHERE book.title<>:p1');

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'filterBy() accepts a sicustom comparator');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->filterBy('Title', 'foo');

        $sql = $this->getSql('SELECT  FROM book WHERE book.title=:p1');

        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation(
            $c,
            $sql,
            $params,
            'filterBy() accepts a simple field name, even if initialized with an alias'
        );

    }

    public function testGetParams()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterBy('Title', 'foo');

        $expectedParams =  array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );

        $params = $c->getParams();

        $this->assertEquals($expectedParams, $params, 'test getting parameters with a simple criterion');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterBy('Title', 'foo', Criteria::LIKE);

        $expectedParams =  array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );

        $this->assertEquals($expectedParams, $params, 'test getting parameters with Specialized Criterion used for LIKE expressions');

    }

    public function testHaving()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->having('Propel\Tests\Bookstore\Book.Title <> ?', 'foo');

        $sql = "SELECT  FROM book HAVING book.title <> :p1";
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts a string clause');
    }

    public function testHavingConditions()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->having(array('cond1', 'cond2'));

        $sql = "SELECT  FROM book HAVING (book.title <> :p1 AND book.title like :p2)";
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => 'foo'),
            array('entity' => 'Propel\Tests\Bookstore\Book', 'field' => 'title', 'value' => '%bar%'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts an array of named conditions');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title <> ?', 'foo');
        $c->condition('cond2', 'Propel\Tests\Bookstore\Book.Title like ?', '%bar%');
        $c->having(array('cond1', 'cond2'), Criteria::LOGICAL_OR);

        $sql = "SELECT  FROM book HAVING (book.title <> :p1 OR book.title like :p2)";
        $this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts an array of named conditions with an operator');
    }

    /**
     * @group mysql
     */
    public function testHavingWithField()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->withField('SUBSTRING(Book.Title, 1, 4)', 'title_start');
        $c->having('title_start = ?', 'foo', \PDO::PARAM_STR);

        $sql = $this->getSql('SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, SUBSTRING(book.title, 1, 4) AS title_start FROM book HAVING title_start = :p1');

        $params = array(
            array('entity' => null, 'type' => 2, 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts a string clause');
        $c->find();

        $expected = $this->getSql('SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, SUBSTRING(book.title, 1, 4) AS title_start FROM book HAVING title_start = \'foo\'');

        $this->assertEquals($expected, Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection()->getLastExecutedQuery());
    }

    public function testOrderBy()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');

        $sql = 'SELECT  FROM book ORDER BY book.title ASC';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'orderBy() accepts a field name and adds an ORDER BY clause');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title', 'desc');

        $sql = 'SELECT  FROM book ORDER BY book.title DESC';
        $this->assertCriteriaTranslation($c, $sql, $params, 'orderBy() accepts an order parameter');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        try {
            $c->orderBy('Propel\Tests\Bookstore\Book.Foo');
            $this->fail('orderBy() throws an exception when called with an unknown field name');
        } catch (UnknownFieldException $e) {
            $this->assertTrue(true, 'orderBy() throws an exception when called with an unknown field name');
        }
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        try {
            $c->orderBy('Propel\Tests\Bookstore\Book.Title', 'foo');
            $this->fail('orderBy() throws an exception when called with an unknown order');
        } catch (UnexpectedValueException $e) {
            $this->assertTrue(true, 'orderBy() throws an exception when called with an unknown order');
        }
    }

    public function testOrderBySimpleField()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Title');

        $sql = 'SELECT  FROM book ORDER BY book.title ASC';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'orderBy() accepts a simple field name and adds an ORDER BY clause');
    }

    public function testOrderByAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->addAsField('t', BookEntityMap::FIELD_TITLE);
        $c->orderBy('t');

        $sql = 'SELECT book.title AS t FROM book ORDER BY t ASC';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'orderBy() accepts a field alias and adds an ORDER BY clause');
    }

    public function testGroupBy()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->groupBy('Propel\Tests\Bookstore\Book.authorId');

        $sql = 'SELECT  FROM book GROUP BY book.author_id';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupBy() accepts a field name and adds a GROUP BY clause');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        try {
            $c->groupBy('Book.Foo');
            $this->fail('groupBy() throws an exception when called with an unknown field name');
        } catch (UnknownFieldException $e) {
            $this->assertTrue(true, 'groupBy() throws an exception when called with an unknown field name');
        }
    }

    public function testGroupBySimpleField()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->groupBy('AuthorId');

        $sql = 'SELECT  FROM book GROUP BY book.author_id';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupBy() accepts a simple field name and adds a GROUP BY clause');
    }

    public function testGroupByAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->addAsField('t', BookEntityMap::FIELD_TITLE);
        $c->groupBy('t');

        $sql = 'SELECT book.title AS t FROM book GROUP BY t';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupBy() accepts a field alias and adds a GROUP BY clause');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\ClassNotFoundException
     */
    public function testGroupByClassThrowsExceptionOnUnknownClass()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->groupByClass('Author');
    }

    public function testGroupByClass()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->groupByClass('Propel\Tests\Bookstore\Book');

        $sql = 'SELECT  FROM book GROUP BY book.id,book.title,book.isbn,book.price,book.publisher_id,book.author_id';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupByClass() accepts a class name and adds a GROUP BY clause for all fields of the class');
    }

    public function testGroupByClassAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->groupByClass('b');

        $sql = 'SELECT  FROM book GROUP BY book.id,book.title,book.isbn,book.price,book.publisher_id,book.author_id';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupByClass() accepts a class alias and adds a GROUP BY clause for all fields of the class');
    }

    public function testGroupByClassTrueAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', true);
        $c->groupByClass('b');

        $sql = 'SELECT  FROM book GROUP BY b.id,b.title,b.isbn,b.price,b.publisher_id,b.author_id';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupByClass() accepts a true class alias and adds a GROUP BY clause for all fields of the class');
    }

    public function testGroupByClassJoinedModel()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->join('Propel\Tests\Bookstore\Author.book');
        $c->groupByClass('book');

        $sql = $this->getSql('SELECT  FROM author INNER JOIN book ON (author.id=book.author_id) GROUP BY book.id,book.title,book.isbn,book.price,book.publisher_id,book.author_id');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupByClass() accepts the class name of a joined model');
    }

    public function testGroupByClassJoinedModelWithAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->join('Propel\Tests\Bookstore\Author.book b');
        $c->groupByClass('b');

        $sql = $this->getSql('SELECT  FROM author INNER JOIN book b ON (author.id=b.author_id) GROUP BY b.id,b.title,b.isbn,b.price,b.publisher_id,b.author_id');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'groupByClass() accepts the alias of a joined model');
    }

    public function testDistinct()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->distinct();
        $sql = 'SELECT DISTINCT  FROM book';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'distinct() adds a DISTINCT clause');
    }

    public function testLimit()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->limit(10);
        $sql = 'SELECT  FROM book LIMIT 10';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'limit() adds a LIMIT clause');
        //test that limit 0 also works
        $c->limit(0);
        $sql = 'SELECT  FROM book LIMIT 0';
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'limit() adds a LIMIT clause');

    }

    public function testOffset()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->limit(50);
        $c->offset(10);
        if ($this->isDb('mysql')) {
            $sql = 'SELECT  FROM book LIMIT 10, 50';
        } else {
            $sql = 'SELECT  FROM book LIMIT 50 OFFSET 10';
        }
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'offset() adds an OFFSET clause');
    }

    public function testAddJoin()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->addJoin(BookEntityMap::FIELD_AUTHOR_ID, AuthorEntityMap::FIELD_ID);
        $c->addJoin(BookEntityMap::FIELD_PUBLISHER_ID, PublisherEntityMap::FIELD_ID);
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id) INNER JOIN publisher ON (book.publisher_id=publisher.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'addJoin() works the same as in Criteria');
    }

    public function testJoin()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() uses a relation to guess the fields');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        try {
            $c->join('Propel\Tests\Bookstore\Book.Foo');
            $this->fail('join() throws an exception when called with a non-existing relation');
        } catch (UnknownRelationException $e) {
            $this->assertTrue(true, 'join() throws an exception when called with a non-existing relation');
        }

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $c->where('Author.FirstName = ?', 'Leo');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id) WHERE author.first_name = :p1');
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Author', 'field' => 'firstName', 'value' => 'Leo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() uses a relation to guess the fields');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('author');
        $c->where('author.FirstName = ?', 'Leo');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id) WHERE author.first_name = :p1');
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Author', 'field' => 'firstName', 'value' => 'Leo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() uses the current model name when given a simple relation name');
    }

    public function testJoinQuery()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $c->where('author.FirstName = ?', 'Neal');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON (book.author_id=author.id) WHERE author.first_name = 'Neal'");
        $this->assertEquals($expectedSQL, Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection()->getLastExecutedQuery(), 'join() issues a real JOIN query');
        $this->assertEquals(1, count($books), 'join() issues a real JOIN query');
    }

    public function testJoinRelationName()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee');
        $c->join('Propel\Tests\Bookstore\BookstoreEmployee.supervisor');
        $sql = $this->getSql('SELECT  FROM  INNER JOIN bookstore_employee ON (bookstore_employee.supervisor_id=bookstore_employee.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() uses relation names as defined in schema.xml');
    }

    public function testJoinComposite()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\ReaderFavorite');
        $c->join('Propel\Tests\Bookstore\ReaderFavorite.bookOpinion');
        $sql = $this->getSql('SELECT  FROM reader_favorite INNER JOIN book_opinion ON (reader_favorite.book_id=book_opinion.book_id AND reader_favorite.reader_id=book_opinion.reader_id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() knows how to create a JOIN clause for relationships with composite fkeys');
    }

    public function testJoinType()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() adds an INNER JOIN by default');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() adds an INNER JOIN by default');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::LEFT_JOIN);
        $sql = $this->getSql('SELECT  FROM book LEFT JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() can add a LEFT JOIN');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::RIGHT_JOIN);
        $sql = $this->getSql('SELECT  FROM book RIGHT JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() can add a RIGHT JOIN');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', 'incorrect join');
        $sql = $this->getSql('SELECT  FROM book incorrect join author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() accepts any join string');
    }

    public function testJoinDirection()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for many to one relationship');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->join('Propel\Tests\Bookstore\Author.book');
        $sql = $this->getSql('SELECT  FROM author INNER JOIN book ON (author.id=book.author_id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to many relationship');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee');
        $c->join('Propel\Tests\Bookstore\BookstoreEmployee.bookstoreEmployeeAccount');
        $sql = $this->getSql('SELECT  FROM bookstore_employee INNER JOIN bookstore_employee_account ON (bookstore_employee.id=bookstore_employee_account.employee_id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to one relationship');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $c->join('Propel\Tests\Bookstore\BookstoreEmployeeAccount.employee');
        $sql = $this->getSql('SELECT  FROM bookstore_employee_account INNER JOIN bookstore_employee ON (bookstore_employee_account.employee_id=bookstore_employee.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to one relationship');
    }

    public function testJoinSeveral()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->join('Propel\Tests\Bookstore\Author.book');
        $c->join('book.publisher');
        $c->where('Publisher.Name = ?', 'foo');
        $sql = $this->getSql('SELECT  FROM author INNER JOIN book ON (author.id=book.author_id) INNER JOIN publisher ON (book.publisher_id=publisher.id) WHERE publisher.name = :p1');
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Publisher', 'field' => 'name', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() can guess relationships from related tables');
    }

    public function testJoinAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation on main alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('author');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author ON (book.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() can use a simple relation name when the model has an alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author a');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author a ON (book.author_id=a.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author a');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author a ON (book.author_id=a.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation alias on main alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author a');
        $c->where('a.FirstName = ?', 'Leo');
        $sql = $this->getSql('SELECT  FROM book INNER JOIN author a ON (book.author_id=a.id) WHERE a.first_name = :p1');
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Author', 'field' => 'firstName', 'value' => 'Leo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() allows the use of relation alias in where()');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author', 'a');
        $c->join('a.book b');
        $c->join('b.publisher p');
        $c->where('p.Name = ?', 'foo');
        $sql = $this->getSql('SELECT  FROM author INNER JOIN book b ON (author.id=b.author_id) INNER JOIN publisher p ON (b.publisher_id=p.id) WHERE p.name = :p1');
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\Publisher', 'field' => 'name', 'value' => 'foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() allows the use of relation alias in further join()');
    }

    public function testJoinTrueTableAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', true);
        $c->join('b.author');
        $sql = $this->getSql('SELECT  FROM book b INNER JOIN author ON (b.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation on true table alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', true);
        $c->join('author');
        $sql = $this->getSql('SELECT  FROM book b INNER JOIN author ON (b.author_id=author.id)');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation without alias name on true table alias');
    }

    public function testJoinOnSameTable()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee', 'be');
        $c->join('be.supervisor sup');
        $c->join('sup.subordinate sub');
        $c->where('sub.Name = ?', 'Foo');
        $sql = $this->getSql('SELECT  FROM bookstore_employee INNER JOIN bookstore_employee sup ON (bookstore_employee.supervisor_id=sup.id) INNER JOIN bookstore_employee sub ON (sup.id=sub.supervisor_id) WHERE sub.name = :p1');
        $params = array(
            array('entity' => 'Propel\Tests\Bookstore\BookstoreEmployee', 'field' => 'name', 'value' => 'Foo'),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'join() allows two joins on the same table thanks to aliases');
    }

    public function testJoinAliasQuery()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author a');
        $c->where('a.FirstName = ?', 'Leo');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'join() allows the use of relation alias in where()');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee', 'be');
        $c->join('be.supervisor sup');
        $c->join('sup.subordinate sub');
        $c->where('sub.Name = ?', 'Foo');
        $employees = BookstoreEmployeeQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT bookstore_employee.id, bookstore_employee.class_key, bookstore_employee.name, bookstore_employee.job_title, bookstore_employee.supervisor_id FROM bookstore_employee INNER JOIN bookstore_employee sup ON (bookstore_employee.supervisor_id=sup.id) INNER JOIN bookstore_employee sub ON (sup.id=sub.supervisor_id) WHERE sub.name = 'Foo'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'join() allows the use of relation alias in further joins()');
    }

    public function testAddJoinConditionSimple()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $c->addJoinCondition('author', 'Propel\Tests\Bookstore\Book.Title IS NOT NULL');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON (book.author_id=author.id AND book.title IS NOT NULL)");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'addJoinCondition() allows the use of custom conditions');
    }

    public function testAddJoinConditionBinding()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $c->addJoinCondition('author', 'Propel\Tests\Bookstore\Book.Title = ?', 'foo');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON (book.author_id=author.id AND book.title = 'foo')");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'addJoinCondition() allows the use of custom conditions with values to bind');
    }

    public function testAddJoinConditionSeveral()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $c->addJoinCondition('author', 'Propel\Tests\Bookstore\Book.Title = ?', 'foo');
        $c->addJoinCondition('author', 'Propel\Tests\Bookstore\Book.isbn IS NOT NULL');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON ((book.author_id=author.id AND book.title = 'foo') AND book.isbn IS NOT NULL)");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'addJoinCondition() allows the use of several custom conditions');
    }

    public function testAddJoinConditionBindingAndWhere()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Title LIKE ?', 'foo%');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $c->addJoinCondition('author', 'Propel\Tests\Bookstore\Book.Title = ?', 'foo');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON (book.author_id=author.id AND book.title = 'foo') WHERE book.title LIKE 'foo%'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'addJoinCondition() allows the use of custom conditions with values and lives well with WHERE conditions');
    }

    public function testAddJoinConditionAlias()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author a', Criteria::INNER_JOIN);
        $c->addJoinCondition('a', 'Book.Title IS NOT NULL');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author a ON (book.author_id=a.id AND book.title IS NOT NULL)");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'addJoinCondition() allows the use of custom conditions even on aliased relations');
    }

    public function testAddJoinConditionOperator()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $c->addJoinCondition('author', 'Propel\Tests\Bookstore\Book.Title IS NOT NULL', null, Criteria::LOGICAL_OR);
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON (book.author_id=author.id OR book.title IS NOT NULL)");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'addJoinCondition() allows the use of custom conditions with a custom operator');
    }

    public function testSetJoinConditionCriterion()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $criterion = $c->getNewCriterion(BookEntityMap::FIELD_TITLE, BookEntityMap::FIELD_TITLE . ' = ' . AuthorEntityMap::FIELD_FIRST_NAME, Criteria::CUSTOM);
        $c->setJoinCondition('author', $criterion);
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON book.title = author.first_name");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'setJoinCondition() can override a previous join condition with a Criterion');
    }

    public function testSetJoinConditionNamedCondition()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author', Criteria::INNER_JOIN);
        $c->condition('cond1', 'Propel\Tests\Bookstore\Book.Title = Author.FirstName');
        $c->setJoinCondition('author', 'cond1');
        $books = BookQuery::create(null, $c)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author ON book.title = author.first_name");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'setJoinCondition() can override a previous join condition with a named condition');
    }

    public function testGetJoin()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');

        $joins = $c->getJoins();
        $this->assertEquals($joins['author'], $c->getJoin('author'), "getJoin() returns a specific Join from the ModelCriteria");
    }

    public function testWith()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $c->with('author');
        $withs = $c->getWith();
        $this->assertTrue(array_key_exists('author', $withs), 'with() adds an entry to the internal list of Withs');
        $this->assertInstanceOf('Propel\Runtime\ActiveQuery\ModelWith', $withs['author'], 'with() references the ModelWith object');
    }

    /**
     * @expectedException \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     */
    public function testWithThrowsExceptionWhenJoinLacks()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->with('Propel\Tests\Bookstore\Author');
    }

    public function testWithAlias()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author a');
        $c->with('a');
        $withs = $c->getWith();
        $this->assertTrue(array_key_exists('a', $withs), 'with() uses the alias for the index of the internal list of Withs');
    }

    /**
     * @expectedException \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     */
    public function testWithThrowsExceptionWhenNotUsingAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author a');
        $c->with('Propel\Tests\Bookstore\Author');
    }

    public function testWithAddsSelectFields()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $c->with('author');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'with() adds the fields of the related table');
    }

    public function testWithAliasAddsSelectFields()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author a');
        $c->with('a');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            'a.id',
            'a.firstName',
            'a.lastName',
            'a.email',
            'a.age'
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'with() adds the fields of the related table');
    }

    public function testWithAddsSelectFieldsOfMainTable()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Propel\Tests\Bookstore\Book.author');
        $c->with('author');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'with() adds the fields of the main table if required');
    }

    public function testWithAliasAddsSelectFieldsOfMainTable()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', true);
        $c->join('b.author a');
        $c->with('a');
        $expectedFields = array(
            'b.id',
            'b.title',
            'b.ISBN',
            'b.price',
            'b.publisherId',
            'b.authorId',
            'a.id',
            'a.firstName',
            'a.lastName',
            'a.email',
            'a.age'
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'with() adds the fields of the main table with an alias if required');
    }

    public function testWithOneToManyAddsSelectFields()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->leftJoin('Propel\Tests\Bookstore\Author.book');
        $c->with('book');
        $expectedFields = array(
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE,
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'with() adds the fields of the related table even in a one-to-many relationship');
    }

    public function testJoinWith()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->joinWith('Propel\Tests\Bookstore\Book.author');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'joinWith() adds the join');
        $joins = $c->getJoins();
        $join = $joins['author'];
        $this->assertEquals(Criteria::INNER_JOIN, $join->getJoinType(), 'joinWith() adds an INNER JOIN by default');
    }

    public function testJoinWithType()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->joinWith('Propel\Tests\Bookstore\Book.author', Criteria::LEFT_JOIN);
        $joins = $c->getJoins();
        $join = $joins['author'];
        $this->assertEquals(Criteria::LEFT_JOIN, $join->getJoinType(), 'joinWith() accepts a join type as second parameter');
    }

    public function testJoinWithAlias()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->joinWith('Propel\Tests\Bookstore\Book.author a');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            'a.id',
            'a.firstName',
            'a.lastName',
            'a.email',
            'a.age'
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'joinWith() adds the join with the alias');
    }

    public function testJoinWithSeveral()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Review');
        $c->joinWith('Review.book');
        $c->joinWith('book.author');
        $c->joinWith('book.publisher');
        $expectedFields = array(
            ReviewEntityMap::FIELD_ID,
            ReviewEntityMap::FIELD_REVIEWED_BY,
            ReviewEntityMap::FIELD_REVIEW_DATE,
            ReviewEntityMap::FIELD_RECOMMENDED,
            ReviewEntityMap::FIELD_STATUS,
            ReviewEntityMap::FIELD_BOOK_ID,
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE,
            PublisherEntityMap::FIELD_ID,
            PublisherEntityMap::FIELD_NAME
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'joinWith() adds the with');
        $joins = $c->getJoins();
        $expectedJoinKeys = array('book', 'author', 'publisher');
        $this->assertEquals($expectedJoinKeys, array_keys($joins), 'joinWith() adds the join');
    }

    public function testJoinWithTwice()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->join('Book.review');
        $c->joinWith('Book.author');
        $c->joinWith('Book.review');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE,
            ReviewEntityMap::FIELD_ID,
            ReviewEntityMap::FIELD_REVIEWED_BY,
            ReviewEntityMap::FIELD_REVIEW_DATE,
            ReviewEntityMap::FIELD_RECOMMENDED,
            ReviewEntityMap::FIELD_STATUS,
            ReviewEntityMap::FIELD_BOOK_ID,
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'joinWith() adds the with');
        $joins = $c->getJoins();
        $expectedJoinKeys = array('review', 'author');
        $this->assertEquals($expectedJoinKeys, array_keys($joins), 'joinWith() adds the join');
    }

    public static function conditionsForTestWithField()
    {
        return array(
            array('Propel\Tests\Bookstore\Book.Title', 'BookTitle', 'book.title AS BookTitle'),
            array('Book.Title', null, 'book.title AS BookTitle'),
            array('UPPER(Book.Title)', null, 'UPPER(book.title) AS UPPERBookTitle'),
            array('CONCAT(Propel\Tests\Bookstore\Book.Title, Propel\Tests\Bookstore\Book.isbn)', 'foo', 'CONCAT(book.title, book.isbn) AS foo'),
        );
    }

    /**
     * @dataProvider conditionsForTestWithField
     */
    public function testWithField($clause, $alias, $selectTranslation)
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->withField($clause, $alias);
        $sql = $this->getSql('SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, ' . $selectTranslation . ' FROM book');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'withField() adds a calculated field to the select clause');
    }

    public static function conditionsForTestWithFieldAndQuotes()
    {
        return array(
            // Examples for simple string concatenation needed for MSSQL.
            // MSSQL has no CONCAT() function so uses + to join strings.
            array("CONVERT(varchar, Propel\Tests\Bookstore\Author.Age, 120) + \' GMT\'", 'GMTCreatedAt', "CONVERT(varchar, author.age, 120) + \' GMT\' AS GMTCreatedAt"),
            array("(Propel\Tests\Bookstore\Author.FirstName + ' ' + Propel\Tests\Bookstore\Author.LastName)", 'AuthorFullname', "(author.first_name + ' ' + author.last_name) AS AuthorFullname"),
            array("('\"' + Propel\Tests\Bookstore\Author.FirstName + ' ' + Propel\Tests\Bookstore\Author.LastName + '\"')", 'QuotedAuthorFullname', "('\"' + author.first_name + ' ' + author.last_name + '\"') AS QuotedAuthorFullname"),

            // Examples for simple string concatenation needed for Sqlite
            // Sqlite has no CONCAT() function so uses || to join strings.  || can also be used to join strings in PQSql and Oracle
            array("(Propel\Tests\Bookstore\Author.FirstName || ' ' || Propel\Tests\Bookstore\Author.LastName)", 'AuthorFullname', "(author.first_name || ' ' || author.last_name) AS AuthorFullname"),
            array("('\"' || Propel\Tests\Bookstore\Author.FirstName || ' ' || Propel\Tests\Bookstore\Author.LastName || '\"')", 'QuotedAuthorFullname', "('\"' || author.first_name || ' ' || author.last_name || '\"') AS QuotedAuthorFullname"),
        );
    }

    /**
     * @dataProvider conditionsForTestWithFieldAndQuotes
     */
    public function testWithFieldAndQuotes($clause, $alias, $selectTranslation)
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->withField($clause, $alias);
        $sql = $this->getSql('SELECT author.id, author.first_name, author.last_name, author.email, author.age, ' . $selectTranslation . ' FROM author');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'withField() adds a calculated field using quotes to the select clause');
    }

    public function testWithFieldAndSelectFields()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->withField('UPPER(Propel\Tests\Bookstore\Book.Title)', 'foo');
        $sql = $this->getSql('SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, UPPER(book.title) AS foo FROM book');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'withField() adds the object fields if the criteria has no select fields');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->addSelectField('book.id');
        $c->withField('UPPER(Propel\Tests\Bookstore\Book.Title)', 'foo');
        $sql = $this->getSql('SELECT book.id, UPPER(book.title) AS foo FROM book');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'withField() does not add the object fields if the criteria already has select fields');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->addSelectField('book.id');
        $c->withField('UPPER(Propel\Tests\Bookstore\Book.Title)', 'foo');
        $c->addSelectField('book.title');
        $sql = $this->getSql('SELECT book.id, book.title, UPPER(book.title) AS foo FROM book');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'withField() does adds as field after the select fields even though the withField() method was called first');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->addSelectField('book.id');
        $c->withField('UPPER(Propel\Tests\Bookstore\Book.Title)', 'foo');
        $c->withField('UPPER(Propel\Tests\Bookstore\Book.isbn)', 'isbn');
        $sql = $this->getSql('SELECT book.id, UPPER(book.title) AS foo, UPPER(book.isbn) AS isbn FROM book');
        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'withField() called repeatedly adds several as fields');
    }

    public function testKeepQuery()
    {
        $c = BookQuery::create();
        $this->assertTrue($c->isKeepQuery(), 'keepQuery is enabled by default');
        $c->keepQuery(false);
        $this->assertFalse($c->isKeepQuery(), 'keepQuery(false) disables the keepQuery property');
        $c->keepQuery();
        $this->assertTrue($c->isKeepQuery(), 'keepQuery() enables the keepQuery property');
    }

    public function testKeepQueryFind()
    {
        $c = BookQuery::create();
        $c->filterByTitle('foo');
        $c->find();
        $this->assertEquals(array(), $c->getSelectFields(), 'find() clones the query by default');

        $c = BookQuery::create();
        $c->setEntityAlias('book', true);
        $c->filterByTitle('foo');
        $c->keepQuery(false);
        $c->find();
        $expected = array('book.id', 'book.title', 'book.ISBN', 'book.price', 'book.publisherId', 'book.authorId');
        $this->assertEquals($expected, $c->getSelectFields(), 'keepQuery(false) forces find() to use the original query');
    }

    public function testKeepQueryFindOne()
    {
        $c = BookQuery::create();
        $c->filterByTitle('foo');
        $c->findOne();
        $this->assertEquals(-1, $c->getLimit(), 'findOne() clones the query by default');

        $c = BookQuery::create();
        $c->filterByTitle('foo');
        $c->keepQuery(false);
        $c->findOne();
        $this->assertEquals(1, $c->getLimit(), 'keepQuery(false) forces findOne() to use the original query');
    }

    public function testKeepQueryFindPk()
    {
        $c = BookQuery::create();
        $c->findPk(1);
        $this->assertEquals(array(), $c->getSelectFields(), 'findPk() clones the query by default');

        $c = BookQuery::create('b');
        $c->keepQuery(false);
        $c->findPk(1);
        $expected = array(
            'Propel\Tests\Bookstore\Book.id',
            'Propel\Tests\Bookstore\Book.title',
            'Propel\Tests\Bookstore\Book.ISBN',
            'Propel\Tests\Bookstore\Book.price',
            'Propel\Tests\Bookstore\Book.publisherId',
            'Propel\Tests\Bookstore\Book.authorId'
        );
        $this->assertEquals($expected, $c->getSelectFields(),
            'keepQuery(false) forces findPk() to use the original query');
    }

    public function testKeepQueryCount()
    {
        $c = BookQuery::create();
        $c->orderByTitle();
        $c->count();
        $this->assertEquals(array('book.title ASC'), $c->getOrderByFields(), 'count() clones the query by default');

        $c = BookQuery::create();
        $c->orderByTitle();
        $c->keepQuery(false);
        $c->count();
        $this->assertEquals(array(), $c->getOrderByFields(), 'keepQuery() forces count() to use the original query');
    }

    public function testFind()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $books = $c->find();
        $this->assertTrue($books instanceof Collection, 'find() returns a collection by default');
        $this->assertEquals(0, count($books), 'find() returns an empty array when the query returns no result');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author a');
        $c->where('a.FirstName = ?', 'Neal');
        $books = $c->find();
        $this->assertTrue($books instanceof Collection, 'find() returns a collection by default');
        $this->assertEquals(1, count($books), 'find() returns as many rows as the results in the query');
        $book = $books->shift();
        $this->assertTrue($book instanceof Book, 'find() returns an array of Model objects by default');
        $this->assertEquals('Quicksilver', $book->getTitle(), 'find() returns the model objects matching the query');
    }

    public function testFindAddsSelectFields()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->find();
        $sql = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book");
        $this->assertEquals($sql, $con->getLastExecutedQuery(), 'find() adds the select fields of the current model');
    }

    public function testFindTrueAliasAddsSelectFields()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', true);
        $books = $c->find();
        $sql = $this->getSql("SELECT b.id, b.title, b.isbn, b.price, b.publisher_id, b.author_id FROM book b");
        $this->assertEquals($sql, $con->getLastExecutedQuery(), 'find() uses the true model alias if available');
    }

    public function testFindOne()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $book = $c->findOne();
        $this->assertNull($book, 'findOne() returns null when the query returns no result');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->orderBy('b.Title');
        $book = $c->findOne();
        $this->assertTrue($book instanceof Book, 'findOne() returns a Model object by default');
        $this->assertEquals('Don Juan', $book->getTitle(), 'find() returns the model objects matching the query');
    }

    public function testFindOneOrCreateNotExists()
    {
        BookQuery::create()->deleteAll();
        $book = BookQuery::create('b')
            ->where('b.Title = ?', 'foo')
            ->filterByPrice(125)
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book, 'findOneOrCreate() returns an instance of the model when the request has no result');
        $this->assertTrue($book->isNew(), 'findOneOrCreate() returns a new instance of the model when the request has no result');
        $this->assertEquals('foo', $book->getTitle(), 'findOneOrCreate() returns a populated objects based on the conditions');
        $this->assertEquals(125, $book->getPrice(), 'findOneOrCreate() returns a populated objects based on the conditions');
    }

    public function testFindOneOrCreateNotExistsFormatter()
    {
        BookQuery::create()->deleteAll();
        $book = BookQuery::create('b')
            ->where('b.Title = ?', 'foo')
            ->filterByPrice(125)
            ->setFormatter(ModelCriteria::FORMAT_ARRAY)
            ->findOneOrCreate();
        $this->assertTrue(is_array($book), 'findOneOrCreate() uses the query formatter even when the request has no result');
        $this->assertEquals('foo', $book['title'], 'findOneOrCreate() returns a populated array based on the conditions');
        $this->assertEquals(125, $book['price'], 'findOneOrCreate() returns a populated array based on the conditions');
    }

    public function testFindOneOrCreateExists()
    {
        BookQuery::create()->deleteAll();
        $book = new Book();
        $book->setTitle('foo');
        $book->setISBN('FA404');
        $book->setPrice(125);
        $book->save();
        $book = BookQuery::create('b')
            ->where('b.Title = ?', 'foo')
            ->filterByPrice(125)
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book, 'findOneOrCreate() returns an instance of the model when the request has one result');
        $this->assertFalse($book->isNew(), 'findOneOrCreate() returns an existing instance of the model when the request has one result');
        $this->assertEquals('foo', $book->getTitle(), 'findOneOrCreate() returns a populated objects based on the conditions');
        $this->assertEquals(125, $book->getPrice(), 'findOneOrCreate() returns a populated objects based on the conditions');
    }

    /**
     * @expectedException Propel\Runtime\Exception\PropelException
     */
    public function testFindOneOrCreateThrowsExceptionWhenQueryContainsJoin()
    {
        $book = BookQuery::create('b')
            ->filterByPrice(125)
            ->useAuthorQuery()
                ->filterByFirstName('Leo')
            ->endUse()
            ->findOneOrCreate();
    }

    public function testFindOneOrCreateMakesOneQueryWhenRecordNotExists()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        BookQuery::create()->deleteAll();
        $count = $con->getQueryCount();
        $book = BookQuery::create('b')
            ->filterByPrice(125)
            ->findOneOrCreate();
        $this->assertEquals($count + 1, $con->getQueryCount(), 'findOneOrCreate() makes only a single query when the record doesn\'t exist');
    }

    public function testFindOneOrCreateMakesOneQueryWhenRecordExists()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        BookQuery::create()->deleteAll();
        $book = new Book();
        $book->setTitle('Title');
        $book->setISBN('FA404');
        $book->setPrice(125);
        $book->save();
        $count = $con->getQueryCount();
        $book = BookQuery::create('b')
            ->filterByPrice(125)
            ->findOneOrCreate();
        $this->assertEquals($count + 1, $con->getQueryCount(), 'findOneOrCreate() makes only a single query when the record exists');
    }

    public function testFindOneOrCreateWithEnums()
    {
        Book2Query::create()->deleteAll();

        $book = Book2Query::create('b')
            ->where('b.Title = ?', 'bar')
            ->filterByStyle('poetry')
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book2, 'findOneOrCreate() returns an instance of the model when the request has no result');
        $this->assertTrue($book->isNew(), 'findOneOrCreate() returns a new instance of the model when the request has no result');
        $this->assertEquals('bar', $book->getTitle(), 'findOneOrCreate() returns a populated objects based on the conditions');
        $this->assertEquals('poetry', $book->getStyle(), 'findOneOrCreate() returns a populated objects based on the conditions');

        $book = Book2Query::create('b')
            ->where('b.Title = ?', 'foobar')
            ->filterByStyle('essay')
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book2, 'findOneOrCreate() returns an instance of the model when the request has no result');
        $this->assertTrue($book->isNew(), 'findOneOrCreate() returns a new instance of the model when the request has no result');
        $this->assertEquals('foobar', $book->getTitle(), 'findOneOrCreate() returns a populated objects based on the conditions');
        $this->assertEquals('essay', $book->getStyle(), 'findOneOrCreate() returns a populated objects based on the conditions');

        $book = Book2Query::create('b')
            ->where('b.Style = ?', 'novel')
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book2, 'findOneOrCreate() returns an instance of the model when the request has no result');
        $this->assertTrue($book->isNew(), 'findOneOrCreate() returns a new instance of the model when the request has no result');
        $this->assertEquals('novel', $book->getStyle(), 'findOneOrCreate() returns a populated objects based on the conditions');
    }

    public function testFindOneOrCreateWithArrays()
    {
        $this->markTestSkipped('Array fields from query object. Where is this converted?');
        Book2Query::create()->deleteAll();

        $book = Book2Query::create('b')
            ->filterByTag('russian')
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book2, 'findOneOrCreate() returns an instance of the model when the request has no result');
        $this->assertTrue($book->isNew(), 'findOneOrCreate() returns a new instance of the model when the request has no result');
        $this->assertTrue(is_array($book->getTags()), 'findOneOrCreate() returns a populated objects based on the conditions');
        $this->assertSame(array('russian'), $book->getTags(), 'findOneOrCreate() returns a populated objects based on the conditions');

        $book = Book2Query::create('b')
            ->filterByTags(array('poetry'))
            ->findOneOrCreate();
        $this->assertTrue($book instanceof Book2, 'findOneOrCreate() returns an instance of the model when the request has no result');
        $this->assertTrue($book->isNew(), 'findOneOrCreate() returns a new instance of the model when the request has no result');
        $this->assertTrue(is_array($book->getTags()), 'findOneOrCreate() returns a populated objects based on the conditions');
        $this->assertSame(array('poetry'), $book->getTags(), 'findOneOrCreate() returns a populated objects based on the conditions');
    }

    public function testFindPkSimpleKey()
    {
        BookstoreDataPopulator::depopulate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $book = $c->findPk(765432);
        $this->assertNull($book, 'findPk() returns null when the primary key is not found');

        BookstoreDataPopulator::populate();

        // retrieve the test data
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $testBook = $c->findOne();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $book = $c->findPk($testBook->getId());
        $this->assertEquals($testBook, $book, 'findPk() returns a model object corresponding to the pk');
    }

    public function testFindPksSimpleKey()
    {
        BookstoreDataPopulator::depopulate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->findPks(array(765432, 434535));
        $this->assertTrue($books instanceof Collection, 'findPks() returns a Collection');
        $this->assertEquals(0, count($books), 'findPks() returns an empty collection when the primary keys are not found');

        BookstoreDataPopulator::populate();

        // retrieve the test data
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $testBooks = $c->find();
        $testBook1 = $testBooks->pop();
        $testBook2 = $testBooks->pop();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->findPks(array($testBook1->getId(), $testBook2->getId()));
        $this->assertEquals(array($testBook2, $testBook1), $books->getData(), 'findPks() returns an array of model objects corresponding to the pks');
    }

    public function testFindPkCompositeKey()
    {
        BookstoreDataPopulator::depopulate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookListRel');
        $bookListRel = $c->findPk(array(1, 2));
        $this->assertNull($bookListRel, 'findPk() returns null when the composite primary key is not found');

        BookstoreDataPopulator::populate();

        // save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->find();
        foreach ($books as $book) {
            $book->save();
        }

        // retrieve the test data
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookListRel');
        $bookListRelTest = $c->findOne();
        $pk = Configuration::getCurrentConfiguration()->getEntityMap('Propel\Tests\Bookstore\BookListRel')->getPK($bookListRelTest);

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookListRel');
        $bookListRel = $c->findPk($pk);
        $this->assertEquals($bookListRelTest, $bookListRel, 'findPk() can find objects with composite primary keys');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testFindPksCompositeKey()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookListRel');
        $bookListRel = $c->findPks(array(array(1, 2)));

    }

    public function testFindBy()
    {
        try {
            $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
            $books = $c->findBy('Foo', 'Bar');
            $this->fail('findBy() throws an exception when called on an unknown field name');
        } catch (UnknownFieldException $e) {
            $this->assertTrue(true, 'findBy() throws an exception when called on an unknown field name');
        }

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->findBy('Title', 'Don Juan', $con);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findBy() adds simple field conditions');
        $this->assertTrue($books instanceof Collection, 'findBy() issues a find()');
        $this->assertEquals(1, count($books), 'findBy() adds simple field conditions');
        $book = $books->shift();
        $this->assertTrue($book instanceof Book, 'findBy() returns an array of Model objects by default');
        $this->assertEquals('Don Juan', $book->getTitle(), 'findBy() returns the model objects matching the query');
    }

    public function testFindByArray()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->findByArray(array('Title' => 'Don Juan', 'ISBN' => 12345), $con);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan' AND book.isbn=12345");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByArray() adds multiple field conditions');
    }

    public function testFindOneBy()
    {
        try {
            $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
            $book = $c->findOneBy('Foo', 'Bar');
            $this->fail('findOneBy() throws an exception when called on an unknown field name');
        } catch (UnknownFieldException $e) {
            $this->assertTrue(true, 'findOneBy() throws an exception when called on an unknown field name');
        }

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $book = $c->findOneBy('Title', 'Don Juan', $con);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan' LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneBy() adds simple field conditions');
        $this->assertTrue($book instanceof Book, 'findOneBy() returns a Model object by default');
        $this->assertEquals('Don Juan', $book->getTitle(), 'findOneBy() returns the model object matching the query');
    }

    public function testFindOneByArray()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $book = $c->findOneByArray(array('Title' => 'Don Juan', 'ISBN' => 12345), $con);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan' AND book.isbn=12345 LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneBy() adds multiple field conditions');
    }

    public function testGetIteratorReturnsATraversable()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $this->assertInstanceOf('Traversable', $c->getIterator());
    }

    public function testGetIteratorAllowsTraversingQueryObjects()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $nbResults = 0;
        foreach ($c as $book) {
            $nbResults++;
        }
        $this->assertEquals(4, $nbResults);
    }

    public function testGetIteratorReturnsATraversableWithArrayFormatter()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $this->assertInstanceOf('Traversable', $c->getIterator());
    }

    public function testGetIteratorAllowsTraversingQueryObjectsWithArrayFormatter()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $nbResults = 0;
        foreach ($c as $book) {
            $nbResults++;
        }
        $this->assertEquals(4, $nbResults);
    }

    public function testGetIteratorReturnsATraversableWithOnDemandFormatter()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $it = $c->getIterator();
        $this->assertInstanceOf('Traversable', $it);
        $it->closeCursor();
    }

    public function testGetIteratorAllowsTraversingQueryObjectsWithOnDemandFormatter()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $nbResults = 0;
        foreach ($c as $book) {
            $nbResults++;
        }
        $this->assertEquals(4, $nbResults);
    }

    public function testGetIteratorReturnsATraversableWithStatementFormatter()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_STATEMENT);
        $this->assertInstanceOf('Traversable', $c->getIterator());
    }

    public function testGetIteratorAllowsTraversingQueryObjectsWithStatementFormatter()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_STATEMENT);
        $nbResults = 0;
        foreach ($c as $book) {
            $nbResults++;
        }
        $this->assertEquals(4, $nbResults);
    }

    public function testGetIteratorReturnsATraversableWithSimpleArrayFormatter()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Id');
        $this->assertInstanceOf('Traversable', $c->getIterator());
    }

    public function testGetIteratorAllowsTraversingQueryObjectsWithSimpleArrayFormatter()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->select('Id');
        $nbResults = 0;
        foreach ($c as $book) {
            $nbResults++;
        }
        $this->assertEquals(4, $nbResults);
    }

    public function testCount()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $nbBooks = $c->count();
        $this->assertTrue(is_int($nbBooks), 'count() returns an integer');
        $this->assertEquals(0, $nbBooks, 'count() returns 0 when the query returns no result');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author a');
        $c->where('a.FirstName = ?', 'Neal');
        $nbBooks = $c->count();
        $this->assertTrue(is_int($nbBooks), 'count() returns an integer');
        $this->assertEquals(1, $nbBooks, 'count() returns the number of results in the query');
    }

    public function testPaginate()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->join('b.author a');
        $c->where('a.FirstName = ?', 'Neal');
        $books = $c->paginate(1, 5);
        $this->assertTrue($books instanceof PropelModelPager, 'paginate() returns a PropelModelPager');
        $this->assertEquals(1, count($books), 'paginate() returns a countable pager with the correct count');
        foreach ($books as $book) {
            $this->assertEquals('Neal', $book->getAuthor()->getFirstName(), 'paginate() returns an iterable pager');
        }
    }

    public function testDelete()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        try {
            $nbBooks = $c->delete();
            $this->fail('delete() throws an exception when called on an empty Criteria');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'delete() throws an exception when called on an empty Criteria');
        }

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $nbBooks = $c->delete();
        $this->assertTrue(is_int($nbBooks), 'delete() returns an integer');
        $this->assertEquals(0, $nbBooks, 'delete() returns 0 when the query deleted no rows');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->delete();
        $this->assertTrue(is_int($nbBooks), 'delete() returns an integer');
        $this->assertEquals(1, $nbBooks, 'delete() returns the number of the deleted rows');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $nbBooks = $c->count();
        $this->assertEquals(3, $nbBooks, 'delete() deletes rows in the database');
    }

    public function testDeleteUsingTableAlias()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', false);
        $c->where('b.Title = ?', 'foo');
        $c->delete();
        $expectedSQL = $this->getSql("DELETE FROM book WHERE book.title = 'foo'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'delete() also works on tables with table alias');

        if ($this->runningOnMySQL() || $this->runningOnPostgreSQL()) {
            $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
            $c->setEntityAlias('b', true);
            $c->where('b.Title = ?', 'foo');
            $c->delete();
            if (!$this->runningOnMySQL()) {
                $expectedSQL = $this->getSql("DELETE FROM book AS b WHERE b.title = 'foo'");
            } else {
                $expectedSQL = $this->getSql("DELETE b FROM book AS b WHERE b.title = 'foo'");
            }
            $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'delete() also works on tables with true table alias');
        }
    }

    public function testDeleteAll()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $nbBooks = $c->deleteAll();
        $this->assertTrue(is_int($nbBooks), 'deleteAll() returns an integer');
        $this->assertEquals(4, $nbBooks, 'deleteAll() returns the number of deleted rows');

        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->deleteAll();
        $this->assertEquals(4, $nbBooks, 'deleteAll() ignores conditions on the criteria');
    }

    public function testUpdate()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $count = $con->getQueryCount();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $nbBooks = $c->update(array('Title' => 'foo'));
        $this->assertEquals(4, $nbBooks, 'update() returns the number of updated rows');
        $this->assertEquals($count + 1, $con->getQueryCount(), 'update() updates all the objects in one query by default');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'foo');
        $nbBooks = $c->count();
        $this->assertEquals(4, $nbBooks, 'update() updates all records by default');

        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $count = $con->getQueryCount();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $nbBooks = $c->update(array('ISBN' => '3456'));
        $this->assertEquals(1, $nbBooks, 'update() updates only the records matching the criteria');
        $this->assertEquals($count + 1, $con->getQueryCount(), 'update() updates all the objects in one query by default');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->where('b.Title = ?', 'Don Juan');
        $book = $c->findOne();
        $this->assertEquals('3456', $book->getISBN(), 'update() updates only the records matching the criteria');
    }

    public function testUpdateUsingTableAlias()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('b', false);
        $c->where('b.Title = ?', 'foo');
        $c->update(array('title' => 'foo2'), $con);
        $expectedSQL = $this->getSql("UPDATE book SET title='foo2'  WHERE book.title = 'foo'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'update() also works on tables with table alias');

        if ($this->runningOnMySQL() || $this->runningOnPostgreSQL()) {
            $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
            $c->setEntityAlias('b', true);
            $c->where('b.Title = ?', 'foo');
            $c->update(array('Title' => 'foo2'), $con);
            $expectedSQL = $this->getSql("UPDATE book b SET title='foo2'  WHERE b.title = 'foo'");
            $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'update() also works on tables with true table alias');
        }
    }

    public static function conditionsForTestGetRelationName()
    {
        return array(
            array('Author', 'Author'),
            array('Book.author', 'author'),
            array('Author.Book', 'Book'),
            array('Book.author a', 'a'),
        );
    }

    /**
     * @dataProvider conditionsForTestGetRelationName
     */
    public function testGetRelationName($relation, $relationName)
    {
        $this->assertEquals($relationName, ModelCriteria::getRelationName($relation));
    }

    public function testMagicJoin()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->leftJoin('b.author a');
        $c->where('a.FirstName = ?', 'Leo');
        $books = $c->findOne();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo' LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'leftJoin($x) is turned into join($x, Criteria::LEFT_JOIN)');

        $books = BookQuery::create()
            ->leftJoinAuthor('a')
            ->where('a.FirstName = ?', 'Leo')
            ->findOne();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo' LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'leftJoinX() is turned into join($x, Criteria::LEFT_JOIN)');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->innerJoin('b.author a');
        $c->where('a.FirstName = ?', 'Leo');
        $books = $c->findOne();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo' LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'innerJoin($x) is turned into join($x, Criteria::INNER_JOIN)');

        $books = BookQuery::create()
            ->innerJoinAuthor('a')
            ->where('a.FirstName = ?', 'Leo')
            ->findOne();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book INNER JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo' LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'innerJoinX() is turned into join($x, Criteria::INNER_JOIN)');

        if (!$this->runningOnSQLite()) {
            //SQLITE: SQLSTATE[HY000]: General error: 1 RIGHT and FULL OUTER JOINs are not currently supported
            $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
            $c->rightJoin('b.author a');
            $c->where('a.FirstName = ?', 'Leo');
            $books = $c->findOne();
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book RIGHT JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo' LIMIT 1");
            $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'rightJoin($x) is turned into join($x, Criteria::RIGHT_JOIN)');

            $books = BookQuery::create()
                ->rightJoinAuthor('a')
                ->where('a.FirstName = ?', 'Leo')
                ->findOne();
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book RIGHT JOIN author a ON (book.author_id=a.id) WHERE a.first_name = 'Leo' LIMIT 1");
            $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'rightJoinX() is turned into join($x, Criteria::RIGHT_JOIN)');

            $books = BookQuery::create()
                ->leftJoinAuthor()
                ->where('Author.FirstName = ?', 'Leo')
                ->findOne();
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE author.first_name = 'Leo' LIMIT 1");
            $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'leftJoinX() is turned into join($x, Criteria::LEFT_JOIN)');
        }

    }

    public function testMagicJoinWith()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->leftJoinWith('author a');
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            'a.id',
            'a.firstName',
            'a.lastName',
            'a.email',
            'a.age'
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'leftJoinWith() adds the join with the alias');
        $joins = $c->getJoins();
        $join = $joins['a'];
        $this->assertEquals(Criteria::LEFT_JOIN, $join->getJoinType(), 'leftJoinWith() adds a LEFT JOIN');
    }

    public function testMagicJoinWithRelation()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->joinWithAuthor();
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'joinWithXXX() adds the join with the XXX relation');
        $joins = $c->getJoins();
        $join = $joins['author'];
        $this->assertEquals(Criteria::INNER_JOIN, $join->getJoinType(), 'joinWithXXX() adds an INNER JOIN');
    }

    public function testMagicJoinWithTypeAndRelation()
    {
        $c = new TestableModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->leftJoinWithAuthor();
        $expectedFields = array(
            BookEntityMap::FIELD_ID,
            BookEntityMap::FIELD_TITLE,
            BookEntityMap::FIELD_ISBN,
            BookEntityMap::FIELD_PRICE,
            BookEntityMap::FIELD_PUBLISHER_ID,
            BookEntityMap::FIELD_AUTHOR_ID,
            AuthorEntityMap::FIELD_ID,
            AuthorEntityMap::FIELD_FIRST_NAME,
            AuthorEntityMap::FIELD_LAST_NAME,
            AuthorEntityMap::FIELD_EMAIL,
            AuthorEntityMap::FIELD_AGE
        );
        $this->assertEquals($expectedFields, $c->getSelectFields(), 'leftJoinWithXXX() adds the join with the XXX relation');
        $joins = $c->getJoins();
        $join = $joins['author'];
        $this->assertEquals(Criteria::LEFT_JOIN, $join->getJoinType(), 'leftJoinWithXXX() adds an INNER JOIN');
    }

    public function testMagicFind()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->findByTitle('Don Juan');
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByXXX($value) is turned into findBy(XXX, $value)');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->findByTitleAndISBN('Don Juan', 1234);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan' AND book.isbn=1234");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByXXXAndYYY($value) is turned into findBy(array(XXX,YYY), $value)');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $book = $c->findOneByTitle('Don Juan');
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan' LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneByXXX($value) is turned into findOneBy(XXX, $value)');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $book = $c->findOneByTitleAndISBN('Don Juan', 1234);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan' AND book.isbn=1234 LIMIT 1");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneByXXX($value) is turned into findOneBy(XXX, $value)');
    }

    public function testMagicFilterBy()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->filterByTitle('Don Juan')->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.title='Don Juan'");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'filterByXXX($value) is turned into filterBy(XXX, $value)');
    }

    public function testMagicOrderBy()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->orderByTitle()->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book ORDER BY book.title ASC");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'orderByXXX() is turned into orderBy(XXX)');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->orderByTitle(Criteria::DESC)->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book ORDER BY book.title DESC");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'orderByXXX($direction) is turned into orderBy(XXX, $direction)');
    }

    public function testMagicGroupBy()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $books = $c->groupByTitle()->find();

        if ($this->isDb('pgsql')) {
            $expectedSQL = "SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book GROUP BY book.title,book.id,book.isbn,book.price,book.publisher_id,book.author_id";
        } else {
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book GROUP BY book.title");
        }

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'groupByXXX() is turned into groupBy(XXX)');
    }

    public function testUseQuery()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->thisIsMe = true;
        $c->where('b.Title = ?', 'foo');
        $c->setOffset(10);
        $c->leftJoin('b.author');

        $c2 = $c->useQuery('author');
        $this->assertTrue($c2 instanceof AuthorQuery, 'useQuery() returns a secondary Criteria');
        $this->assertEquals($c, $c2->getPrimaryCriteria(), 'useQuery() sets the primary Criteria os the secondary Criteria');
        $c2->where('author.FirstName = ?', 'john');
        $c2->limit(5);

        $c = $c2->endUse();
        $this->assertTrue($c->thisIsMe, 'endUse() returns the Primary Criteria');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $c->getEntityName(), 'endUse() returns the Primary Criteria');

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c->find();

        if (!$this->runningOnMySQL()) {
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE book.title = 'foo' AND author.first_name = 'john' LIMIT 5 OFFSET 10");

        } else {
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE book.title = 'foo' AND author.first_name = 'john' LIMIT 10, 5");
        }

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'useQuery() and endUse() allow to merge a secondary criteria');
    }

    public function testUseQueryAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->thisIsMe = true;
        $c->where('b.Title = ?', 'foo');
        $c->setOffset(10);
        $c->leftJoin('b.author a');

        $c2 = $c->useQuery('a');
        $this->assertTrue($c2 instanceof AuthorQuery, 'useQuery() returns a secondary Criteria');
        $this->assertEquals($c, $c2->getPrimaryCriteria(), 'useQuery() sets the primary Criteria os the secondary Criteria');
        $this->assertEquals(array('a' => 'Propel\Tests\Bookstore\Author'), $c2->getAliases(), 'useQuery() sets the secondary Criteria alias correctly');
        $c2->where('a.FirstName = ?', 'john');
        $c2->limit(5);

        $c = $c2->endUse();
        $this->assertTrue($c->thisIsMe, 'endUse() returns the Primary Criteria');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $c->getEntityName(), 'endUse() returns the Primary Criteria');

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c->find();

        if (!$this->runningOnMySQL()) {
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author a ON (book.author_id=a.id) WHERE book.title = 'foo' AND a.first_name = 'john' LIMIT 5 OFFSET 10");

        } else {
            $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author a ON (book.author_id=a.id) WHERE book.title = 'foo' AND a.first_name = 'john' LIMIT 10, 5");
        }

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'useQuery() and endUse() allow to merge a secondary criteria');
    }

    public function testUseQueryCustomClass()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c->thisIsMe = true;
        $c->where('b.Title = ?', 'foo');
        $c->setLimit(10);
        $c->leftJoin('b.author a');

        $c2 = $c->useQuery('a', 'Propel\Tests\Runtime\ActiveQuery\ModelCriteriaForUseQuery');
        $this->assertTrue($c2 instanceof ModelCriteriaForUseQuery, 'useQuery() returns a secondary Criteria with the custom class');
        $c2->withNoName();
        $c = $c2->endUse();

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c->find();
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author a ON (book.author_id=a.id) WHERE book.title = 'foo' AND a.first_name IS NOT NULL  AND a.last_name IS NOT NULL LIMIT 10");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'useQuery() and endUse() allow to merge a custom secondary criteria');
    }

    public function testUseQueryJoinWithFind()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Review');
        $c->joinWith('book');

        $c2 = $c->useQuery('book');

        $joins = $c->getJoins();
        $this->assertEquals($c->getPreviousJoin(), null, 'The default value for previousJoin remains null');
        $this->assertEquals($c2->getPreviousJoin(), $joins['book'], 'useQuery() sets the previousJoin');

        // join Book with Author, which is possible since previousJoin is set, which makes resolving of relations possible during hydration
        $c2->joinWith('author');

        $c = $c2->endUse();

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c->find();

        $expectedSQL = $this->getSql("SELECT review.id, review.reviewed_by, review.review_date, review.recommended, review.status, review.book_id, book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id, author.id, author.first_name, author.last_name, author.email, author.age FROM review INNER JOIN book ON (review.book_id=book.id) INNER JOIN author ON (book.author_id=author.id)");

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'useQuery() and joinWith() can be used together and form a correct query');
    }

    public function testUseQueryCustomRelationPhpName()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreContest');
        $c->leftJoin('Propel\Tests\Bookstore\BookstoreContest.work');
        $c2 = $c->useQuery('work');
        $this->assertTrue($c2 instanceof BookQuery, 'useQuery() returns a secondary Criteria');
        $this->assertEquals($c, $c2->getPrimaryCriteria(), 'useQuery() sets the primary Criteria os the secondary Criteria');
        //$this->assertEquals(array('a' => 'author'), $c2->getAliases(), 'useQuery() sets the secondary Criteria alias correctly');
        $c2->where('work.Title = ?', 'War And Peace');

        $c = $c2->endUse();
        $this->assertEquals('Propel\Tests\Bookstore\BookstoreContest', $c->getEntityName(), 'endUse() returns the Primary Criteria');

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c->find();

        $expectedSQL = $this->getSql("SELECT bookstore_contest.bookstore_id, bookstore_contest.contest_id, bookstore_contest.prize_book_id FROM bookstore_contest LEFT JOIN book ON (bookstore_contest.prize_book_id=book.id) WHERE book.title = 'War And Peace'");

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'useQuery() and endUse() allow to merge a secondary criteria');
    }

    public function testUseQueryCustomRelationPhpNameAndAlias()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreContest');
        $c->leftJoin('Propel\Tests\Bookstore\BookstoreContest.work w');
        $c2 = $c->useQuery('w');
        $this->assertTrue($c2 instanceof BookQuery, 'useQuery() returns a secondary Criteria');
        $this->assertEquals($c, $c2->getPrimaryCriteria(), 'useQuery() sets the primary Criteria os the secondary Criteria');
        $this->assertEquals(array('w' => 'Propel\Tests\Bookstore\Book'), $c2->getAliases(), 'useQuery() sets the secondary Criteria alias correctly');
        $c2->where('w.Title = ?', 'War And Peace');

        $c = $c2->endUse();
        $this->assertEquals('Propel\Tests\Bookstore\BookstoreContest', $c->getEntityName(), 'endUse() returns the Primary Criteria');

        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c->find();

        $expectedSQL = $this->getSql("SELECT bookstore_contest.bookstore_id, bookstore_contest.contest_id, bookstore_contest.prize_book_id FROM bookstore_contest LEFT JOIN book w ON (bookstore_contest.prize_book_id=w.id) WHERE w.title = 'War And Peace'");

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'useQuery() and endUse() allow to merge a secondary criteria');
    }

    public function testMergeWithJoins()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c1->leftJoin('b.author a');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c1->mergeWith($c2);
        $joins = $c1->getJoins();
        $this->assertEquals(1, count($joins), 'mergeWith() does not remove an existing join');
        $this->assertEquals('LEFT JOIN author a ON (Propel\Tests\Bookstore\Book.authorId=a.id)', $joins['a']->toString(), 'mergeWith() does not remove an existing join');
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2->leftJoin('b.author a');
        $c1->mergeWith($c2);
        $joins = $c1->getJoins();
        $this->assertEquals(1, count($joins), 'mergeWith() merge joins to an empty join');
        $this->assertEquals('LEFT JOIN author a ON (Propel\Tests\Bookstore\Book.authorId=a.id)', $joins['a']->toString(), 'mergeWith() merge joins to an empty join');

        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c1->leftJoin('b.author a');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2->innerJoin('b.publisher p');
        $c1->mergeWith($c2);
        $joins = $c1->getJoins();
        $this->assertEquals(2, count($joins), 'mergeWith() merge joins to an existing join');
        $this->assertEquals('LEFT JOIN author a ON (Propel\Tests\Bookstore\Book.authorId=a.id)', $joins['a']->toString(), 'mergeWith() merge joins to an empty join');
        $this->assertEquals('INNER JOIN publisher p ON (Propel\Tests\Bookstore\Book.publisherId=p.id)', $joins['p']->toString(), 'mergeWith() merge joins to an empty join');
    }

    public function testMergeWithWiths()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c1->leftJoinWith('b.author a');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c1->mergeWith($c2);
        $with = $c1->getWith();
        $this->assertEquals(1, count($with), 'mergeWith() does not remove an existing join');
        $this->assertEquals('entityName: Propel\Tests\Bookstore\Author, relationName: author, leftName: , rightName: a', $with['a']->__toString(), 'mergeWith() does not remove an existing join');

        $c1 = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book', 'b');
        $c1->leftJoinWith('b.author a');
        $c2 = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Author');
        $c1->mergeWith($c2);
        $with = $c1->getWith();
        $this->assertEquals(1, count($with), 'mergeWith() does not remove an existing join');
        $this->assertEquals('entityName: Propel\Tests\Bookstore\Author, relationName: author, leftName: , rightName: a', $with['a']->__toString(), 'mergeWith() does not remove an existing join');

        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2->leftJoinWith('b.author a');
        $c1->mergeWith($c2);
        $with = $c1->getWith();
        $this->assertEquals(1, count($with), 'mergeWith() merge joins to an empty join');
        $this->assertEquals('entityName: Propel\Tests\Bookstore\Author, relationName: author, leftName: , rightName: a', $with['a']->__toString(), 'mergeWith() merge joins to an empty join');

        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c1->leftJoinWith('b.author a');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2->innerJoinWith('b.publisher p');
        $c1->mergeWith($c2);
        $with = $c1->getWith();
        $this->assertEquals(2, count($with), 'mergeWith() merge joins to an existing join');
        $this->assertEquals('entityName: Propel\Tests\Bookstore\Author, relationName: author, leftName: , rightName: a', $with['a']->__toString(), 'mergeWith() merge joins to an empty join');
        $this->assertEquals('entityName: Propel\Tests\Bookstore\Publisher, relationName: publisher, leftName: , rightName: p', $with['p']->__toString(), 'mergeWith() merge joins to an empty join');

    }

    public function testGetAliasedColName()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $this->assertEquals(BookEntityMap::FIELD_TITLE, $c->getAliasedColName(BookEntityMap::FIELD_TITLE), 'getAliasedColName() returns the input when the table has no alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('foo');
        $this->assertEquals(BookEntityMap::FIELD_TITLE, $c->getAliasedColName(BookEntityMap::FIELD_TITLE), 'getAliasedColName() returns the input when the table has a query alias');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setEntityAlias('foo', true);
        $this->assertEquals('foo.title', $c->getAliasedColName(BookEntityMap::FIELD_TITLE), 'getAliasedColName() returns the field name with table alias when the table has a true alias');
    }

    public function testAddUsingAliasNoAlias()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $this->assertEquals($c2, $c1, 'addUsingalias() translates to add() when the table has no alias');
    }

    public function testAddUsingAliasQueryAlias()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book', 'b');
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $this->assertEquals($c2, $c1, 'addUsingalias() translates the colname using the table alias before calling add() when the table has a true alias');
    }

    public function testAddUsingAliasTrueAlias()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c1->setEntityAlias('b', true);
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'foo');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c2->setEntityAlias('b', true);
        $c2->add('b.title', 'foo');
        $this->assertEquals($c2, $c1, 'addUsingalias() translates to add() when the table has a true alias');
    }

    public function testAddUsingAliasTwice()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'bar');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c2->add(BookEntityMap::FIELD_TITLE, 'foo');
        $c2->addAnd(BookEntityMap::FIELD_TITLE, 'bar');
        $this->assertEquals($c2, $c1, 'addUsingalias() translates to addAnd() when the table already has a condition on the field');
    }

    public function testAddUsingAliasTrueAliasTwice()
    {
        $c1 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c1->setEntityAlias('b', true);
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'foo');
        $c1->addUsingAlias(BookEntityMap::FIELD_TITLE, 'bar');
        $c2 = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c2->setEntityAlias('b', true);
        $c2->add('b.title', 'foo');
        $c2->addAnd('b.title', 'bar');
        $this->assertEquals($c2, $c1, 'addUsingalias() translates to addAnd() when the table already has a condition on the field');
    }

    public function testCloneCopiesConditions()
    {
        $bookQuery1 = BookQuery::create()
            ->filterByPrice(1);
        $bookQuery2 = clone $bookQuery1;
        $bookQuery2
            ->filterByPrice(2);
        $params = array();
        $sql = $bookQuery1->createSelectSql($params);

        $expected = $this->getSql('SELECT  FROM book WHERE book.price=:p1');

        $this->assertEquals($expected, $sql, 'conditions applied on a cloned query don\'t get applied on the original query');
    }

    public function testCloneCopiesFormatter()
    {
        $formatter1 = new ArrayFormatter();
        $formatter1->test = false;
        $bookQuery1 = BookQuery::create();
        $bookQuery1->setFormatter($formatter1);
        $bookQuery2 = clone $bookQuery1;
        $formatter2 = $bookQuery2->getFormatter();
        $this->assertFalse($formatter2->test);
        $formatter2->test = true;
        $this->assertFalse($formatter1->test);
    }

    public function testCloneCopiesSelect()
    {
        $bookQuery1 = BookQuery::create();
        $bookQuery1->select(array('Id', 'Title'));
        $bookQuery2 = clone $bookQuery1;
        $bookQuery2->select(array('ISBN', 'Price'));
        $this->assertEquals(array('Id', 'Title'), $bookQuery1->getSelect());
    }

    public function testMagicFindByObject()
    {
        $con = Configuration::getCurrentConfiguration()->getConnectionManager('bookstore')->getWriteConnection();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $testAuthor = $c->findOne();
        $q = BookQuery::create()
            ->findByAuthor($testAuthor);

        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.author_id=" . $testAuthor->getId());

        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByXXX($value) is turned into findBy(XXX, $value)');

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $testAuthor = $c->findOne();
        $q = BookQuery::create()
            ->findByAuthorAndISBN($testAuthor, 1234);
        $expectedSQL = $this->getSql("SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.author_id=" . $testAuthor->getId() . " AND book.isbn=1234");
        $this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByXXXAndYYY($value) is turned into findBy(array(XXX, YYY), $value)');
    }
}
