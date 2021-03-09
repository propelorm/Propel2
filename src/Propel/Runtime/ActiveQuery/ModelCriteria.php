<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery;

use Exception;
use Propel\Common\Exception\SetColumnConverterException;
use Propel\Common\Util\SetColumnConverter;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\Criterion\BasicModelCriterion;
use Propel\Runtime\ActiveQuery\Criterion\BinaryModelCriterion;
use Propel\Runtime\ActiveQuery\Criterion\CustomCriterion;
use Propel\Runtime\ActiveQuery\Criterion\InModelCriterion;
use Propel\Runtime\ActiveQuery\Criterion\LikeModelCriterion;
use Propel\Runtime\ActiveQuery\Criterion\RawCriterion;
use Propel\Runtime\ActiveQuery\Criterion\RawModelCriterion;
use Propel\Runtime\ActiveQuery\Criterion\SeveralModelCriterion;
use Propel\Runtime\ActiveQuery\Exception\UnknownColumnException;
use Propel\Runtime\ActiveQuery\Exception\UnknownModelException;
use Propel\Runtime\ActiveQuery\Exception\UnknownRelationException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\ClassNotFoundException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\Util\PropelModelPager;

/**
 * This class extends the Criteria by adding runtime introspection abilities
 * in order to ease the building of queries.
 *
 * A ModelCriteria requires additional information to be initialized.
 * Using a model name and tablemaps, a ModelCriteria can do more powerful things than a simple Criteria
 *
 * magic methods:
 *
 * @method \Propel\Runtime\ActiveQuery\ModelCriteria leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method \Propel\Runtime\ActiveQuery\ModelCriteria rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method \Propel\Runtime\ActiveQuery\ModelCriteria innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @author François Zaninotto
 */
class ModelCriteria extends BaseModelCriteria
{
    public const FORMAT_STATEMENT = '\Propel\Runtime\Formatter\StatementFormatter';
    public const FORMAT_ARRAY = '\Propel\Runtime\Formatter\ArrayFormatter';
    public const FORMAT_OBJECT = '\Propel\Runtime\Formatter\ObjectFormatter';
    public const FORMAT_ON_DEMAND = '\Propel\Runtime\Formatter\OnDemandFormatter';

    /**
     * @var bool
     */
    protected $useAliasInSQL = false;

    /**
     * @var \Propel\Runtime\ActiveQuery\ModelCriteria|null
     */
    protected $primaryCriteria;

    /**
     * @var bool
     */
    protected $isWithOneToMany = false;

    /**
     * This is introduced to prevent useQuery->join from going wrong
     *
     * @var \Propel\Runtime\ActiveQuery\Join|null
     */
    protected $previousJoin;

    /**
     * Whether to clone the current object before termination methods
     *
     * @var bool
     */
    protected $isKeepQuery = true;

    // this is for the select method
    /**
     * @var string|array|null
     */
    protected $select;

    /**
     * temporary property used in replaceNames
     *
     * @var string|null
     */
    protected $currentAlias;

    /**
     * @var bool
     */
    protected $foundMatch = false;

    /**
     * Used to memorize whether we added self-select columns before.
     *
     * @var bool
     */
    protected $isSelfSelected = false;

    /**
     * Adds a condition on a column based on a pseudo SQL clause
     * but keeps it for later use with combine()
     * Until combine() is called, the condition is not added to the query
     * Uses introspection to translate the column phpName into a fully qualified name
     * <code>
     * $c->condition('cond1', 'b.Title = ?', 'foo');
     * </code>
     *
     * @see Criteria::add()
     *
     * @param string $conditionName A name to store the condition for a later combination with combine()
     * @param string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
     * @param mixed $value A value for the condition
     * @param mixed $bindingType A value for the condition
     *
     * @return $this The current object, for fluid interface
     */
    public function condition($conditionName, $clause, $value = null, $bindingType = null)
    {
        $this->addCond($conditionName, $this->getCriterionForClause($clause, $value, $bindingType), null, $bindingType);

        return $this;
    }

    /**
     * Adds a condition on a column based on a column phpName and a value
     * Uses introspection to translate the column phpName into a fully qualified name
     * Warning: recognizes only the phpNames of the main Model (not joined tables)
     * <code>
     * $c->filterBy('Title', 'foo');
     * </code>
     *
     * @see Criteria::add()
     *
     * @param string $column A string representing thecolumn phpName, e.g. 'AuthorId'
     * @param mixed $value A value for the condition
     * @param string $comparison What to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this The current object, for fluid interface
     */
    public function filterBy($column, $value, $comparison = Criteria::EQUAL)
    {
        $this->add($this->getRealColumnName($column), $value, $comparison);

        return $this;
    }

    /**
     * Adds a list of conditions on the columns of the current model
     * Uses introspection to translate the column phpName into a fully qualified name
     * Warning: recognizes only the phpNames of the main Model (not joined tables)
     * <code>
     * $c->filterByArray(array(
     *  'Title' => 'War And Peace',
     *  'Publisher' => $publisher
     * ));
     * </code>
     *
     * @see filterBy()
     *
     * @param mixed $conditions An array of conditions, using column phpNames as key
     *
     * @return $this The current object, for fluid interface
     */
    public function filterByArray($conditions)
    {
        foreach ($conditions as $column => $args) {
            call_user_func_array([$this, 'filterBy' . $column], is_array($args) ? $args : [$args]);
        }

        return $this;
    }

    /**
     * Adds a condition on a column based on a pseudo SQL clause
     * Uses introspection to translate the column phpName into a fully qualified name
     * <code>
     * // simple clause
     * $c->where('b.Title = ?', 'foo');
     * // named conditions
     * $c->condition('cond1', 'b.Title = ?', 'foo');
     * $c->condition('cond2', 'b.ISBN = ?', 12345);
     * $c->where(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
     * </code>
     *
     * @see Criteria::add()
     *
     * @param mixed $clause A string representing the pseudo SQL clause, e.g. 'Book.AuthorId = ?'
     *   Or an array of condition names
     * @param mixed $value A value for the condition
     * @param int|null $bindingType
     *
     * @return $this The current object, for fluid interface
     */
    public function where($clause, $value = null, $bindingType = null)
    {
        if (is_array($clause)) {
            // where(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
            $criterion = $this->getCriterionForConditions($clause, $value);
        } else {
            // where('Book.AuthorId = ?', 12)
            $criterion = $this->getCriterionForClause($clause, $value, $bindingType);
        }

        $this->addUsingOperator($criterion, null, null);

        return $this;
    }

    /**
     * Adds a having condition on a column based on a pseudo SQL clause
     * Uses introspection to translate the column phpName into a fully qualified name
     * <code>
     * // simple clause
     * $c->having('b.Title = ?', 'foo');
     * // named conditions
     * $c->condition('cond1', 'b.Title = ?', 'foo');
     * $c->condition('cond2', 'b.ISBN = ?', 12345);
     * $c->having(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
     * </code>
     *
     * @see Criteria::addHaving()
     *
     * @param mixed $clause A string representing the pseudo SQL clause, e.g. 'Book.AuthorId = ?'
     *                      Or an array of condition names
     * @param mixed $value A value for the condition
     * @param int|null $bindingType
     *
     * @return $this The current object, for fluid interface
     */
    public function having($clause, $value = null, $bindingType = null)
    {
        if (is_array($clause)) {
            // having(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
            $criterion = $this->getCriterionForConditions($clause, $value);
        } else {
            // having('Book.AuthorId = ?', 12)
            $criterion = $this->getCriterionForClause($clause, $value, $bindingType);
        }

        $this->addHaving($criterion);

        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query
     * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
     * Infers $column and $order from $columnName and some optional arguments
     * Examples:
     *   $c->orderBy('Book.CreatedAt')
     *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
     *   $c->orderBy('Book.CategoryId', 'desc')
     *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
     *
     * @param string $columnName The column to order by
     * @param string $order The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
     *
     * @throws \Propel\Runtime\Exception\UnexpectedValueException
     *
     * @return $this The current object, for fluid interface
     */
    public function orderBy($columnName, $order = Criteria::ASC)
    {
        [, $realColumnName] = $this->getColumnFromName($columnName, false);
        $order = strtoupper($order);
        switch ($order) {
            case Criteria::ASC:
                $this->addAscendingOrderByColumn($realColumnName);

                break;
            case Criteria::DESC:
                $this->addDescendingOrderByColumn($realColumnName);

                break;
            default:
                throw new UnexpectedValueException('ModelCriteria::orderBy() only accepts Criteria::ASC or Criteria::DESC as argument');
        }

        return $this;
    }

    /**
     * Adds a GROUP BY clause to the query
     * Usability layer on top of Criteria::addGroupByColumn()
     * Infers $column $columnName
     * Examples:
     *   $c->groupBy('Book.AuthorId')
     *    => $c->addGroupByColumn(BookTableMap::AUTHOR_ID)
     *
     *   $c->groupBy(array('Book.AuthorId', 'Book.AuthorName'))
     *    => $c->addGroupByColumn(BookTableMap::AUTHOR_ID)
     *    => $c->addGroupByColumn(BookTableMap::AUTHOR_NAME)
     *
     * @param mixed $columnName an array of columns name (e.g. array('Book.AuthorId', 'Book.AuthorName')) or a single column name (e.g. 'Book.AuthorId')
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return $this The current object, for fluid interface
     */
    public function groupBy($columnName)
    {
        if (empty($columnName)) {
            throw new PropelException('You must ask for at least one column');
        }

        if (!is_array($columnName)) {
            $columnName = [$columnName];
        }

        foreach ($columnName as $column) {
            [, $realColumnName] = $this->getColumnFromName($column, false);
            $this->addGroupByColumn($realColumnName);
        }

        return $this;
    }

    /**
     * Adds a GROUP BY clause for all columns of a model to the query
     * Examples:
     *   $c->groupBy('Book');
     *    => $c->addGroupByColumn(BookTableMap::ID);
     *    => $c->addGroupByColumn(BookTableMap::TITLE);
     *    => $c->addGroupByColumn(BookTableMap::AUTHOR_ID);
     *    => $c->addGroupByColumn(BookTableMap::PUBLISHER_ID);
     *
     * @param string $class The class name or alias
     *
     * @throws \Propel\Runtime\Exception\ClassNotFoundException
     *
     * @return $this The current object, for fluid interface
     */
    public function groupByClass($class)
    {
        if ($class == $this->getModelAliasOrName()) {
            // column of the Criteria's model
            $tableMap = $this->getTableMap();
        } elseif (isset($this->joins[$class])) {
            // column of a relations's model
            $tableMap = $this->joins[$class]->getTableMap();
        } else {
            throw new ClassNotFoundException(sprintf('Unknown model or alias: %s.', $class));
        }

        foreach ($tableMap->getColumns() as $column) {
            if (isset($this->aliases[$class])) {
                $this->addGroupByColumn($class . '.' . $column->getName());
            } else {
                $this->addGroupByColumn($column->getFullyQualifiedName());
            }
        }

        return $this;
    }

    /**
     * Adds a DISTINCT clause to the query
     * Alias for Criteria::setDistinct()
     *
     * @return $this The current object, for fluid interface
     */
    public function distinct()
    {
        $this->setDistinct();

        return $this;
    }

    /**
     * Adds a LIMIT clause (or its subselect equivalent) to the query
     * Alias for Criteria::setLimit()
     *
     * @param int $limit Maximum number of results to return by the query
     *
     * @return $this The current object, for fluid interface
     */
    public function limit($limit)
    {
        $this->setLimit($limit);

        return $this;
    }

    /**
     * Adds an OFFSET clause (or its subselect equivalent) to the query
     * Alias for of Criteria::setOffset()
     *
     * @param int $offset Offset of the first result to return
     *
     * @return $this The current object, for fluid interface
     */
    public function offset($offset)
    {
        $this->setOffset($offset);

        return $this;
    }

    /**
     * Makes the ModelCriteria return a string, array, or ArrayCollection
     * Examples:
     *   ArticleQuery::create()->select('Name')->find();
     *   => ArrayCollection Object ('Foo', 'Bar')
     *
     *   ArticleQuery::create()->select('Name')->findOne();
     *   => string 'Foo'
     *
     *   ArticleQuery::create()->select(array('Id', 'Name'))->find();
     *   => ArrayCollection Object (
     *        array('Id' => 1, 'Name' => 'Foo'),
     *        array('Id' => 2, 'Name' => 'Bar')
     *      )
     *
     *   ArticleQuery::create()->select(array('Id', 'Name'))->findOne();
     *   => array('Id' => 1, 'Name' => 'Foo')
     *
     * @param mixed $columnArray A list of column names (e.g. array('Title', 'Category.Name', 'c.Content')) or a single column name (e.g. 'Name')
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return $this The current object, for fluid interface
     */
    public function select($columnArray)
    {
        if (empty($columnArray)) {
            throw new PropelException('You must ask for at least one column');
        }

        if ($columnArray === '*') {
            $columnArray = [];
            foreach (call_user_func([$this->modelTableMapName, 'getFieldNames'], TableMap::TYPE_PHPNAME) as $column) {
                $columnArray[] = $this->modelName . '.' . $column;
            }
        }

        $this->select = $columnArray;

        return $this;
    }

    /**
     * Retrieves the columns defined by a previous call to select().
     *
     * @see select()
     *
     * @return array|string A list of column names (e.g. array('Title', 'Category.Name', 'c.Content')) or a single column name (e.g. 'Name')
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * This method returns the previousJoin for this ModelCriteria,
     * by default this is null, but after useQuery this is set the to the join of that use
     *
     * @return \Propel\Runtime\ActiveQuery\Join the previousJoin for this ModelCriteria
     */
    public function getPreviousJoin()
    {
        return $this->previousJoin;
    }

    /**
     * This method sets the previousJoin for this ModelCriteria,
     * by default this is null, but after useQuery this is set the to the join of that use
     *
     * @param \Propel\Runtime\ActiveQuery\Join $previousJoin The previousJoin for this ModelCriteria
     *
     * @return void
     */
    public function setPreviousJoin(Join $previousJoin)
    {
        $this->previousJoin = $previousJoin;
    }

    /**
     * Adds a JOIN clause to the query
     * Infers the ON clause from a relation name
     * Uses the Propel table maps, based on the schema, to guess the related columns
     * Beware that the default JOIN operator is INNER JOIN, while Criteria defaults to WHERE
     * Examples:
     * <code>
     *   $c->join('Book.Author');
     *    => $c->addJoin(BookTableMap::AUTHOR_ID, AuthorTableMap::ID, Criteria::INNER_JOIN);
     *   $c->join('Book.Author', Criteria::RIGHT_JOIN);
     *    => $c->addJoin(BookTableMap::AUTHOR_ID, AuthorTableMap::ID, Criteria::RIGHT_JOIN);
     *   $c->join('Book.Author a', Criteria::RIGHT_JOIN);
     *    => $c->addAlias('a', AuthorTableMap::TABLE_NAME);
     *    => $c->addJoin(BookTableMap::AUTHOR_ID, 'a.ID', Criteria::RIGHT_JOIN);
     * </code>
     *
     * @param string $relation Relation to use for the join
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     *
     * @return $this The current object, for fluid interface
     */
    public function join($relation, $joinType = Criteria::INNER_JOIN)
    {
        // relation looks like '$leftName.$relationName $relationAlias'
        [$fullName, $relationAlias] = self::getClassAndAlias($relation);
        if (strpos($fullName, '.') === false) {
            // simple relation name, refers to the current table
            $leftName = $this->getModelAliasOrName();
            $relationName = $fullName;
            $previousJoin = $this->getPreviousJoin();
            $tableMap = $this->getTableMap();
        } else {
            [$leftName, $relationName] = explode('.', $fullName);
            $shortLeftName = self::getShortName($leftName);
            // find the TableMap for the left table using the $leftName
            if ($leftName === $this->getModelAliasOrName() || $leftName === $this->getModelShortName()) {
                $previousJoin = $this->getPreviousJoin();
                $tableMap = $this->getTableMap();
            } elseif (isset($this->joins[$leftName])) {
                $previousJoin = $this->joins[$leftName];
                $tableMap = $previousJoin->getTableMap();
            } elseif (isset($this->joins[$shortLeftName])) {
                $previousJoin = $this->joins[$shortLeftName];
                $tableMap = $previousJoin->getTableMap();
            } else {
                throw new PropelException('Unknown table or alias ' . $leftName);
            }
        }
        $leftTableAlias = isset($this->aliases[$leftName]) ? $leftName : null;

        // find the RelationMap in the TableMap using the $relationName
        if (!$tableMap->hasRelation($relationName)) {
            throw new UnknownRelationException(sprintf('Unknown relation %s on the %s table.', $relationName, $leftName));
        }
        $relationMap = $tableMap->getRelation($relationName);

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        if ($previousJoin !== null) {
            $join->setPreviousJoin($previousJoin);
        }
        $join->setRelationMap($relationMap, $leftTableAlias, $relationAlias);

        // add the ModelJoin to the current object
        if ($relationAlias !== null) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, $relationName);
        }

        return $this;
    }

    /**
     * Add another condition to an already added join
     *
     * @example
     * <code>
     * $query->join('Book.Author');
     * $query->addJoinCondition('Author', 'Book.Title LIKE ?', 'foo%');
     * </code>
     *
     * @param string $name The relation name or alias on which the join was created
     * @param string $clause SQL clause, may contain column and table phpNames
     * @param mixed $value An optional value to bind to the clause
     * @param string|null $operator The operator to use to add the condition. Defaults to 'AND'
     * @param int|null $bindingType
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return $this The current object, for fluid interface
     */
    public function addJoinCondition($name, $clause, $value = null, $operator = null, $bindingType = null)
    {
        if (!isset($this->joins[$name])) {
            throw new PropelException(sprintf('Adding a condition to a nonexistent join, %s. Try calling join() first.', $name));
        }
        $join = $this->joins[$name];
        if (!$join->getJoinCondition() instanceof AbstractCriterion) {
            $join->buildJoinCondition($this);
        }
        $criterion = $this->getCriterionForClause($clause, $value, $bindingType);
        $method = $operator === Criteria::LOGICAL_OR ? 'addOr' : 'addAnd';
        $join->getJoinCondition()->$method($criterion);

        return $this;
    }

    /**
     * Replace the condition of an already added join
     *
     * @example
     * <code>
     * $query->join('Book.Author');
     * $query->condition('cond1', 'Book.AuthorId = Author.Id')
     * $query->condition('cond2', 'Book.Title LIKE ?', 'War%')
     * $query->combine(array('cond1', 'cond2'), 'and', 'cond3')
     * $query->setJoinCondition('Author', 'cond3');
     * </code>
     *
     * @param string $name The relation name or alias on which the join was created
     * @param mixed $condition A Criterion object, or a condition name
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return $this The current object, for fluid interface
     */
    public function setJoinCondition($name, $condition)
    {
        if (!isset($this->joins[$name])) {
            throw new PropelException(sprintf('Setting a condition to a nonexistent join, %s. Try calling join() first.', $name));
        }

        if ($condition instanceof AbstractCriterion) {
            $this->getJoin($name)->setJoinCondition($condition);
        } elseif (isset($this->namedCriterions[$condition])) {
            $this->getJoin($name)->setJoinCondition($this->namedCriterions[$condition]);
        } else {
            throw new PropelException(sprintf('Cannot add condition %s on join %s. setJoinCondition() expects either a Criterion, or a condition added by way of condition()', $condition, $name));
        }

        return $this;
    }

    /**
     * Add a join object to the Criteria
     *
     * @see Criteria::addJoinObject()
     *
     * @param \Propel\Runtime\ActiveQuery\Join $join A join object
     * @param string|null $name
     *
     * @return $this The current object, for fluid interface
     */
    public function addJoinObject(Join $join, $name = null)
    {
        if (!in_array($join, $this->joins)) { // compare equality, NOT identity
            if ($name === null) {
                $this->joins[] = $join;
            } else {
                $this->joins[$name] = $join;
            }
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query and hydrates the related objects
     * Shortcut for $c->join()->with()
     * <code>
     *   $c->joinWith('Book.Author');
     *    => $c->join('Book.Author');
     *    => $c->with('Author');
     *   $c->joinWith('Book.Author a', Criteria::RIGHT_JOIN);
     *    => $c->join('Book.Author a', Criteria::RIGHT_JOIN);
     *    => $c->with('a');
     * </code>
     *
     * @param string $relation Relation to use for the join
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this The current object, for fluid interface
     */
    public function joinWith($relation, $joinType = Criteria::INNER_JOIN)
    {
        $this->join($relation, $joinType);
        $this->with(self::getRelationName($relation));

        return $this;
    }

    /**
     * Adds a relation to hydrate together with the main object
     * The relation must be initialized via a join() prior to calling with()
     * Examples:
     * <code>
     *   $c->join('Book.Author');
     *   $c->with('Author');
     *
     *   $c->join('Book.Author a', Criteria::RIGHT_JOIN);
     *   $c->with('a');
     * </code>
     * WARNING: on a one-to-many relationship, the use of with() combined with limit()
     * will return a wrong number of results for the related objects
     *
     * @param string $relation Relation to use for the join
     *
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return $this The current object, for fluid interface
     */
    public function with($relation)
    {
        if (!isset($this->joins[$relation])) {
            throw new UnknownRelationException('Unknown relation name or alias ' . $relation);
        }

        /** @var \Propel\Runtime\ActiveQuery\ModelJoin $join */
        $join = $this->joins[$relation];
        if ($join->getRelationMap()->getType() === RelationMap::MANY_TO_MANY) {
            throw new PropelException(__METHOD__ . ' does not allow hydration for many-to-many relationships');
        }
        if ($join->getRelationMap()->getType() === RelationMap::ONE_TO_MANY) {
            // For performance reasons, the formatters will use a special routine in this case
            $this->isWithOneToMany = true;
        }

        // check that the columns of the main class are already added (but only if this isn't a useQuery)
        if (!$this->hasSelectClause() && !$this->getPrimaryCriteria()) {
            $this->addSelfSelectColumns();
        }
        // add the columns of the related class
        $this->addRelationSelectColumns($relation);

        // list the join for later hydration in the formatter
        $this->with[$relation] = new ModelWith($join);

        return $this;
    }

    /**
     * @return bool
     */
    public function isWithOneToMany()
    {
        return $this->isWithOneToMany;
    }

    /**
     * Adds a supplementary column to the select clause
     * These columns can later be retrieved from the hydrated objects using getVirtualColumn()
     *
     * @param string $clause The SQL clause with object model column names
     *                       e.g. 'UPPER(Author.FirstName)'
     * @param string|null $name Optional alias for the added column
     *                       If no alias is provided, the clause is used as a column alias
     *                       This alias is used for retrieving the column via BaseObject::getVirtualColumn($alias)
     *
     * @return $this The current object, for fluid interface
     */
    public function withColumn($clause, $name = null)
    {
        if ($name === null) {
            $name = str_replace(['.', '(', ')'], '', $clause);
        }

        $clause = trim($clause);
        $this->replaceNames($clause);
        // check that the columns of the main class are already added (if this is the primary ModelCriteria)
        if (!$this->hasSelectClause() && !$this->getPrimaryCriteria()) {
            $this->addSelfSelectColumns();
        }
        $this->addAsColumn($name, $clause);

        return $this;
    }

    /**
     * Initializes a secondary ModelCriteria object, to be later merged with the current object
     *
     * @see ModelCriteria::endUse()
     *
     * @param string $relationName Relation name or alias
     * @param string|null $secondaryCriteriaClass ClassName for the ModelCriteria to be used
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria The secondary criteria object
     */
    public function useQuery($relationName, $secondaryCriteriaClass = null)
    {
        if (!isset($this->joins[$relationName])) {
            throw new PropelException('Unknown class or alias ' . $relationName);
        }

        /** @var \Propel\Runtime\ActiveQuery\ModelJoin $modelJoin */
        $modelJoin = $this->joins[$relationName];
        $className = $modelJoin->getTableMap()->getClassName();
        /** @var self $secondaryCriteriaClass */
        if ($secondaryCriteriaClass === null) {
            $secondaryCriteria = PropelQuery::from($className);
        } else {
            $secondaryCriteria = new $secondaryCriteriaClass();
        }

        if ($className !== $relationName) {
            $secondaryCriteria->setModelAlias($relationName, $relationName == $this->joins[$relationName]->getRelationMap()->getName() ? false : true);
        }

        $secondaryCriteria->setPrimaryCriteria($this, $this->joins[$relationName]);

        return $secondaryCriteria;
    }

    /**
     * Finalizes a secondary criteria and merges it with its primary Criteria
     *
     * @see Criteria::mergeWith()
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria The primary criteria object
     */
    public function endUse()
    {
        if (isset($this->aliases[$this->modelAlias])) {
            $this->removeAlias($this->modelAlias);
        }

        $primaryCriteria = $this->getPrimaryCriteria();
        $primaryCriteria->mergeWith($this);

        return $primaryCriteria;
    }

    /**
     * Add the content of a Criteria to the current Criteria
     * In case of conflict, the current Criteria keeps its properties
     *
     * @see Criteria::mergeWith()
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria The criteria to read properties from
     * @param string|null $operator The logical operator used to combine conditions
     *                           Defaults to Criteria::LOGICAL_AND, also accepts Criteria::LOGICAL_OR
     *
     * @return $this The primary criteria object
     */
    public function mergeWith(Criteria $criteria, $operator = null)
    {
        if (
            $criteria instanceof ModelCriteria
            && !$criteria->getPrimaryCriteria()
            && $criteria->isSelfColumnsSelected()
            && $criteria->getWith()
        ) {
            if (!$this->isSelfColumnsSelected()) {
                $this->addSelfSelectColumns();
            }
            $criteria->removeSelfSelectColumns();
        }

        parent::mergeWith($criteria, $operator);

        // merge with
        if ($criteria instanceof ModelCriteria) {
            $this->with = array_merge($this->getWith(), $criteria->getWith());
        }

        return $this;
    }

    /**
     * Clear the conditions to allow the reuse of the query object.
     * The ModelCriteria's Model and alias 'all the properties set by construct) will remain.
     *
     * @return $this The primary criteria object
     */
    public function clear()
    {
        parent::clear();

        $this->with = [];
        $this->primaryCriteria = null;
        $this->formatter = null;
        $this->select = null;

        return $this;
    }

    /**
     * Sets the primary Criteria for this secondary Criteria
     *
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $criteria The primary criteria
     * @param \Propel\Runtime\ActiveQuery\Join $previousJoin The previousJoin for this ModelCriteria
     *
     * @return void
     */
    public function setPrimaryCriteria(ModelCriteria $criteria, Join $previousJoin)
    {
        $this->primaryCriteria = $criteria;
        $this->setPreviousJoin($previousJoin);
    }

    /**
     * Gets the primary criteria for this secondary Criteria
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria|null The primary criteria
     */
    public function getPrimaryCriteria()
    {
        return $this->primaryCriteria;
    }

    /**
     * Adds a Criteria as subQuery in the From Clause.
     *
     * @see Criteria::addSelectQuery()
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $subQueryCriteria Criteria to build the subquery from
     * @param string|null $alias alias for the subQuery
     * @param bool $addAliasAndSelectColumns Set to false if you want to manually add the aliased select columns
     *
     * @return $this The current object, for fluid interface
     */
    public function addSelectQuery(Criteria $subQueryCriteria, $alias = null, $addAliasAndSelectColumns = true)
    {
        if (!$subQueryCriteria->hasSelectClause()) {
            $subQueryCriteria->addSelfSelectColumns();
        }

        parent::addSelectQuery($subQueryCriteria, $alias);

        if ($addAliasAndSelectColumns) {
            // give this query-model same alias as subquery
            if ($alias === null) {
                end($this->selectQueries);
                $alias = key($this->selectQueries);
            }
            $this->setModelAlias($alias, true);
            // so we can add selfSelectColumns
            $this->addSelfSelectColumns(true);
        }

        return $this;
    }

    /**
     * Adds the select columns for the current table
     *
     * @param bool $force To enforce adding columns for changed alias, set it to true (f.e. with sub selects)
     *
     * @return $this The current object, for fluid interface
     */
    public function addSelfSelectColumns($force = false)
    {
        if ($this->isSelfSelected && !$force) {
            return $this;
        }

        /** @var string $tableMap */
        $tableMap = $this->modelTableMapName;
        $tableMap::addSelectColumns($this, $this->useAliasInSQL ? $this->modelAlias : null);
        $this->isSelfSelected = true;

        return $this;
    }

    /**
     * Removes the select columns for the current table
     *
     * @param bool $force To enforce removing columns for changed alias, set it to true (f.e. with sub selects)
     *
     * @return $this The current object, for fluid interface
     */
    public function removeSelfSelectColumns($force = false)
    {
        if (!$this->isSelfSelected && !$force) {
            return $this;
        }

        /** @var string $tableMap */
        $tableMap = $this->modelTableMapName;
        $tableMap::removeSelectColumns($this, $this->useAliasInSQL ? $this->modelAlias : null);
        $this->isSelfSelected = false;

        return $this;
    }

    /**
     * Returns whether select columns for the current table are included
     *
     * @return bool
     */
    public function isSelfColumnsSelected()
    {
        return $this->isSelfSelected;
    }

    /**
     * Adds the select columns for a relation
     *
     * @param string $relation The relation name or alias, as defined in join()
     *
     * @return $this The current object, for fluid interface
     */
    public function addRelationSelectColumns($relation)
    {
        /** @var \Propel\Runtime\ActiveQuery\ModelJoin $join */
        $join = $this->joins[$relation];
        $join->getTableMap()->addSelectColumns($this, $join->getRelationAlias());

        return $this;
    }

    /**
     * Returns the class and alias of a string representing a model or a relation
     * e.g. 'Book b' => array('Book', 'b')
     * e.g. 'Book' => array('Book', null)
     *
     * @param string $class The classname to explode
     *
     * @return array list($className, $aliasName)
     */
    public static function getClassAndAlias($class)
    {
        if (strpos($class, ' ') !== false) {
            [$class, $alias] = explode(' ', $class);
        } else {
            $alias = null;
        }
        if (strpos($class, '\\') === 0) {
            $class = substr($class, 1);
        }

        return [$class, $alias];
    }

    /**
     * Returns the name of a relation from a string.
     * The input looks like '$leftName.$relationName $relationAlias'
     *
     * @param string $relation Relation to use for the join
     *
     * @return string the relationName used in the join
     */
    public static function getRelationName($relation)
    {
        // get the relationName
        [$fullName, $relationAlias] = self::getClassAndAlias($relation);
        if ($relationAlias) {
            $relationName = $relationAlias;
        } elseif (strpos($fullName, '.') === false) {
            $relationName = $fullName;
        } else {
            [, $relationName] = explode('.', $fullName);
        }

        return $relationName;
    }

    /**
     * Triggers the automated cloning on termination.
     * By default, termination methods don't clone the current object,
     * even though they modify it. If the query must be reused after termination,
     * you must call this method prior to termination.
     *
     * @param bool $isKeepQuery
     *
     * @return $this The current object, for fluid interface
     */
    public function keepQuery($isKeepQuery = true)
    {
        $this->isKeepQuery = (bool)$isKeepQuery;

        return $this;
    }

    /**
     * Checks whether the automated cloning on termination is enabled.
     *
     * @return bool true if cloning must be done before termination
     */
    public function isKeepQuery()
    {
        return $this->isKeepQuery;
    }

    /**
     * Code to execute before every SELECT statement
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The connection object used by the query
     *
     * @return void
     */
    protected function basePreSelect(ConnectionInterface $con)
    {
        $this->preSelect($con);
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @return void
     */
    protected function preSelect(ConnectionInterface $con)
    {
    }

    /**
     * Issue a SELECT query based on the current ModelCriteria
     * and format the list of results with the current formatter
     * By default, returns an array of model objects
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return \Propel\Runtime\Collection\ObjectCollection|\Propel\Runtime\ActiveRecord\ActiveRecordInterface[]|mixed the list of results, formatted by the current formatter
     */
    public function find(?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria->doSelect($con);

        return $criteria
            ->getFormatter()
            ->init($criteria)->format($dataFetcher);
    }

    /**
     * Issue a SELECT ... LIMIT 1 query based on the current ModelCriteria
     * and format the result with the current formatter
     * By default, returns a model object.
     *
     * Does not work with ->with()s containing one-to-many relations.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function findOne(?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $criteria->limit(1);
        $dataFetcher = $criteria->doSelect($con);

        return $criteria
            ->getFormatter()
            ->init($criteria)
            ->formatOne($dataFetcher);
    }

    /**
     * Find object by primary key
     * Behaves differently if the model has simple or composite primary key
     * <code>
     * // simple primary key
     * $book = $c->requirePk(12, $con);
     * // composite primary key
     * $bookOpinion = $c->requirePk(array(34, 634), $con);
     * </code>
     *
     * Throws an exception when nothing was found.
     *
     * @param mixed $key Primary key to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\EntityNotFoundException|\Exception When nothing is found
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function requirePk($key, ?ConnectionInterface $con = null)
    {
        $result = $this->findPk($key, $con);

        if ($result === null) {
            throw $this->createEntityNotFoundException();
        }

        return $result;
    }

    /**
     * Issue a SELECT ... LIMIT 1 query based on the current ModelCriteria
     * and format the result with the current formatter
     * By default, returns a model object.
     *
     * Throws an exception when nothing was found.
     *
     * Does not work with ->with()s containing one-to-many relations.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\EntityNotFoundException|\Exception When nothing is found
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function requireOne(?ConnectionInterface $con = null)
    {
        $result = $this->findOne($con);

        if ($result === null) {
            throw $this->createEntityNotFoundException();
        }

        return $result;
    }

    /**
     * Apply a condition on a column and issues the SELECT ... LIMIT 1 query
     *
     * Throws an exception when nothing was found.
     *
     * @see filterBy()
     * @see findOne()
     *
     * @param mixed $column A string representing the column phpName, e.g. 'AuthorId'
     * @param mixed $value A value for the condition
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\EntityNotFoundException|\Exception When nothing is found
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function requireOneBy($column, $value, ?ConnectionInterface $con = null)
    {
        $result = $this->findOneBy($column, $value, $con);

        if ($result === null) {
            throw $this->createEntityNotFoundException();
        }

        return $result;
    }

    /**
     * Apply a list of conditions on columns and issues the SELECT ... LIMIT 1 query
     * <code>
     * $c->requireOneByArray([
     *  'Title' => 'War And Peace',
     *  'Publisher' => $publisher
     * ], $con);
     * </code>
     *
     * @see requireOne()
     *
     * @param mixed $conditions An array of conditions, using column phpNames as key
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Exception
     *
     * @return mixed the list of results, formatted by the current formatter
     */
    public function requireOneByArray($conditions, ?ConnectionInterface $con = null)
    {
        $result = $this->findOneByArray($conditions, $con);

        if ($result === null) {
            throw $this->createEntityNotFoundException();
        }

        return $result;
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Exception
     */
    private function createEntityNotFoundException()
    {
        if (!isset($this->entityNotFoundExceptionClass)) {
            throw new PropelException('Please define a entityNotFoundExceptionClass property with the name of your NotFoundException-class in ' . static::class);
        }

        return new $this->entityNotFoundExceptionClass("{$this->getModelShortName()} could not be found");
    }

    /**
     * Issue a SELECT ... LIMIT 1 query based on the current ModelCriteria
     * and format the result with the current formatter
     * By default, returns a model object
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function findOneOrCreate(?ConnectionInterface $con = null)
    {
        if ($this->joins) {
            throw new PropelException(__METHOD__ . ' cannot be used on a query with a join, because Propel cannot transform a SQL JOIN into a subquery. You should split the query in two queries to avoid joins.');
        }

        $ret = $this->findOne($con);
        if (!$ret) {
            $class = $this->getModelName();
            $obj = new $class();
            foreach ($this->keys() as $key) {
                $obj->setByName($key, $this->getValue($key), TableMap::TYPE_COLNAME);
            }
            $ret = $this->getFormatter()->formatRecord($obj);
        }

        return $ret;
    }

    /**
     * Find object by primary key
     * Behaves differently if the model has simple or composite primary key
     * <code>
     * // simple primary key
     * $book = $c->findPk(12, $con);
     * // composite primary key
     * $bookOpinion = $c->findPk(array(34, 634), $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function findPk($key, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        // As the query uses a PK condition, no limit(1) is necessary.
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $pkCols = array_values($this->getTableMap()->getPrimaryKeys());
        if (count($pkCols) === 1) {
            // simple primary key
            $pkCol = $pkCols[0];
            $criteria->add($pkCol->getFullyQualifiedName(), $key);
        } else {
            // composite primary key
            foreach ($pkCols as $pkCol) {
                $keyPart = array_shift($key);
                $criteria->add($pkCol->getFullyQualifiedName(), $keyPart);
            }
        }
        $dataFetcher = $criteria->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * Behaves differently if the model has simple or composite primary key
     * <code>
     * // simple primary key
     * $books = $c->findPks(array(12, 56, 832), $con);
     * // composite primary key
     * $bookOpinion = $c->findPks(array(array(34, 634), array(45, 518), array(34, 765)), $con);
     * </code>
     *
     * @param array $keys Primary keys to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        // As the query uses a PK condition, no limit(1) is necessary.
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $pkCols = $this->getTableMap()->getPrimaryKeys();
        if (count($pkCols) === 1) {
            // simple primary key
            $pkCol = array_shift($pkCols);
            $criteria->add($pkCol->getFullyQualifiedName(), $keys, Criteria::IN);
        } else {
            // composite primary key
            throw new PropelException('Multiple object retrieval is not implemented for composite primary keys');
        }
        $dataFetcher = $criteria->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Apply a condition on a column and issues the SELECT query
     *
     * @see filterBy()
     * @see find()
     *
     * @param string $column A string representing the column phpName, e.g. 'AuthorId'
     * @param mixed $value A value for the condition
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con An optional connection object
     *
     * @return mixed the list of results, formatted by the current formatter
     */
    public function findBy($column, $value, ?ConnectionInterface $con = null)
    {
        $method = 'filterBy' . $column;
        $this->$method($value);

        return $this->find($con);
    }

    /**
     * Apply a list of conditions on columns and issues the SELECT query
     * <code>
     * $c->findByArray(array(
     *  'Title' => 'War And Peace',
     *  'Publisher' => $publisher
     * ), $con);
     * </code>
     *
     * @see filterByArray()
     * @see find()
     *
     * @param mixed $conditions An array of conditions, using column phpNames as key
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return mixed the list of results, formatted by the current formatter
     */
    public function findByArray($conditions, ?ConnectionInterface $con = null)
    {
        $this->filterByArray($conditions);

        return $this->find($con);
    }

    /**
     * Apply a condition on a column and issues the SELECT ... LIMIT 1 query
     *
     * @see filterBy()
     * @see findOne()
     *
     * @param mixed $column A string representing thecolumn phpName, e.g. 'AuthorId'
     * @param mixed $value A value for the condition
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return mixed the result, formatted by the current formatter
     */
    public function findOneBy($column, $value, ?ConnectionInterface $con = null)
    {
        $method = 'filterBy' . $column;
        $this->$method($value);

        return $this->findOne($con);
    }

    /**
     * Apply a list of conditions on columns and issues the SELECT ... LIMIT 1 query
     * <code>
     * $c->findOneByArray(array(
     *  'Title' => 'War And Peace',
     *  'Publisher' => $publisher
     * ), $con);
     * </code>
     *
     * @see filterByArray()
     * @see findOne()
     *
     * @param mixed $conditions An array of conditions, using column phpNames as key
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return mixed the list of results, formatted by the current formatter
     */
    public function findOneByArray($conditions, ?ConnectionInterface $con = null)
    {
        $this->filterByArray($conditions);

        return $this->findOne($con);
    }

    /**
     * Issue a SELECT COUNT(*) query based on the current ModelCriteria
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return int the number of results
     */
    public function count(?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $criteria->setDbName($this->getDbName()); // Set the correct dbName
        $criteria->clearOrderByColumns(); // ORDER BY won't ever affect the count

        // We need to set the primary table name, since in the case that there are no WHERE columns
        // it will be impossible for the createSelectSql() method to determine which
        // tables go into the FROM clause.
        $criteria->setPrimaryTableName(constant($this->modelTableMapName . '::TABLE_NAME'));

        $dataFetcher = $criteria->doCount($con);
        $row = $dataFetcher->fetch();
        if ($row) {
            $count = (int)current($row);
        } else {
            $count = 0; // no rows returned; we infer that means 0 matches.
        }
        $dataFetcher->close();

        return $count;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function doCount(?ConnectionInterface $con = null)
    {
        $this->configureSelectColumns();

        // check that the columns of the main class are already added (if this is the primary ModelCriteria)
        if (!$this->hasSelectClause() && !$this->getPrimaryCriteria()) {
            $this->addSelfSelectColumns();
        }

        return parent::doCount($con);
    }

    /**
     * Issue an existence check on the current ModelCriteria
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return bool column existence
     */
    public function exists(?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $criteria->setDbName($this->getDbName()); // Set the correct dbName
        $criteria->clearOrderByColumns(); // ORDER BY will do nothing but slow down the query
        $criteria->clearSelectColumns(); // We are not retrieving data
        $criteria->addSelectColumn('1');
        $criteria->limit(1);

        // We need to set the primary table name, since in the case that there are no WHERE columns
        // it will be impossible for the createSelectSql() method to determine which
        // tables go into the FROM clause.
        $criteria->setPrimaryTableName(constant($this->modelTableMapName . '::TABLE_NAME'));

        $dataFetcher = $criteria->doSelect($con);
        $exists = (bool)$dataFetcher->fetchColumn(0);
        $dataFetcher->close();

        return $exists;
    }

    /**
     * Issue a SELECT query based on the current ModelCriteria
     * and uses a page and a maximum number of results per page
     * to compute an offset and a limit.
     *
     * @param int $page number of the page to start the pager on. Page 1 means no offset
     * @param int $maxPerPage maximum number of results per page. Determines the limit
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return \Propel\Runtime\Util\PropelModelPager a pager object, supporting iteration
     */
    public function paginate($page = 1, $maxPerPage = 10, ?ConnectionInterface $con = null)
    {
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $pager = new PropelModelPager($criteria, $maxPerPage);
        $pager->setPage($page);
        $pager->init($con);

        return $pager;
    }

    /**
     * Code to execute before every DELETE statement
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The connection object used by the query
     *
     * @return int
     */
    protected function basePreDelete(ConnectionInterface $con)
    {
        return $this->preDelete($con);
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @return int|null
     */
    protected function preDelete(ConnectionInterface $con)
    {
        return null;
    }

    /**
     * Code to execute after every DELETE statement
     *
     * @param int $affectedRows the number of deleted rows
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The connection object used by the query
     *
     * @return int
     */
    protected function basePostDelete($affectedRows, ConnectionInterface $con)
    {
        return $this->postDelete($affectedRows, $con);
    }

    /**
     * @param int $affectedRows
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @return int|null
     */
    protected function postDelete($affectedRows, ConnectionInterface $con)
    {
        return null;
    }

    /**
     * Issue a DELETE query based on the current ModelCriteria
     * An optional hook on basePreDelete() can prevent the actual deletion
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return int the number of deleted rows
     */
    public function delete(?ConnectionInterface $con = null)
    {
        if (count($this->getMap()) === 0) {
            throw new PropelException(__METHOD__ . ' expects a Criteria with at least one condition. Use deleteAll() to delete all the rows of a table');
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection($this->getDbName());
        }

        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $criteria->setDbName($this->getDbName());

        try {
            return $con->transaction(function () use ($con, $criteria) {
                $affectedRows = $criteria->basePreDelete($con);
                if (!$affectedRows) {
                    $affectedRows = $criteria->doDelete($con);
                }
                $criteria->basePostDelete($affectedRows, $con);

                return $affectedRows;
            });
        } catch (PropelException $e) {
            throw new PropelException(__METHOD__ . ' is unable to delete. ', 0, $e);
        }
    }

    /**
     * Issue a DELETE query based on the current ModelCriteria deleting all rows in the table
     * An optional hook on basePreDelete() can prevent the actual deletion
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return int the number of deleted rows
     */
    public function deleteAll(?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection($this->getDbName());
        }
        try {
            return $con->transaction(function () use ($con) {
                if (!$affectedRows = $this->basePreDelete($con)) {
                    $affectedRows = $this->doDeleteAll($con);
                }
                $this->basePostDelete($affectedRows, $con);

                return $affectedRows;
            });
        } catch (PropelException $e) {
            throw new PropelException(__METHOD__ . ' is unable to delete all. ', 0, $e);
        }
    }

    /**
     * Issue a DELETE query based on the current ModelCriteria deleting all rows in the table
     * This method is called by ModelCriteria::deleteAll() inside a transaction
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con a connection object
     *
     * @throws \Propel\Runtime\Exception\RuntimeException
     *
     * @return int the number of deleted rows
     */
    public function doDeleteAll(?ConnectionInterface $con = null)
    {
        $databaseName = $this->getDbName();

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection($databaseName);
        }

        // join are not supported with DELETE statement
        if (count($this->getJoins())) {
            throw new RuntimeException('Delete does not support join');
        }

        $this->setPrimaryTableName(constant($this->modelTableMapName . '::TABLE_NAME'));
        $tableName = $this->getPrimaryTableName();

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        try {
            $tableName = $this->quoteIdentifierTable($tableName);
            $sql = 'DELETE FROM ' . $tableName;
            $stmt = $con->prepare($sql);

            $stmt->execute();

            $affectedRows += $stmt->rowCount();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);

            throw new RuntimeException(sprintf('Unable to execute DELETE ALL statement [%s]', $sql), 0, $e);
        }

        return $affectedRows;
    }

    /**
     * Code to execute before every UPDATE statement
     *
     * @param array $values The associative array of columns and values for the update
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The connection object used by the query
     * @param bool $forceIndividualSaves If false (default), the resulting call is a Criteria::doUpdate(), otherwise it is a series of save() calls on all the found objects
     *
     * @return int|null
     */
    protected function basePreUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        return $this->preUpdate($values, $con, $forceIndividualSaves);
    }

    /**
     * @param array $values
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param bool $forceIndividualSaves
     *
     * @return int|null
     */
    protected function preUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        return null;
    }

    /**
     * Code to execute after every UPDATE statement
     *
     * @param int $affectedRows the number of updated rows
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The connection object used by the query
     *
     * @return int|null
     */
    protected function basePostUpdate($affectedRows, ConnectionInterface $con)
    {
        return $this->postUpdate($affectedRows, $con);
    }

    /**
     * @param int $affectedRows
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @return int|null
     */
    protected function postUpdate($affectedRows, ConnectionInterface $con)
    {
        return null;
    }

    /**
     * Issue an UPDATE query based the current ModelCriteria and a list of changes.
     * An optional hook on basePreUpdate() can prevent the actual update.
     * Beware that behaviors based on hooks in the object's save() method
     * will only be triggered if you force individual saves, i.e. if you pass true as second argument.
     *
     * @param mixed $values Associative array of keys and values to replace
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     * @param bool $forceIndividualSaves If false (default), the resulting call is a Criteria::doUpdate(), otherwise it is a series of save() calls on all the found objects
     *
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception|\Propel\Runtime\Exception\PropelException
     *
     * @return int Number of updated rows
     */
    public function update($values, ?ConnectionInterface $con = null, $forceIndividualSaves = false)
    {
        if (!is_array($values) && !($values instanceof Criteria)) {
            throw new PropelException(__METHOD__ . ' expects an array or Criteria as first argument');
        }

        if (count($this->getJoins())) {
            throw new PropelException(__METHOD__ . ' does not support multitable updates, please do not use join()');
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection($this->getDbName());
        }

        $criteria = $this->isKeepQuery() ? clone $this : $this;
        if ($this->modelTableMapName) {
            $criteria->setPrimaryTableName(constant($this->modelTableMapName . '::TABLE_NAME'));
        }

        return $con->transaction(function () use ($con, $values, $criteria, $forceIndividualSaves) {
            $affectedRows = $criteria->basePreUpdate($values, $con, $forceIndividualSaves);
            if (!$affectedRows) {
                $affectedRows = $criteria->doUpdate($values, $con, $forceIndividualSaves);
            }
            $criteria->basePostUpdate($affectedRows, $con);

            return $affectedRows;
        });
    }

    /**
     * Issue an UPDATE query based the current ModelCriteria and a list of changes.
     * This method is called by ModelCriteria::update() inside a transaction.
     *
     * @param array|\Propel\Runtime\ActiveQuery\Criteria $updateValues Associative array of keys and values to replace
     * @param \Propel\Runtime\Connection\ConnectionInterface $con a connection object
     * @param bool $forceIndividualSaves If false (default), the resulting call is a Criteria::doUpdate(), otherwise it is a series of save() calls on all the found objects
     *
     * @return int Number of updated rows
     */
    public function doUpdate($updateValues, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        if ($forceIndividualSaves) {
            // Update rows one by one
            $objects = $this->setFormatter(ModelCriteria::FORMAT_OBJECT)->find($con);
            foreach ($objects as $object) {
                foreach ($updateValues as $key => $value) {
                    $object->setByName($key, $value);
                }
            }
            $objects->save($con);
            $affectedRows = count($objects);
        } else {
            // update rows in a single query
            if ($updateValues instanceof Criteria) {
                $set = $updateValues;
            } else {
                $set = new Criteria($this->getDbName());
                foreach ($updateValues as $columnName => $value) {
                    $realColumnName = $this->getTableMap()->getColumnByPhpName($columnName)->getFullyQualifiedName();
                    $set->add($realColumnName, $value);
                }
            }

            $affectedRows = parent::doUpdate($set, $con);
            if ($this->getTableMap()->extractPrimaryKey($this)) {
                // this criteria updates only one object defined by a concrete primary key,
                // therefore there's no need to remove anything from the pool
            } else {
                call_user_func([$this->modelTableMapName, 'clearInstancePool']);
                call_user_func([$this->modelTableMapName, 'clearRelatedInstancePool']);
            }
        }

        return $affectedRows;
    }

    /**
     * Creates a Criterion object based on a list of existing condition names and a comparator
     *
     * @param array $conditions The list of condition names, e.g. array('cond1', 'cond2')
     * @param string|null $operator An operator, Criteria::LOGICAL_AND (default) or Criteria::LOGICAL_OR
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion A Criterion or ModelCriterion object
     */
    protected function getCriterionForConditions($conditions, $operator = null)
    {
        $operator = ($operator === null) ? Criteria::LOGICAL_AND : $operator;
        $this->combine($conditions, $operator, 'propel_temp_name');
        $criterion = $this->namedCriterions['propel_temp_name'];
        unset($this->namedCriterions['propel_temp_name']);

        return $criterion;
    }

    /**
     * Creates a Criterion object based on a SQL clause and a value
     * Uses introspection to translate the column phpName into a fully qualified name
     *
     * @param string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
     * @param mixed $value A value for the condition
     * @param int|null $bindingType
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion a Criterion object
     */
    protected function getCriterionForClause($clause, $value, $bindingType = null)
    {
        $origin = $clause = trim($clause);
        if ($this->replaceNames($clause)) {
            // at least one column name was found and replaced in the clause
            // this is enough to determine the type to bind the parameter to
            /** @var \Propel\Runtime\Map\ColumnMap $colMap */
            $colMap = $this->replacedColumns[0];
            $value = $this->convertValueForColumn($value, $colMap);
            $clauseLen = strlen($clause);
            if ($bindingType !== null) {
                return new RawModelCriterion($this, $clause, $colMap, $value, $this->currentAlias, $bindingType);
            }
            if (stripos($clause, 'IN ?') == $clauseLen - 4) {
                if ($colMap->isSetType()) {
                    if (stripos($clause, 'NOT IN ?') == $clauseLen - 8) {
                        $clause = str_ireplace('NOT IN ?', '& ? = 0', $clause);
                    } else {
                        $clause = str_ireplace('IN ?', '& ?', $clause);
                    }
                } else {
                    return new InModelCriterion($this, $clause, $colMap, $value, $this->currentAlias);
                }
            }
            if (stripos($clause, '& ?') !== false) {
                return new BinaryModelCriterion($this, $clause, $colMap, $value, $this->currentAlias);
            }
            if (stripos($clause, 'LIKE ?') == $clauseLen - 6) {
                return new LikeModelCriterion($this, $clause, $colMap, $value, $this->currentAlias);
            }
            if (substr_count($clause, '?') > 1) {
                return new SeveralModelCriterion($this, $clause, $colMap, $value, $this->currentAlias);
            }

            return new BasicModelCriterion($this, $clause, $colMap, $value, $this->currentAlias);
        }
        // no column match in clause, must be an expression like '1=1'
        if (strpos($clause, '?') !== false) {
            if ($bindingType === null) {
                throw new PropelException(sprintf('Cannot determine the column to bind to the parameter in clause "%s".', $origin));
            }

            return new RawCriterion($this, $clause, $value, $bindingType);
        }

        return new CustomCriterion($this, $clause);
    }

    /**
     * Converts value for some column types
     *
     * @param mixed $value The value to convert
     * @param \Propel\Runtime\Map\ColumnMap $colMap The ColumnMap object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return mixed The converted value
     */
    protected function convertValueForColumn($value, ColumnMap $colMap)
    {
        if ($colMap->getType() === 'OBJECT' && is_object($value)) {
            if (is_array($value)) {
                $value = array_map('serialize', $value);
            } else {
                $value = serialize($value);
            }
        } elseif ($colMap->getType() === 'ARRAY' && is_array($value)) {
            $value = '| ' . implode(' | ', $value) . ' |';
        } elseif ($colMap->getType() === PropelTypes::ENUM && $value !== null) {
            if (is_array($value)) {
                $value = array_map([$colMap, 'getValueSetKey'], $value);
            } else {
                $value = $colMap->getValueSetKey($value);
            }
        } elseif ($colMap->isSetType() && $value !== null) {
            try {
                $value = SetColumnConverter::convertToInt($value, $colMap->getValueSet());
            } catch (SetColumnConverterException $e) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this set column', $e->getValue()), $e->getCode(), $e);
            }
        }

        return $value;
    }

    /**
     * Callback function to replace column names by their real name in a clause
     * e.g. 'Book.Title IN ?'
     *    => 'book.title IN ?'
     *
     * @param array $matches Matches found by preg_replace_callback
     *
     * @return string the column name replacement
     */
    protected function doReplaceNameInExpression($matches)
    {
        $key = $matches[0];
        [$column, $realFullColumnName] = $this->getColumnFromName($key);

        if ($column instanceof ColumnMap) {
            $this->replacedColumns[] = $column;
            $this->foundMatch = true;

            if (strpos($key, '.') !== false) {
                [$tableName, $columnName] = explode('.', $key);
                $realColumnName = substr($realFullColumnName, strrpos($realFullColumnName, '.') + 1);
                if (isset($this->aliases[$tableName])) {
                    //don't replace a alias with their real table name
                    return $this->quoteIdentifier($tableName . '.' . $realColumnName);
                }
            }

            return $this->quoteIdentifier($realFullColumnName);
        }

        return $this->quoteIdentifier($key);
    }

    /**
     * Finds a column and a SQL translation for a pseudo SQL column name
     * Respects table aliases previously registered in a join() or addAlias()
     * Examples:
     * <code>
     * $c->getColumnFromName('Book.Title');
     *   => array($bookTitleColumnMap, 'book.title')
     * $c->join('Book.Author a')
     *   ->getColumnFromName('a.FirstName');
     *   => array($authorFirstNameColumnMap, 'a.first_name')
     * </code>
     *
     * @param string $phpName String representing the column name in a pseudo SQL clause, e.g. 'Book.Title'
     * @param bool $failSilently
     *
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownColumnException
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownModelException
     *
     * @return array List($columnMap, $realColumnName)
     */
    protected function getColumnFromName($phpName, $failSilently = true)
    {
        if (strpos($phpName, '.') === false) {
            $prefix = $this->getModelAliasOrName();
        } else {
            // $prefix could be either class name or table name
            [$prefix, $phpName] = explode('.', $phpName);
        }

        $shortClass = self::getShortName($prefix);

        if ($prefix === $this->getModelAliasOrName()) {
            // column of the Criteria's model
            $tableMap = $this->getTableMap();
        } elseif ($prefix === $this->getModelShortName()) {
            // column of the Criteria's model
            $tableMap = $this->getTableMap();
        } elseif ($this->getTableMap() && $prefix == $this->getTableMap()->getName()) {
            // column name from Criteria's tableMap
            $tableMap = $this->getTableMap();
        } elseif (isset($this->joins[$prefix])) {
            // column of a relations's model
            $tableMap = $this->joins[$prefix]->getTableMap();
        } elseif (isset($this->joins[$shortClass])) {
            // column of a relations's model
            $tableMap = $this->joins[$shortClass]->getTableMap();
        } elseif ($this->hasSelectQuery($prefix)) {
            return $this->getColumnFromSubQuery($prefix, $phpName, $failSilently);
        } elseif ($modelJoin = $this->getModelJoinByTableName($prefix)) {
            $tableMap = $modelJoin->getTableMap();
        } elseif ($failSilently) {
            return [null, null];
        } else {
            throw new UnknownModelException(sprintf('Unknown model, alias or table "%s"', $prefix));
        }

        if ($tableMap->hasColumnByPhpName($phpName)) {
            $column = $tableMap->getColumnByPhpName($phpName);
            if (isset($this->aliases[$prefix])) {
                $this->currentAlias = $prefix;
                $realColumnName = $prefix . '.' . $column->getName();
            } else {
                $realColumnName = $column->getFullyQualifiedName();
            }

            return [$column, $realColumnName];
        } elseif ($tableMap->hasColumn($phpName)) {
            $column = $tableMap->getColumn($phpName);
            $realColumnName = $column->getFullyQualifiedName();

            return [$column, $realColumnName];
        } elseif (isset($this->asColumns[$phpName])) {
            // aliased column
            return [null, $phpName];
        } elseif ($failSilently) {
            return [null, null];
        } else {
            throw new UnknownColumnException(sprintf('Unknown column "%s" on model, alias or table "%s"', $phpName, $prefix));
        }
    }

    /**
     * @param string $tableName
     *
     * @return \Propel\Runtime\ActiveQuery\ModelJoin|null
     */
    public function getModelJoinByTableName($tableName)
    {
        foreach ($this->joins as $join) {
            if ($join instanceof ModelJoin && $join->getTableMap()->getName() == $tableName) {
                return $join;
            }
        }

        return null;
    }

    /**
     * Builds, binds and executes a SELECT query based on the current object.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con A connection object
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface A dataFetcher using the connection, ready to be fetched
     */
    public function doSelect(?ConnectionInterface $con = null)
    {
        $this->addSelfSelectColumns();

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $this->configureSelectColumns();

        return parent::doSelect($con);
    }

    /**
     * @return void
     */
    public function configureSelectColumns()
    {
        if ($this->select === null) {
            // leave early
            return;
        }

        // select() needs the PropelSimpleArrayFormatter if no formatter given
        if ($this->formatter === null) {
            $this->setFormatter('\Propel\Runtime\Formatter\SimpleArrayFormatter');
        }

        // clear only the selectColumns, clearSelectColumns() clears asColumns too
        $this->selectColumns = [];

        // We need to set the primary table name, since in the case that there are no WHERE columns
        // it will be impossible for the createSelectSql() method to determine which
        // tables go into the FROM clause.
        if (!$this->selectQueries) {
            $this->setPrimaryTableName(constant($this->modelTableMapName . '::TABLE_NAME'));
        }

        // Add requested columns which are not withColumns
        $columnNames = is_array($this->select) ? $this->select : [$this->select];
        foreach ($columnNames as $columnName) {
            // check if the column was added by a withColumn, if not add it
            if (!array_key_exists($columnName, $this->getAsColumns())) {
                $column = $this->getColumnFromName($columnName);
                // always put quotes around the columnName to be safe, we strip them in the formatter
                $this->addAsColumn('"' . $columnName . '"', $column[1]);
            }
        }
    }

    /**
     * Special case for subquery columns
     *
     * @param string $class
     * @param string $phpName
     * @param bool $failSilently
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return array List($columnMap, $realColumnName)
     */
    protected function getColumnFromSubQuery($class, $phpName, $failSilently = true)
    {
        $subQueryCriteria = $this->getSelectQuery($class);
        $tableMap = $subQueryCriteria->getTableMap();
        if ($tableMap->hasColumnByPhpName($phpName)) {
            $column = $tableMap->getColumnByPhpName($phpName);
            $realColumnName = $class . '.' . $column->getName();

            return [$column, $realColumnName];
        }
        if (isset($subQueryCriteria->asColumns[$phpName])) {
            // aliased column
            return [null, $class . '.' . $phpName];
        }
        if ($failSilently) {
            return [null, null];
        }

        throw new PropelException(sprintf('Unknown column "%s" in the subQuery with alias "%s".', $phpName, $class));
    }

    /**
     * Return a fully qualified column name corresponding to a simple column phpName
     * Uses model alias if it exists
     * Warning: restricted to the columns of the main model
     * e.g. => 'Title' => 'book.TITLE'
     *
     * @param string $columnName the Column phpName, without the table name
     *
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownColumnException
     *
     * @return string the fully qualified column name
     */
    protected function getRealColumnName($columnName)
    {
        if (!$this->getTableMap()->hasColumnByPhpName($columnName)) {
            throw new UnknownColumnException('Unknown column ' . $columnName . ' in model ' . $this->modelName);
        }

        if ($this->useAliasInSQL) {
            return $this->modelAlias . '.' . $this->getTableMap()->getColumnByPhpName($columnName)->getName();
        }

        return $this
            ->getTableMap()
            ->getColumnByPhpName($columnName)
            ->getFullyQualifiedName();
    }

    /**
     * Changes the table part of a a fully qualified column name if a true model alias exists
     * e.g. => 'book.TITLE' => 'b.TITLE'
     * This is for use as first argument of Criteria::add()
     *
     * @param string $colName the fully qualified column name, e.g 'book.TITLE' or BookTableMap::TITLE
     *
     * @return string the fully qualified column name, using table alias if applicable
     */
    public function getAliasedColName($colName)
    {
        if ($this->useAliasInSQL) {
            return $this->modelAlias . substr($colName, strrpos($colName, '.'));
        }

        return $colName;
    }

    /**
     * Return the short ClassName for class with namespace
     *
     * @param string $fullyQualifiedClassName The fully qualified class name
     *
     * @return string The short class name
     */
    public static function getShortName($fullyQualifiedClassName)
    {
        $namespaceParts = explode('\\', $fullyQualifiedClassName);

        return array_pop($namespaceParts);
    }

    /**
     * Overrides Criteria::add() to force the use of a true table alias if it exists
     *
     * @see Criteria::add()
     *
     * @param string $column The colName of column to run the condition on (e.g. BookTableMap::ID)
     * @param mixed $value
     * @param string|null $operator A String, like Criteria::EQUAL.
     *
     * @return $this A modified Criteria object.
     */
    public function addUsingAlias($column, $value = null, $operator = null)
    {
        $this->addUsingOperator($this->getAliasedColName($column), $value, $operator);

        return $this;
    }

    /**
     * Get all the parameters to bind to this criteria
     * Does part of the job of createSelectSql() for the cache
     *
     * @return array list of parameters, each parameter being an array like
     *               array('table' => $realtable, 'column' => $column, 'value' => $value)
     */
    public function getParams()
    {
        $params = [];
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());

        foreach ($this->getMap() as $criterion) {
            $table = null;
            foreach ($criterion->getAttachedCriterion() as $attachedCriterion) {
                $tableName = $attachedCriterion->getTable();

                $table = $this->getTableForAlias($tableName);
                if ($table === null) {
                    $table = $tableName;
                }

                if (
                    ($this->isIgnoreCase() || method_exists($attachedCriterion, 'setIgnoreCase'))
                    && $dbMap->getTable($table)->getColumn($attachedCriterion->getColumn())->isText()
                ) {
                    $attachedCriterion->setIgnoreCase(true);
                }
            }

            $sb = '';
            $criterion->appendPsTo($sb, $params);
        }

        $having = $this->getHaving();
        if ($having !== null) {
            $sb = '';
            $having->appendPsTo($sb, $params);
        }

        return $params;
    }

    /**
     * Handle the magic
     * Supports findByXXX(), findOneByXXX(), requireOneByXXX(), filterByXXX(), orderByXXX(), and groupByXXX() methods,
     * where XXX is a column phpName.
     * Supports XXXJoin(), where XXX is a join direction (in 'left', 'right', 'inner')
     *
     * @param string $name
     * @param array $arguments
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // Maybe it's a magic call to one of the methods supporting it, e.g. 'findByTitle'
        static $methods = ['findBy', 'findOneBy', 'requireOneBy', 'filterBy', 'orderBy', 'groupBy'];
        foreach ($methods as $method) {
            if (strpos($name, $method) === 0) {
                $columns = substr($name, strlen($method));
                if (in_array($method, ['findBy', 'findOneBy', 'requireOneBy'], true) && strpos($columns, 'And') !== false) {
                    $method = $method . 'Array';
                    $columns = explode('And', $columns);
                    $conditions = [];
                    foreach ($columns as $column) {
                        $conditions[$column] = array_shift($arguments);
                    }
                    array_unshift($arguments, $conditions);
                } else {
                    array_unshift($arguments, $columns);
                }

                return call_user_func_array([$this, $method], $arguments);
            }
        }

        // Maybe it's a magic call to a qualified joinWith method, e.g. 'leftJoinWith' or 'joinWithAuthor'
        if (($pos = stripos($name, 'joinWith')) !== false) {
            $joinType = null;

            $type = substr($name, 0, $pos);
            if (in_array($type, ['left', 'right', 'inner'])) {
                $joinType = strtoupper($type) . ' JOIN';
            }

            $relation = substr($name, $pos + 8);
            if (!$relation) {
                $relation = $arguments[0];
                $joinType = isset($arguments[1]) ? $arguments[1] : $joinType;
            } else {
                $joinType = isset($arguments[0]) ? $arguments[0] : $joinType;
            }

            return $this->joinWith($relation, $joinType);
        }

        // Maybe it's a magic call to a qualified join method, e.g. 'leftJoin'
        if (($pos = strpos($name, 'Join')) > 0) {
            $type = substr($name, 0, $pos);
            if (in_array($type, ['left', 'right', 'inner'])) {
                $joinType = strtoupper($type) . ' JOIN';
                // Test if first argument is supplied, else don't provide an alias to joinXXX (default value)
                if (!isset($arguments[0])) {
                    $arguments[0] = null;
                }
                array_push($arguments, $joinType);
                $method = lcfirst(substr($name, $pos));

                return call_user_func_array([$this, $method], $arguments);
            }
        }

        throw new PropelException(sprintf('Undefined method %s::%s()', self::class, $name));
    }

    /**
     * Ensures deep cloning of attached objects
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        foreach ($this->with as $key => $join) {
            $this->with[$key] = clone $join;
        }

        if ($this->formatter !== null) {
            $this->formatter = clone $this->formatter;
        }
    }

    /**
     * Override method to prevent an addition of self columns.
     *
     * @param string $name
     *
     * @return $this
     */
    public function addSelectColumn($name)
    {
        $this->isSelfSelected = true;

        return parent::addSelectColumn($name);
    }
}
