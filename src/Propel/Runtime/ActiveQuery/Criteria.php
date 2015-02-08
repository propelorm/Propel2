<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Adapter\Pdo\PdoAdapter;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Util\PropelConditionalProxy;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\Criterion\BasicCriterion;
use Propel\Runtime\ActiveQuery\Criterion\InCriterion;
use Propel\Runtime\ActiveQuery\Criterion\CustomCriterion;
use Propel\Runtime\ActiveQuery\Criterion\LikeCriterion;
use Propel\Runtime\ActiveQuery\Criterion\RawCriterion;

/**
 * This is a utility class for holding criteria information for a query.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Kaspars Jaudzems <kaspars.jaudzems@inbox.lv> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Eric Dobbs <eric@dobbse.net> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Sam Joseph <sam@neurogrid.com> (Torque)
 */
class Criteria
{

    /** Comparison type. */
    const EQUAL = '=';

    /** Comparison type. */
    const NOT_EQUAL = '<>';

    /** Comparison type. */
    const ALT_NOT_EQUAL = '!=';

    /** Comparison type. */
    const GREATER_THAN = '>';

    /** Comparison type. */
    const LESS_THAN = '<';

    /** Comparison type. */
    const GREATER_EQUAL = '>=';

    /** Comparison type. */
    const LESS_EQUAL = '<=';

    /** Comparison type. */
    const LIKE = ' LIKE ';

    /** Comparison type. */
    const NOT_LIKE = ' NOT LIKE ';

    /** Comparison for array column types */
    const CONTAINS_ALL = 'CONTAINS_ALL';

    /** Comparison for array column types */
    const CONTAINS_SOME = 'CONTAINS_SOME';

    /** Comparison for array column types */
    const CONTAINS_NONE = 'CONTAINS_NONE';

    /** PostgreSQL comparison type */
    const ILIKE = ' ILIKE ';

    /** PostgreSQL comparison type */
    const NOT_ILIKE = ' NOT ILIKE ';

    /** Comparison type. */
    const CUSTOM = 'CUSTOM';

    /** Comparison type */
    const RAW = 'RAW';

    /** Comparison type for update */
    const CUSTOM_EQUAL = 'CUSTOM_EQUAL';

    /** Comparison type. */
    const DISTINCT = 'DISTINCT';

    /** Comparison type. */
    const IN = ' IN ';

    /** Comparison type. */
    const NOT_IN = ' NOT IN ';

    /** Comparison type. */
    const ALL = 'ALL';

    /** Comparison type. */
    const JOIN = 'JOIN';

    /** Binary math operator: AND */
    const BINARY_AND = '&';

    /** Binary math operator: OR */
    const BINARY_OR = '|';

    /** 'Order by' qualifier - ascending */
    const ASC = 'ASC';

    /** 'Order by' qualifier - descending */
    const DESC = 'DESC';

    /** 'IS NULL' null comparison */
    const ISNULL = ' IS NULL ';

    /** 'IS NOT NULL' null comparison */
    const ISNOTNULL = ' IS NOT NULL ';

    /** 'CURRENT_DATE' ANSI SQL function */
    const CURRENT_DATE = 'CURRENT_DATE';

    /** 'CURRENT_TIME' ANSI SQL function */
    const CURRENT_TIME = 'CURRENT_TIME';

    /** 'CURRENT_TIMESTAMP' ANSI SQL function */
    const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /** 'LEFT JOIN' SQL statement */
    const LEFT_JOIN = 'LEFT JOIN';

    /** 'RIGHT JOIN' SQL statement */
    const RIGHT_JOIN = 'RIGHT JOIN';

    /** 'INNER JOIN' SQL statement */
    const INNER_JOIN = 'INNER JOIN';

    /** logical OR operator */
    const LOGICAL_OR = 'OR';

    /** logical AND operator */
    const LOGICAL_AND = 'AND';

    protected $ignoreCase = false;

    protected $singleRecord = false;

    /**
     * Storage of select data. Collection of column names.
     * @var array
     */
    protected $selectColumns = array();

    /**
     * Storage of aliased select data. Collection of column names.
     * @var string[]
     */
    protected $asColumns = array();

    /**
     * Storage of select modifiers data. Collection of modifier names.
     * @var string[]
     */
    protected $selectModifiers = array();

    /**
     * Storage of conditions data. Collection of Criterion objects.
     * @var AbstractCriterion[]
     */
    protected $map = array();

    /**
     * Storage of ordering data. Collection of column names.
     * @var array
     */
    protected $orderByColumns = array();

    /**
     * Storage of grouping data. Collection of column names.
     * @var array
     */
    protected $groupByColumns = array();

    /**
     * Storage of having data.
     * @var AbstractCriterion
     */
    protected $having = null;

    /**
     * Storage of join data. collection of Join objects.
     * @var Join[]
     */
    protected $joins = array();

    protected $selectQueries = array();

    /**
     * The name of the database.
     * @var string
     */
    protected $dbName;

    /**
     * The primary table for this Criteria.
     * Useful in cases where there are no select or where
     * columns.
     * @var string
     */
    protected $primaryTableName;

    /** The name of the database as given in the constructor. */
    protected $originalDbName;

    /**
     * To limit the number of rows to return.  <code>-1</code> means return all
     * rows.
     */
    protected $limit = -1;

    /** To start the results at a row other than the first one. */
    protected $offset = 0;

    /**
     * Comment to add to the SQL query
     * @var string
     */
    protected $queryComment;

    protected $aliases = array();

    protected $useTransaction = false;

    /**
     * Storage for Criterions expected to be combined
     * @var array
     */
    protected $namedCriterions = array();

    /**
     * Default operator for combination of criterions
     * @see addUsingOperator
     * @var string Criteria::LOGICAL_AND or Criteria::LOGICAL_OR
     */
    protected $defaultCombineOperator = Criteria::LOGICAL_AND;

    /**
     * @var PropelConditionalProxy
     */
    protected $conditionalProxy = null;

    /**
     * Whether identifier should be quoted.
     *
     * @var boolean
     */
    protected $identifierQuoting = null;

    /**
     * @var array
     */
    public $replacedColumns = [];

    /**
     * Creates a new instance with the default capacity which corresponds to
     * the specified database.
     *
     * @param String $dbName The database name.
     */
    public function __construct($dbName = null)
    {
        $this->setDbName($dbName);
        $this->originalDbName = $dbName;
    }

    /**
     * Get the criteria map, i.e. the array of Criterions
     * @return AbstractCriterion[]
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * Brings this criteria back to its initial state, so that it
     * can be reused as if it was new. Except if the criteria has grown in
     * capacity, it is left at the current capacity.
     * @return void
     */
    public function clear()
    {
        $this->map = array();
        $this->namedCriterions = array();
        $this->ignoreCase = false;
        $this->singleRecord = false;
        $this->selectModifiers = array();
        $this->selectColumns = array();
        $this->orderByColumns = array();
        $this->groupByColumns = array();
        $this->having = null;
        $this->asColumns = array();
        $this->joins = array();
        $this->selectQueries = array();
        $this->dbName = $this->originalDbName;
        $this->offset = 0;
        $this->limit = -1;
        $this->aliases = array();
        $this->useTransaction = false;
        $this->ifLvlCount = false;
        $this->wasTrue = false;
    }

    /**
     * Add an AS clause to the select columns. Usage:
     *
     * <code>
     * Criteria myCrit = new Criteria();
     * myCrit->addAsColumn('alias', 'ALIAS('.MyTableMap::ID.')');
     * </code>
     *
     * If the name already exists, it is replaced by the new clause.
     *
     * @param string $name   Wanted Name of the column (alias).
     * @param string $clause SQL clause to select from the table
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAsColumn($name, $clause)
    {
        $this->asColumns[$name] = $clause;

        return $this;
    }

    /**
     * Get the column aliases.
     *
     * @return array An assoc array which map the column alias names
     *               to the alias clauses.
     */
    public function getAsColumns()
    {
        return $this->asColumns;
    }

    /**
     * Returns the column name associated with an alias (AS-column).
     *
     * @param  string $alias
     * @return string $string
     */
    public function getColumnForAs($as)
    {
        if (isset($this->asColumns[$as])) {
            return $this->asColumns[$as];
        }
    }

    /**
     * Allows one to specify an alias for a table that can
     * be used in various parts of the SQL.
     *
     * @param string $alias
     * @param string $table
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAlias($alias, $table)
    {
        $this->aliases[$alias] = $table;

        return $this;
    }

    /**
     * Remove an alias for a table (useful when merging Criterias).
     *
     * @param string $alias
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function removeAlias($alias)
    {
        unset($this->aliases[$alias]);

        return $this;
    }

    /**
     * Returns the aliases for this Criteria
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Returns the table name associated with an alias.
     *
     * @param  string $alias
     * @return string $string
     */
    public function getTableForAlias($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }
    }

    /**
     * Returns the table name and alias based on a table alias or name.
     * Use this method to get the details of a table name that comes in a clause,
     * which can be either a table name or an alias name.
     *
     * @param  string            $tableAliasOrName
     * @return array($tableName, $tableAlias)
     */
    public function getTableNameAndAlias($tableAliasOrName)
    {
        if (isset($this->aliases[$tableAliasOrName])) {
            return array($this->aliases[$tableAliasOrName], $tableAliasOrName);
        }

        return array($tableAliasOrName, null);
    }

    /**
     * Get the keys of the criteria map, i.e. the list of columns bearing a condition
     * <code>
     * print_r($c->keys());
     *  => array('book.price', 'book.title', 'author.first_name')
     * </code>
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->map);
    }

    /**
     * Does this Criteria object contain the specified key?
     *
     * @param  string  $column [table.]column
     * @return boolean True if this Criteria object contain the specified key.
     */
    public function containsKey($column)
    {
        // must use array_key_exists() because the key could
        // exist but have a NULL value (that'd be valid).
        return isset($this->map[$column]);
    }

    /**
     * Does this Criteria object contain the specified key and does it have a value set for the key
     *
     * @param  string  $column [table.]column
     * @return boolean True if this Criteria object contain the specified key and a value for that key
     */
    public function keyContainsValue($column)
    {
        // must use array_key_exists() because the key could
        // exist but have a NULL value (that'd be valid).
        return isset($this->map[$column]) && null !== $this->map[$column]->getValue();
    }

    /**
     * Whether this Criteria has any where columns.
     *
     * This counts conditions added with the add() method.
     *
     * @return boolean
     * @see add()
     */
    public function hasWhereClause()
    {
        return !empty($this->map);
    }

    /**
     * Will force the sql represented by this criteria to be executed within
     * a transaction.  This is here primarily to support the oid type in
     * postgresql.  Though it can be used to require any single sql statement
     * to use a transaction.
     * @return void
     */
    public function setUseTransaction($v)
    {
        $this->useTransaction = (Boolean) $v;
    }

    /**
     * Whether the sql command specified by this criteria must be wrapped
     * in a transaction.
     *
     * @return boolean
     */
    public function isUseTransaction()
    {
        return $this->useTransaction;
    }

    /**
     * Method to return criteria related to columns in a table.
     *
     * Make sure you call containsKey($column) prior to calling this method,
     * since no check on the existence of the $column is made in this method.
     *
     * @param  string            $column Column name.
     * @return AbstractCriterion A Criterion object.
     */
    public function getCriterion($column)
    {
        return $this->map[$column];
    }

    /**
     * Method to return the latest Criterion in a table.
     *
     * @return AbstractCriterion A Criterion or null no Criterion is added.
     */
    public function getLastCriterion()
    {
        if ($cnt = count($this->map)) {
            $map = array_values($this->map);

            return $map[$cnt - 1];
        }

        return null;
    }

    /**
     * Method to return a Criterion that is not added automatically
     * to this Criteria.  This can be used to chain the
     * Criterions to form a more complex where clause.
     *
     * @param  string            $column     Full name of column (for example TABLE.COLUMN).
     * @param  mixed             $value
     * @param  string            $comparison Criteria comparison constant or PDO binding type
     * @return AbstractCriterion
     */
    public function getNewCriterion($column, $value = null, $comparison = self::EQUAL)
    {
        if (is_int($comparison)) {
            // $comparison is a PDO::PARAM_* constant value
            // something like $c->add('foo like ?', '%bar%', PDO::PARAM_STR);
            return new RawCriterion($this, $column, $value, $comparison);
        }
        switch ($comparison) {
            case Criteria::CUSTOM:
                // custom expression with no parameter binding
                // something like $c->add(BookTableMap::TITLE, "CONCAT(book.TITLE, 'bar') = 'foobar'", Criteria::CUSTOM);
                return new CustomCriterion($this, $value);
            case Criteria::IN:
            case Criteria::NOT_IN:
                // table.column IN (?, ?) or table.column NOT IN (?, ?)
                // something like $c->add(BookTableMap::TITLE, array('foo', 'bar'), Criteria::IN);
                return new InCriterion($this, $column, $value, $comparison);
            case Criteria::LIKE:
            case Criteria::NOT_LIKE:
            case Criteria::ILIKE:
            case Criteria::NOT_ILIKE:
                // table.column LIKE ? or table.column NOT LIKE ?  (or ILIKE for Postgres)
                // something like $c->add(BookTableMap::TITLE, 'foo%', Criteria::LIKE);
                return new LikeCriterion($this, $column, $value, $comparison);
                break;
            default:
                // simple comparison
                // something like $c->add(BookTableMap::PRICE, 12, Criteria::GREATER_THAN);
                return new BasicCriterion($this, $column, $value, $comparison);
        }
    }

    /**
     * Method to return a String table name.
     *
     * @param  string $name Name of the key.
     * @return string The value of the object at key.
     */
    public function getColumnName($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name]->getColumn();
        }

        return null;
    }

    /**
     * Shortcut method to get an array of columns indexed by table.
     * <code>
     * print_r($c->getTablesColumns());
     *  => array(
     *       'book'   => array('book.price', 'book.title'),
     *       'author' => array('author.first_name')
     *     )
     * </code>
     *
     * @return array array(table => array(table.column1, table.column2))
     */
    public function getTablesColumns()
    {
        $tables = array();
        foreach ($this->keys() as $key) {
            $tableName = substr($key, 0, strrpos($key, '.'));
            $tables[$tableName][] = $key;
        }

        return $tables;
    }

    /**
     * Method to return a comparison String.
     *
     * @param  string $key String name of the key.
     * @return string A String with the value of the object at key.
     */
    public function getComparison($key)
    {
        if (isset($this->map[$key])) {
            return $this->map[$key]->getComparison();
        }

        return null;
    }

    /**
     * Get the Database(Map) name.
     *
     * @return string A String with the Database(Map) name.
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Set the DatabaseMap name.  If <code>null</code> is supplied, uses value
     * provided by <code>Configuration::getDefaultDatasource()</code>.
     *
     * @param  string $dbName The Database (Map) name.
     * @return void
     */
    public function setDbName($dbName = null)
    {
        $this->dbName = (null === $dbName ? Propel::getServiceContainer()->getDefaultDatasource() : $dbName);
    }

    /**
     * Get the primary table for this Criteria.
     *
     * This is useful for cases where a Criteria may not contain
     * any SELECT columns or WHERE columns.  This must be explicitly
     * set, of course, in order to be useful.
     *
     * @return string
     */
    public function getPrimaryTableName()
    {
        return $this->primaryTableName;
    }

    /**
     * Sets the primary table for this Criteria.
     *
     * This is useful for cases where a Criteria may not contain
     * any SELECT columns or WHERE columns.  This must be explicitly
     * set, of course, in order to be useful.
     *
     * @param string $v
     */
    public function setPrimaryTableName($tableName)
    {
        $this->primaryTableName = $tableName;
    }

    /**
     * Method to return a String table name.
     *
     * @param  string $name The name of the key.
     * @return string The value of table for criterion at key.
     */
    public function getTableName($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name]->getTable();
        }

        return null;
    }

    /**
     * Method to return the value that was added to Criteria.
     *
     * @param  string $name A String with the name of the key.
     * @return mixed  The value of object at key.
     */
    public function getValue($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name]->getValue();
        }

        return null;
    }

    /**
     * An alias to getValue() -- exposing a Hashtable-like interface.
     *
     * @param  string $key An Object.
     * @return mixed  The value within the Criterion (not the Criterion object).
     */
    public function get($key)
    {
        return $this->getValue($key);
    }

    /**
     * Overrides Hashtable put, so that this object is returned
     * instead of the value previously in the Criteria object.
     * The reason is so that it more closely matches the behavior
     * of the add() methods. If you want to get the previous value
     * then you should first Criteria.get() it yourself. Note, if
     * you attempt to pass in an Object that is not a String, it will
     * throw a NPE. The reason for this is that none of the add()
     * methods support adding anything other than a String as a key.
     *
     * @param  string         $key
     * @param  mixed          $value
     * @return $this|Criteria Instance of self.
     */
    public function put($key, $value)
    {
        return $this->add($key, $value);
    }

    /**
     * Copies all of the mappings from the specified Map to this Criteria
     * These mappings will replace any mappings that this Criteria had for any
     * of the keys currently in the specified Map.
     *
     * if the map was another Criteria, its attributes are copied to this
     * Criteria, overwriting previous settings.
     *
     * @param mixed $t Mappings to be stored in this map.
     */
    public function putAll($t)
    {
        if (is_array($t)) {
            foreach ($t as $key => $value) {
                if ($value instanceof AbstractCriterion) {
                    $this->map[$key] = $value;
                } else {
                    $this->put($key, $value);
                }
            }
        } elseif ($t instanceof Criteria) {
            $this->joins = $t->joins;
        }
    }

    /**
     * This method adds a new criterion to the list of criterias.
     * If a criterion for the requested column already exists, it is
     * replaced. If is used as follow:
     *
     * <code>
     * $crit = new Criteria();
     * $crit->add($column, $value, Criteria::GREATER_THAN);
     * </code>
     *
     * Any comparison can be used.
     *
     * The name of the table must be used implicitly in the column name,
     * so the Column name must be something like 'TABLE.id'.
     *
     * @param string $p1         The column to run the comparison on, or a Criterion object.
     * @param mixed  $value
     * @param string $comparison A String.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function add($p1, $value = null, $comparison = null)
    {
        if ($p1 instanceof AbstractCriterion) {
            $this->map[$p1->getTable() . '.' . $p1->getColumn()] = $p1;
        } else {
            $this->map[$p1] = $this->getCriterionForCondition($p1, $value, $comparison);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(', ', $this->map) . "\n";
    }

    /**
     * This method creates a new criterion but keeps it for later use with combine()
     * Until combine() is called, the condition is not added to the query
     *
     * <code>
     * $crit = new Criteria();
     * $crit->addCond('cond1', $column1, $value1, Criteria::GREATER_THAN);
     * $crit->addCond('cond2', $column2, $value2, Criteria::EQUAL);
     * $crit->combine(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
     * </code>
     *
     * Any comparison can be used.
     *
     * The name of the table must be used implicitly in the column name,
     * so the Column name must be something like 'TABLE.id'.
     *
     * @param string $name       name to combine the criterion later
     * @param string $p1         The column to run the comparison on, or AbstractCriterion object.
     * @param mixed  $value
     * @param string $comparison A String.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addCond($name, $p1, $value = null, $comparison = null)
    {
        $this->namedCriterions[$name] = $this->getCriterionForCondition($p1, $value, $comparison);

        return $this;
    }

    /**
     * Combine several named criterions with a logical operator
     *
     * @param  array          $criterions array of the name of the criterions to combine
     * @param  string         $operator   logical operator, either Criteria::LOGICAL_AND, or Criteria::LOGICAL_OR
     * @param  string         $name       optional name to combine the criterion later
     * @return $this|Criteria
     */
    public function combine($criterions = array(), $operator = self::LOGICAL_AND, $name = null)
    {
        $operatorMethod = (self::LOGICAL_AND === strtoupper($operator)) ? 'addAnd' : 'addOr';
        $namedCriterions = array();
        foreach ($criterions as $key) {
            if (array_key_exists($key, $this->namedCriterions)) {
                $namedCriterions[]= $this->namedCriterions[$key];
                unset($this->namedCriterions[$key]);
            } else {
                throw new LogicException(sprintf('Cannot combine unknown condition %s', $key));
            }
        }
        $firstCriterion = array_shift($namedCriterions);
        foreach ($namedCriterions as $criterion) {
            $firstCriterion->$operatorMethod($criterion);
        }
        if (null === $name) {
            $this->addAnd($firstCriterion, null, null);
        } else {
            $this->addCond($name, $firstCriterion, null, null);
        }

        return $this;
    }

    /**
     * This is the way that you should add a join of two tables.
     * Example usage:
     * <code>
     * $c->addJoin(ProjectTableMap::ID, FooTableMap::PROJECT_ID, Criteria::LEFT_JOIN);
     * // LEFT JOIN FOO ON (PROJECT.ID = FOO.PROJECT_ID)
     * </code>
     *
     * @param mixed $left     A String with the left side of the join.
     * @param mixed $right    A String with the right side of the join.
     * @param mixed $joinType A String with the join operator
     *                        among Criteria::INNER_JOIN, Criteria::LEFT_JOIN,
     *                        and Criteria::RIGHT_JOIN
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addJoin($left, $right, $joinType = null)
    {
        if (is_array($left)) {
            $conditions = array();
            foreach ($left as $key => $value) {
                $condition = array($value, $right[$key]);
                $conditions[] = $condition;
            }

            return $this->addMultipleJoin($conditions, $joinType);
        }

        $join = new Join();
        $join->setIdentifierQuoting($this->isIdentifierQuotingEnabled());

        // is the left table an alias ?
        $dotpos = strrpos($left, '.');
        $leftTableAlias = substr($left, 0, $dotpos);
        $leftColumnName = substr($left, $dotpos + 1);
        list($leftTableName, $leftTableAlias) = $this->getTableNameAndAlias($leftTableAlias);

        // is the right table an alias ?
        $dotpos = strrpos($right, '.');
        $rightTableAlias = substr($right, 0, $dotpos);
        $rightColumnName = substr($right, $dotpos + 1);
        list($rightTableName, $rightTableAlias) = $this->getTableNameAndAlias($rightTableAlias);

        $join->addExplicitCondition(
            $leftTableName, $leftColumnName, $leftTableAlias,
            $rightTableName, $rightColumnName, $rightTableAlias,
            Join::EQUAL
        );

        $join->setJoinType($joinType);

        return $this->addJoinObject($join);
    }

    /**
     * Add a join with multiple conditions
     * @deprecated use Join::setJoinCondition($criterion) instead
     *
     * @see http://propel.phpdb.org/trac/ticket/167, http://propel.phpdb.org/trac/ticket/606
     *
     * Example usage:
     * $c->addMultipleJoin(array(
     *     array(LeftTableMap::LEFT_COLUMN, RightTableMap::RIGHT_COLUMN),  // if no third argument, defaults to Criteria::EQUAL
     *     array(FoldersTableMap::alias( 'fo', FoldersTableMap::LFT ), FoldersTableMap::alias( 'parent', FoldersTableMap::RGT ), Criteria::LESS_EQUAL )
     *   ),
     *   Criteria::LEFT_JOIN
      * );
     *
     * @see addJoin()
     * @param array  $conditions An array of conditions, each condition being an array (left, right, operator)
     * @param string $joinType   A String with the join operator. Defaults to an implicit join.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addMultipleJoin($conditions, $joinType = null)
    {
        $join = new Join();
        $join->setIdentifierQuoting($this->isIdentifierQuotingEnabled());
        $joinCondition = null;
        foreach ($conditions as $condition) {
            $left = $condition[0];
            $right = $condition[1];
            if ($pos = strrpos($left, '.')) {
                $leftTableAlias = substr($left, 0, $pos);
                $leftColumnName = substr($left, $pos + 1);
                list($leftTableName, $leftTableAlias) = $this->getTableNameAndAlias($leftTableAlias);
            } else {
                list($leftTableName, $leftTableAlias) = array(null, null);
                $leftColumnName = $left;
            }

            if ($pos = strrpos($right, '.')) {
                $rightTableAlias = substr($right, 0, $pos);
                $rightColumnName = substr($right, $pos + 1);
                list($rightTableName, $rightTableAlias) = $this->getTableNameAndAlias($rightTableAlias);
            } else {
                list($rightTableName, $rightTableAlias) = array(null, null);
                $rightColumnName = $right;
            }

            if (!$join->getRightTableName()) {
                $join->setRightTableName($rightTableName);
            }

            if (!$join->getRightTableAlias()) {
                $join->setRightTableAlias($rightTableAlias);
            }

            $conditionClause = $leftTableAlias ? $leftTableAlias . '.' : ($leftTableName ? $leftTableName . '.' : '');
            $conditionClause .= $leftColumnName;
            $conditionClause .= isset($condition[2]) ? $condition[2] : JOIN::EQUAL;
            $conditionClause .= $rightTableAlias ? $rightTableAlias . '.' : ($rightTableName ? $rightTableName . '.' : '');
            $conditionClause .= $rightColumnName;
            $criterion = $this->getNewCriterion($leftTableName.'.'.$leftColumnName, $conditionClause, Criteria::CUSTOM);

            if (null === $joinCondition) {
                $joinCondition = $criterion;
            } else {
                $joinCondition = $joinCondition->addAnd($criterion);
            }
        }
        $join->setJoinType($joinType);
        $join->setJoinCondition($joinCondition);

        return $this->addJoinObject($join);
    }

    /**
     * Add a join object to the Criteria
     *
     * @param Join $join A join object
     *
     * @return $this|Criteria A modified Criteria object
     */
    public function addJoinObject(Join $join)
    {
        if (!in_array($join, $this->joins)) { // compare equality, NOT identity
            $this->joins[] = $join;
        }

        return $this;
    }

    /**
     * Get the array of Joins.
     * @return Join[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * This method returns an already defined join clause from the query
     *
     * @param string $name The name of the join clause
     *
     * @return Join A join object
     */
    public function getJoin($name)
    {
        return $this->joins[$name];
    }

    /**
     * @param string $name The name of the join clause
     *
     * @return Join A join object
     */
    public function hasJoin($name)
    {
        return isset($this->joins[$name]);
    }

    /**
     * Adds a Criteria as subQuery in the From Clause.
     *
     * @param Criteria $subQueryCriteria Criteria to build the subquery from
     * @param string   $alias            alias for the subQuery
     *
     * @return $this|Criteria this modified Criteria object (Fluid API)
     */
    public function addSelectQuery(Criteria $subQueryCriteria, $alias = null)
    {
        if (null === $alias) {
            $alias = 'alias_' . ($subQueryCriteria->forgeSelectQueryAlias() + count($this->selectQueries));
        }
        $this->selectQueries[$alias] = $subQueryCriteria;

        return $this;
    }

    /**
     * Checks whether this Criteria has a subquery.
     *
     * @return boolean
     */
    public function hasSelectQueries()
    {
        return (Boolean) $this->selectQueries;
    }

    /**
     * Get the associative array of Criteria for the subQueries per alias.
     *
     * @return Criteria[]
     */
    public function getSelectQueries()
    {
        return $this->selectQueries;
    }

    /**
     * Get the Criteria for a specific subQuery.
     *
     * @param  string   $alias alias for the subQuery
     * @return Criteria
     */
    public function getSelectQuery($alias)
    {
        return $this->selectQueries[$alias];
    }

    /**
     * checks if the Criteria for a specific subQuery is set.
     *
     * @param  string  $alias alias for the subQuery
     * @return boolean
     */
    public function hasSelectQuery($alias)
    {
        return isset($this->selectQueries[$alias]);
    }

    public function forgeSelectQueryAlias()
    {
        $aliasNumber = 0;
        foreach ($this->getSelectQueries() as $c1) {
            $aliasNumber += $c1->forgeSelectQueryAlias();
        }

        return ++$aliasNumber;
    }

    /**
     * Adds 'ALL' modifier to the SQL statement.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setAll()
    {
        $this->removeSelectModifier(self::DISTINCT);
        $this->addSelectModifier(self::ALL);

        return $this;
    }

    /**
     * Adds 'DISTINCT' modifier to the SQL statement.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setDistinct()
    {
        $this->removeSelectModifier(self::ALL);
        $this->addSelectModifier(self::DISTINCT);

        return $this;
    }

    /**
     * Adds a modifier to the SQL statement.
     * e.g. self::ALL, self::DISTINCT, 'SQL_CALC_FOUND_ROWS', 'HIGH_PRIORITY', etc.
     *
     * @param string $modifier The modifier to add
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function addSelectModifier($modifier)
    {
        // only allow the keyword once
        if (!$this->hasSelectModifier($modifier)) {
            $this->selectModifiers[] = $modifier;
        }

        return $this;
    }

    /**
     * Removes a modifier to the SQL statement.
     * Checks for existence before removal
     *
     * @param string $modifier The modifier to add
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function removeSelectModifier($modifier)
    {
        $this->selectModifiers = array_values(array_diff($this->selectModifiers, array($modifier)));

        return $this;
    }

    /**
     * Checks the existence of a SQL select modifier
     *
     * @param string $modifier The modifier to add
     *
     * @return boolean
     */
    public function hasSelectModifier($modifier)
    {
        return in_array($modifier, $this->selectModifiers);
    }

    /**
     * Sets ignore case.
     *
     * @param  boolean        $b True if case should be ignored.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreCase = (Boolean) $b;

        return $this;
    }

    /**
     * Is ignore case on or off?
     *
     * @return boolean True if case is ignored.
     */
    public function isIgnoreCase()
    {
        return $this->ignoreCase;
    }

    /**
     * Set single record?  Set this to <code>true</code> if you expect the query
     * to result in only a single result record (the default behaviour is to
     * throw a PropelException if multiple records are returned when the query
     * is executed).  This should be used in situations where returning multiple
     * rows would indicate an error of some sort.  If your query might return
     * multiple records but you are only interested in the first one then you
     * should be using setLimit(1).
     *
     * @param  boolean        $b Set to TRUE if you expect the query to select just one record.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setSingleRecord($b)
    {
        $this->singleRecord = (Boolean) $b;

        return $this;
    }

    /**
     * Is single record?
     *
     * @return boolean True if a single record is being returned.
     */
    public function isSingleRecord()
    {
        return $this->singleRecord;
    }

    /**
     * Set limit.
     *
     * @param  int            $limit An int with the value for limit.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setLimit($limit)
    {
        // TODO: do we enforce int here? 32bit issue if we do
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get limit.
     *
     * @return int An int with the value for limit.
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set offset.
     *
     * @param  int            $offset An int with the value for offset.  (Note this values is
     *                        cast to a 32bit integer and may result in truncation)
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setOffset($offset)
    {
        $this->offset = (int) $offset;

        return $this;
    }

    /**
     * Get offset.
     *
     * @return int An int with the value for offset.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Add select column.
     *
     * @param  string         $name Name of the select column.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function addSelectColumn($name)
    {
        $this->selectColumns[] = $name;

        return $this;
    }

    /**
     * Set the query comment, that appears after the first verb in the SQL query
     *
     * @param  string         $comment The comment to add to the query, without comment sign
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setComment($comment = null)
    {
        $this->queryComment = $comment;

        return $this;
    }

    /**
     * Get the query comment, that appears after the first verb in the SQL query
     *
     * @return string The comment to add to the query, without comment sign
     */
    public function getComment()
    {
        return $this->queryComment;
    }

    /**
     * Whether this Criteria has any select columns.
     *
     * This will include columns added with addAsColumn() method.
     *
     * @return boolean
     * @see addAsColumn()
     * @see addSelectColumn()
     */
    public function hasSelectClause()
    {
        return !empty($this->selectColumns) || !empty($this->asColumns);
    }

    /**
     * Get select columns.
     *
     * @return array An array with the name of the select columns.
     */
    public function getSelectColumns()
    {
        return $this->selectColumns;
    }

    /**
     * Clears current select columns.
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function clearSelectColumns()
    {
        $this->selectColumns = $this->asColumns = array();

        return $this;
    }

    /**
     * Get select modifiers.
     *
     * @return array An array with the select modifiers.
     */
    public function getSelectModifiers()
    {
        return $this->selectModifiers;
    }

    /**
     * Add group by column name.
     *
     * @param  string         $groupBy The name of the column to group by.
     * @return $this|Criteria A modified Criteria object.
     */
    public function addGroupByColumn($groupBy)
    {
        $this->groupByColumns[] = $groupBy;

        return $this;
    }

    /**
     * Add order by column name, explicitly specifying ascending.
     *
     * @param  string         $name The name of the column to order by.
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAscendingOrderByColumn($name)
    {
        $this->orderByColumns[] = $name . ' ' . self::ASC;

        return $this;
    }

    /**
     * Add order by column name, explicitly specifying descending.
     *
     * @param  string         $name The name of the column to order by.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function addDescendingOrderByColumn($name)
    {
        $this->orderByColumns[] = $name . ' ' . self::DESC;

        return $this;
    }

    /**
     * Get order by columns.
     *
     * @return array An array with the name of the order columns.
     */
    public function getOrderByColumns()
    {
        return $this->orderByColumns;
    }

    /**
     * Clear the order-by columns.
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function clearOrderByColumns()
    {
        $this->orderByColumns = array();

        return $this;
    }

    /**
     * Clear the group-by columns.
     *
     * @return $this|Criteria
     */
    public function clearGroupByColumns()
    {
        $this->groupByColumns = array();

        return $this;
    }

    /**
     * Get group by columns.
     *
     * @return array
     */
    public function getGroupByColumns()
    {
        return $this->groupByColumns;
    }

    /**
     * Get Having Criterion.
     *
     * @return AbstractCriterion A Criterion object that is the having clause.
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Remove an object from the criteria.
     *
     * @param  string $key A string with the key to be removed.
     * @return mixed  The removed value.
     */
    public function remove($key)
    {
        if (isset($this->map[$key])) {
            $removed = $this->map[$key];
            unset($this->map[$key]);
            if ($removed instanceof AbstractCriterion) {
                return $removed->getValue();
            }

            return $removed;
        }
    }

    /**
     * Build a string representation of the Criteria.
     *
     * @return string A String with the representation of the Criteria.
     */
    public function toString()
    {

        $sb = 'Criteria:';
        try {

            $params = array();
            $sb .= "\nSQL (may not be complete): ".$this->createSelectSql($params);

            $sb .= "\nParams: ";
            $paramstr = array();
            foreach ($params as $param) {
                $paramstr[] = $param['table'] . '.' . $param['column'] . ' => ' . var_export($param['value'], true);
            }
            $sb .= implode(', ', $paramstr);

        } catch (\Exception $exc) {
            $sb .= '(Error: ' . $exc->getMessage() . ')';
        }

        return $sb;
    }

    /**
     * Returns the size (count) of this criteria.
     * @return int
     */
    public function size()
    {
        return count($this->map);
    }

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashtable entries.
     * @return boolean
     */
    public function equals($crit)
    {
        if (null === $crit || !($crit instanceof Criteria)) {
            return false;
        }

        /** @var Criteria $crit */
        if ($this === $crit) {
            return true;
        }

        if ($this->size() === $crit->size()) {

            // Important: nested criterion objects are checked

            $criteria = $crit; // alias
            if  ($this->offset            === $criteria->getOffset()
                && $this->limit           === $criteria->getLimit()
                && $this->ignoreCase      === $criteria->isIgnoreCase()
                && $this->singleRecord    === $criteria->isSingleRecord()
                && $this->dbName          === $criteria->getDbName()
                && $this->selectModifiers === $criteria->getSelectModifiers()
                && $this->selectColumns   === $criteria->getSelectColumns()
                && $this->asColumns       === $criteria->getAsColumns()
                && $this->orderByColumns  === $criteria->getOrderByColumns()
                && $this->groupByColumns  === $criteria->getGroupByColumns()
                && $this->aliases         === $criteria->getAliases()
               ) // what about having ??
            {
                foreach ($criteria->keys() as $key) {
                    if ($this->containsKey($key)) {
                        $a = $this->getCriterion($key);
                        $b = $criteria->getCriterion($key);
                        if (!$a->equals($b)) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }

                $joins = $criteria->getJoins();
                if (count($joins) != count($this->joins)) {
                    return false;
                }

                foreach ($joins as $key => $join) {
                    if (!$join->equals($this->joins[$key])) {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Add the content of a Criteria to the current Criteria
     * In case of conflict, the current Criteria keeps its properties
     *
     * @param Criteria $criteria The criteria to read properties from
     * @param string   $operator The logical operator used to combine conditions
     *                           Defaults to Criteria::LOGICAL_AND, also accepts Criteria::LOGICAL_OR
     *                           This parameter is deprecated, use _or() instead
     *
     * @return $this|Criteria The current criteria object
     */
    public function mergeWith(Criteria $criteria, $operator = null)
    {
        // merge limit
        $limit = $criteria->getLimit();
        if (0 != $limit && -1 === $this->getLimit()) {
            $this->limit = $limit;
        }

        // merge offset
        $offset = $criteria->getOffset();
        if (0 != $offset && 0 === $this->getOffset()) {
            $this->offset = $offset;
        }

        // merge select modifiers
        $selectModifiers = $criteria->getSelectModifiers();
        if ($selectModifiers && ! $this->selectModifiers) {
            $this->selectModifiers = $selectModifiers;
        }

        // merge select columns
        $this->selectColumns = array_merge($this->getSelectColumns(), $criteria->getSelectColumns());

        // merge as columns
        $commonAsColumns = array_intersect_key($this->getAsColumns(), $criteria->getAsColumns());
        if (!empty($commonAsColumns)) {
            throw new LogicException('The given criteria contains an AsColumn with an alias already existing in the current object');
        }
        $this->asColumns = array_merge($this->getAsColumns(), $criteria->getAsColumns());

        // merge orderByColumns
        $orderByColumns = array_merge($this->getOrderByColumns(), $criteria->getOrderByColumns());
        $this->orderByColumns = array_unique($orderByColumns);

        // merge groupByColumns
        $groupByColumns = array_merge($this->getGroupByColumns(), $criteria->getGroupByColumns());
        $this->groupByColumns = array_unique($groupByColumns);

        // merge where conditions
        if (Criteria::LOGICAL_OR === $operator) {
            $this->_or();
        }
        $isFirstCondition = true;
        foreach ($criteria->getMap() as $key => $criterion) {
            if ($isFirstCondition && Criteria::LOGICAL_OR === $this->defaultCombineOperator) {
                $this->addOr($criterion, null, null, false);
                $this->defaultCombineOperator = Criteria::LOGICAL_AND;
            } elseif ($this->containsKey($key)) {
                $this->addAnd($criterion);
            } else {
                $this->add($criterion);
            }
            $isFirstCondition = false;
        }

        // merge having
        if ($having = $criteria->getHaving()) {
            if ($this->getHaving()) {
                $this->addHaving($this->getHaving()->addAnd($having));
            } else {
                $this->addHaving($having);
            }
        }

        // merge alias
        $commonAliases = array_intersect_key($this->getAliases(), $criteria->getAliases());
        if (!empty($commonAliases)) {
            throw new LogicException('The given criteria contains an alias already existing in the current object');
        }
        $this->aliases = array_merge($this->getAliases(), $criteria->getAliases());

        // merge join
        $this->joins = array_merge($this->getJoins(), $criteria->getJoins());

        return $this;
    }

    /**
     * This method adds a prepared Criterion object to the Criteria as a having clause.
     * You can get a new, empty Criterion object with the
     * getNewCriterion() method.
     *
     * <p>
     * <code>
     * $crit = new Criteria();
     * $c = $crit->getNewCriterion(BaseTableMap::ID, 5, Criteria::LESS_THAN);
     * $crit->addHaving($c);
     * </code>
     *
     * @param mixed $p1         A Criterion, or a SQL clause with a question mark placeholder, or a column name
     * @param mixed $value      The value to bind in the condition
     * @param mixed $comparison A PDO::PARAM_ class constant
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addHaving($p1, $value = null, $comparison = null)
    {
        $this->having = $this->getCriterionForCondition($p1, $value, $comparison);

        return $this;
    }

    /**
     * Build a Criterion.
     *
     * This method has multiple signatures, and behaves differently according to it:
     *
     *  - If the first argument is a Criterion, it just returns this Criterion.
     *    <code>$c->getCriterionForCondition($criterion); // returns $criterion</code>
     *
     *  - If the last argument is a PDO::PARAM_* constant value, create a Criterion
     *    using Criteria::RAW and $comparison as a type.
     *    <code>$c->getCriterionForCondition('foo like ?', '%bar%', PDO::PARAM_STR);</code>
     *
     *  - Otherwise, create a classic Criterion based on a column name and a comparison.
     *    <code>$c->getCriterionForCondition(BookTableMap::TITLE, 'War%', Criteria::LIKE);</code>
     *
     * @param mixed $p1         A Criterion, or a SQL clause with a question mark placeholder, or a column name
     * @param mixed $value      The value to bind in the condition
     * @param mixed $comparison A Criteria class constant, or a PDO::PARAM_ class constant
     *
     * @return AbstractCriterion
     */
    protected function getCriterionForCondition($p1, $value = null, $comparison = null)
    {
        if ($p1 instanceof AbstractCriterion) {
            // it's already a Criterion, so ignore $value and $comparison
            return $p1;
        }

        // $comparison is one of Criteria's constants, or a PDO binding type
        // something like $c->add(BookTableMap::TITLE, 'War%', Criteria::LIKE);
        return $this->getNewCriterion($p1, $value, $comparison);
    }

    /**
     * If a criterion for the requested column already exists, the condition is "AND"ed to the existing criterion (necessary for Propel 1.4 compatibility).
     * If no criterion for the requested column already exists, the condition is "AND"ed to the latest criterion.
     * If no criterion exist, the condition is added a new criterion
     *
     * Any comparison can be used.
     *
     * Supports a number of different signatures:
     *  - addAnd(column, value, comparison)
     *  - addAnd(column, value)
     *  - addAnd(Criterion)
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAnd($p1, $p2 = null, $p3 = null, $preferColumnCondition = true)
    {
        $criterion = $this->getCriterionForCondition($p1, $p2, $p3);

        $key = $criterion->getTable() . '.' . $criterion->getColumn();
        if ($preferColumnCondition && $this->containsKey($key)) {
            // FIXME: addAnd() operates preferably on existing conditions on the same column
            // this may cause unexpected results, but it's there for BC with Propel 14
            $this->getCriterion($key)->addAnd($criterion);
        } else {
            // simply add the condition to the list - this is the expected behavior
            $this->add($criterion);
        }

        return $this;
    }

    /**
     * If a prior criterion exists, the condition is "OR"ed to it.
     * If no criterion exist, the condition is added a new criterion
     *
     * Any comparison can be used.
     *
     * Supports a number of different signatures:
     *  - addOr(column, value, comparison)
     *  - addOr(column, value)
     *  - addOr(Criterion)
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addOr($p1, $p2 = null, $p3 = null, $preferColumnCondition = true)
    {
        $rightCriterion = $this->getCriterionForCondition($p1, $p2, $p3);

        $leftCriterion = $this->getLastCriterion();

        if (null !== $leftCriterion) {
            // combine the given criterion with the existing one with an 'OR'
            $leftCriterion->addOr($rightCriterion);
        } else {
            // nothing to do OR / AND with, so make it first condition
            $this->add($rightCriterion);
        }

        return $this;
    }

    /**
     * Overrides Criteria::add() to use the default combine operator
     * @see Criteria::add()
     *
     * @param string|AbstractCriterion $p1                    The column to run the comparison on (e.g. BookTableMap::ID), or Criterion object
     * @param mixed                    $value
     * @param string                   $operator              A String, like Criteria::EQUAL.
     * @param boolean                  $preferColumnCondition If true, the condition is combined with an existing condition on the same column
    *                      (necessary for Propel 1.4 compatibility).
     *                     If false, the condition is combined with the last existing condition.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addUsingOperator($p1, $value = null, $operator = null, $preferColumnCondition = true)
    {
        if (Criteria::LOGICAL_OR === $this->defaultCombineOperator) {
            $this->defaultCombineOperator = Criteria::LOGICAL_AND;

            return $this->addOr($p1, $value, $operator, $preferColumnCondition);
        }

        return $this->addAnd($p1, $value, $operator, $preferColumnCondition);
    }

    /**
     * Method to create an SQL query based on values in a Criteria.
     *
     * This method creates only prepared statement SQL (using ? where values
     * will go).  The second parameter ($params) stores the values that need
     * to be set before the statement is executed.  The reason we do it this way
     * is to let the PDO layer handle all escaping & value formatting.
     *
     * @param  array  &$params Parameters that are to be replaced in prepared statement.
     * @return string
     *
     * @throws \Propel\Runtime\Exception\PropelException Trouble creating the query string.
     */
    public function createSelectSql(&$params)
    {
        $adapter = Propel::getServiceContainer()->getAdapter($this->getDbName());
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());

        $fromClause = array();
        $joinClause = array();
        $joinTables = array();
        $whereClause = array();
        $orderByClause = array();

        $orderBy = $this->getOrderByColumns();

        // get the first part of the SQL statement, the SELECT part
        $selectSql = $adapter->createSelectSqlPart($this, $fromClause);
        $this->replaceNames($selectSql);

        // Handle joins
        // joins with a null join type will be added to the FROM clause and the condition added to the WHERE clause.
        // joins of a specified type: the LEFT side will be added to the fromClause and the RIGHT to the joinClause
        foreach ($this->getJoins() as $join) {
            $join->setAdapter($adapter);

            // add 'em to the queues..
            if (!$fromClause) {
                $fromClause[] = $join->getLeftTableWithAlias();
            }
            $joinTables[] = $join->getRightTableWithAlias();
            $joinClauseString = $join->getClause($params);
            $this->replaceNames($joinClauseString);
            $joinClause[] = $joinClauseString;
        }

        // add the criteria to WHERE clause
        // this will also add the table names to the FROM clause if they are not already
        // included via a LEFT JOIN
        foreach ($this->keys() as $key) {
            $criterion = $this->getCriterion($key);
            $table = null;
            foreach ($criterion->getAttachedCriterion() as $attachedCriterion) {
                $tableName = $attachedCriterion->getTable();

                $table = $this->getTableForAlias($tableName);
                if ($table !== null) {
                    $fromClause[] = $table . ' ' . $tableName;
                } else {
                    $fromClause[] = $tableName;
                    $table = $tableName;
                }

                if ($this->isIgnoreCase() && method_exists($attachedCriterion, 'setIgnoreCase')
                    && $dbMap->getTable($table)->getColumn($attachedCriterion->getColumn())->isText()) {
                    $attachedCriterion->setIgnoreCase(true);
                }
            }

            $criterion->setAdapter($adapter);

            $sb = '';
            $criterion->appendPsTo($sb, $params);
            $this->replaceNames($sb);
            $whereClause[] = $sb;
        }

        // Unique from clause elements
        $fromClause = array_unique($fromClause);
        $fromClause = array_diff($fromClause, array(''));

        // tables should not exist in both the from and join clauses
        if ($joinTables && $fromClause) {
            foreach ($fromClause as $fi => $ftable) {
                if (in_array($ftable, $joinTables)) {
                    unset($fromClause[$fi]);
                }
            }
        }

        $having = $this->getHaving();
        $havingString = null;
        if (null !== $having) {
            $sb = '';
            $having->appendPsTo($sb, $params);
            $this->replaceNames($sb);
            $havingString = $sb;
        }

        if (!empty($orderBy)) {

            foreach ($orderBy as $orderByColumn) {

                // Add function expression as-is.
                if (strpos($orderByColumn, '(') !== false) {
                    $orderByClause[] = $orderByColumn;
                    continue;
                }

                // Split orderByColumn (i.e. "table.column DESC")
                $dotPos = strrpos($orderByColumn, '.');

                if ($dotPos !== false) {
                    $tableName = substr($orderByColumn, 0, $dotPos);
                    $columnName = substr($orderByColumn, $dotPos + 1);
                } else {
                    $tableName = '';
                    $columnName = $orderByColumn;
                }

                $spacePos = strpos($columnName, ' ');

                if ($spacePos !== false) {
                    $direction = substr($columnName, $spacePos);
                    $columnName = substr($columnName, 0, $spacePos);
                } else {
                    $direction = '';
                }

                $tableAlias = $tableName;
                if ($aliasTableName = $this->getTableForAlias($tableName)) {
                    $tableName = $aliasTableName;
                }

                $columnAlias = $columnName;
                if ($asColumnName = $this->getColumnForAs($columnName)) {
                    $columnName = $asColumnName;
                }

                $column = $tableName ? $dbMap->getTable($tableName)->getColumn($columnName) : null;

                if ($this->isIgnoreCase() && $column && $column->isText()) {
                    $ignoreCaseColumn = $adapter->ignoreCaseInOrderBy("$tableAlias.$columnAlias");
                    $this->replaceNames($ignoreCaseColumn);
                    $orderByClause[] =  $ignoreCaseColumn . $direction;
                    $selectSql .= ', ' . $ignoreCaseColumn;
                } else {
                    $this->replaceNames($orderByColumn);
                    $orderByClause[] = $orderByColumn;
                }
            }
        }

        if (empty($fromClause) && $this->getPrimaryTableName()) {
            $fromClause[] = $this->getPrimaryTableName();
        }

        // tables should not exist as alias of subQuery
        if ($this->hasSelectQueries()) {
            foreach ($fromClause as $key => $ftable) {
                if (false !== strpos($ftable, ' ')) {
                    list(, $tableName) = explode(' ', $ftable);
                } else {
                    $tableName = $ftable;
                }
                if ($this->hasSelectQuery($tableName)) {
                    unset($fromClause[$key]);
                }
            }
        }

        // from / join tables quoted if it is necessary
        $fromClause = array_map(array($this, 'quoteIdentifierTable'), $fromClause);
        $joinClause = $joinClause ? $joinClause : array_map(array($this, 'quoteIdentifierTable'), $joinClause);

        // add subQuery to From after adding quotes
        foreach ($this->getSelectQueries() as $subQueryAlias => $subQueryCriteria) {
            $fromClause[] = '(' . $subQueryCriteria->createSelectSql($params) . ') AS ' . $subQueryAlias;
        }

        // build from-clause
        $from = '';
        if (!empty($joinClause) && count($fromClause) > 1) {
            $from .= implode(" CROSS JOIN ", $fromClause);
        } else {
            $from .= implode(", ", $fromClause);
        }

        $from .= $joinClause ? ' ' . implode(' ', $joinClause) : '';

        // Build the SQL from the arrays we compiled
        $sql =  $selectSql
            .' FROM '  . $from
            .($whereClause ? ' WHERE '.implode(' AND ', $whereClause) : '');

        if ($groupBy = $adapter->getGroupBy($this)) {
            $this->replaceNames($groupBy);
            $sql .= $groupBy;
        }

        $sql .= ($havingString ? ' HAVING '.$havingString : '')
             .($orderByClause ? ' ORDER BY '.implode(',', $orderByClause) : '');

        if ($this->getLimit() >= 0 || $this->getOffset()) {
            $adapter->applyLimit($sql, $this->getOffset(), $this->getLimit(), $this);
        }

        return $sql;
    }

    /**
     * This method does only quote identifier, the method doReplaceNameInExpression of child ModelCriteria class does more.
     *
     * @param array $matches Matches found by preg_replace_callback
     *
     * @return string the column name replacement
     */
    protected function doReplaceNameInExpression($matches)
    {
        return $this->quoteIdentifier($matches[0]);
    }

    /**
     * Quotes identifier based on $this->isIdentifierQuotingEnabled() and $tableMap->isIdentifierQuotingEnabled.
     *
     * @param string $string
     * @return string
     */
    public function quoteIdentifier($string, $tableName = '')
    {
        if ($this->isIdentifierQuotingEnabled()) {
            $adapter = Propel::getServiceContainer()->getAdapter($this->getDbName());

            return $adapter->quote($string);
        }

        //find table name and ask tableMap if quoting is enabled
        if (!$tableName && false !== ($pos = strrpos($string, '.'))) {
            $tableName = substr($string, 0, $pos);
        }

        $tableMapName = $this->getTableForAlias($tableName) ?: $tableName;

        if ($tableMapName) {
            $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());
            if ($dbMap->hasTable($tableMapName)) {
                $tableMap = $dbMap->getTable($tableMapName);
                if ($tableMap->isIdentifierQuotingEnabled()) {
                    $adapter = Propel::getServiceContainer()->getAdapter($this->getDbName());

                    return $adapter->quote($string);
                }
            }
        }

        return $string;
    }

    public function quoteIdentifierTable($string)
    {
        $realTableName = $string;
        if (false !== ($pos = strrpos($string, ' '))) {
            $realTableName = substr($string, 0, $pos);
        }

        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());

        if ($this->isIdentifierQuotingEnabled()) {
            $adapter = Propel::getServiceContainer()->getAdapter($this->getDbName());

            return $adapter->quoteIdentifierTable($string);
        }

        if ($dbMap->hasTable($realTableName)) {
            $tableMap = $dbMap->getTable($realTableName);
            if ($tableMap->isIdentifierQuotingEnabled()) {
                $adapter = Propel::getServiceContainer()->getAdapter($this->getDbName());

                return $adapter->quoteIdentifierTable($string);
            }
        }

        return $string;
    }

    /**
     * Replaces complete column names (like Article.AuthorId) in an SQL clause
     * by their exact Propel column fully qualified name (e.g. article.author_id)
     * but ignores the column names inside quotes
     * e.g. 'CONCAT(Book.AuthorID, "Book.AuthorID") = ?'
     *   => 'CONCAT(book.author_id, "Book.AuthorID") = ?'
     *
     * @param string $sql SQL clause to inspect (modified by the method)
     *
     * @return boolean Whether the method managed to find and replace at least one column name
     */
    public function replaceNames(&$sql)
    {
        $this->replacedColumns = array();
        $this->currentAlias = '';
        $this->foundMatch = false;
        $isAfterBackslash = false;
        $isInString = false;
        $stringQuotes = '';
        $parsedString = '';
        $stringToTransform = '';
        $len = strlen($sql);
        $pos = 0;
        while ($pos < $len) {
            $char = $sql[$pos];
            // check flags for strings or escaper
            switch ($char) {
                case '\\':
                    $isAfterBackslash = true;
                    break;
                case "'":
                case '"':
                    if ($isInString && $stringQuotes == $char) {
                        if (!$isAfterBackslash) {
                            $isInString = false;
                        }
                    } elseif (!$isInString) {
                        $parsedString .= preg_replace_callback("/[\w\\\]+\.\w+/", array($this, 'doReplaceNameInExpression'), $stringToTransform);
                        $stringToTransform = '';
                        $stringQuotes = $char;
                        $isInString = true;
                    }
                    break;
            }

            if ('\\' !== $char) {
                $isAfterBackslash = false;
            }

            if ($isInString) {
                $parsedString .= $char;
            } else {
                $stringToTransform .= $char;
            }

            $pos++;
        }

        if ($stringToTransform) {
            $parsedString .= preg_replace_callback("/[\w\\\]+\.\w+/", array($this, 'doReplaceNameInExpression'), $stringToTransform);
        }

        $sql = $parsedString;

        return $this->foundMatch;
    }

    /**
     * Method to perform inserts based on values and keys in a
     * Criteria.
     * <p>
     * If the primary key is auto incremented the data in Criteria
     * will be inserted and the auto increment value will be returned.
     * <p>
     * If the primary key is included in Criteria then that value will
     * be used to insert the row.
     * <p>
     * If no primary key is included in Criteria then we will try to
     * figure out the primary key from the database map and insert the
     * row with the next available id using util.db.IDBroker.
     * <p>
     * If no primary key is defined for the table the values will be
     * inserted as specified in Criteria and null will be returned.
     *
     * @param  ConnectionInterface $con A ConnectionInterface connection.
     * @return mixed               The primary key for the new row if the primary key is auto-generated. Otherwise will return null.
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function doInsert(ConnectionInterface $con = null)
    {
        // The primary key
        $id = null;
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection($this->getDbName());
        }
        $db = Propel::getServiceContainer()->getAdapter($this->getDbName());

        // Get the table name and method for determining the primary
        // key value.
        $keys = $this->keys();
        if (!empty($keys)) {
            $tableName = $this->getTableName($keys[0]);
        } else {
            throw new PropelException('Database insert attempted without anything specified to insert.');
        }

        $tableName = $this->getTableName($keys[0]);
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());
        $tableMap = $dbMap->getTable($tableName);
        $keyInfo = $tableMap->getPrimaryKeyMethodInfo();
        $useIdGen = $tableMap->isUseIdGenerator();
        //$keyGen = $con->getIdGenerator();

        $pk = $this->getPrimaryKey();

        // only get a new key value if you need to
        // the reason is that a primary key might be defined
        // but you are still going to set its value. for example:
        // a join table where both keys are primary and you are
        // setting both columns with your own values

        // pk will be null if there is no primary key defined for the table
        // we're inserting into.
        if (null !== $pk && $useIdGen && !$this->keyContainsValue($pk->getFullyQualifiedName()) && $db->isGetIdBeforeInsert()) {
            try {
                $id = $db->getId($con, $keyInfo);
            } catch (\Exception $e) {
                throw new PropelException('Unable to get sequence id.', 0, $e);
            }
            $this->add($pk->getFullyQualifiedName(), $id);
        }

        try {
            $qualifiedCols = $this->keys(); // we need table.column cols when populating values
            $columns = array(); // but just 'column' cols for the SQL
            foreach ($qualifiedCols as $qualifiedCol) {
                $columns[] = substr($qualifiedCol, strrpos($qualifiedCol, '.') + 1);
            }

            // add identifiers
            $columns = array_map(array($this, 'quoteIdentifier'), $columns);
            $tableName = $this->quoteIdentifierTable($tableName);

            $sql = 'INSERT INTO ' . $tableName
                . ' (' . implode(',', $columns) . ')'
                . ' VALUES (';
            // . substr(str_repeat("?,", count($columns)), 0, -1) .
            for ($p = 1, $cnt = count($columns); $p <= $cnt; $p++) {
                $sql .= ':p'.$p;
                if ($p !== $cnt) {
                    $sql .= ',';
                }
            }
            $sql .= ')';

            $params = $this->buildParams($qualifiedCols);

            $db->cleanupSQL($sql, $params, $this, $dbMap);

            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap, $db);
            $stmt->execute();

        } catch (\Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        // If the primary key column is auto-incremented, get the id now.
        if (null !== $pk && $useIdGen && $db->isGetIdAfterInsert()) {
            try {
                $id = $db->getId($con, $keyInfo);
            } catch (\Exception $e) {
                throw new PropelException("Unable to get autoincrement id.", 0, $e);
            }
        }

        return $id;
    }

    public function getPrimaryKey(Criteria $criteria = null)
    {
        if (!$criteria) {
            $criteria = $this;
        }
        // Assume all the keys are for the same table.
        $keys = $criteria->keys();
        $key = $keys[0];
        $table = $criteria->getTableName($key);

        $pk = null;

        if (!empty($table)) {
            $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());

            $pks = $dbMap->getTable($table)->getPrimaryKeys();
            if (!empty($pks)) {
                $pk = array_shift($pks);
            }
        }

        return $pk;
    }

    /**
     * Method used to update rows in the DB.  Rows are selected based
     * on selectCriteria and updated using values in updateValues.
     * <p>
     * Use this method for performing an update of the kind:
     * <p>
     * WHERE some_column = some value AND could_have_another_column =
     * another value AND so on.
     *
     * @param Criteria            $updateValues A Criteria object containing values used in set clause.
     * @param ConnectionInterface $con          The ConnectionInterface connection object to use.
     *
     * @return int The number of rows affected by last update statement.
     *             For most uses there is only one update statement executed, so this number will
     *             correspond to the number of rows affected by the call to this method.
     *             Note that the return value does require that this information is returned
     *             (supported) by the Propel db driver.
     *
     * @throws PropelException
     */
    public function doUpdate($updateValues, ConnectionInterface $con)
    {
        /** @var PdoAdapter $db */
        $db = Propel::getServiceContainer()->getAdapter($this->getDbName());
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());

        // Get list of required tables, containing all columns
        $tablesColumns = $this->getTablesColumns();
        if (empty($tablesColumns) && ($table = $this->getPrimaryTableName())) {
            $tablesColumns = array($table => array());
        }

        // we also need the columns for the update SQL
        $updateTablesColumns = $updateValues->getTablesColumns();

        // If no columns are changing values, we may get here with
        // an empty array in $updateTablesColumns.  In that case,
        // there is nothing to do, so we return the rows affected,
        // which is 0.  Fixes a bug in which an UPDATE statement
        // would fail in this instance.

        if (empty($updateTablesColumns)) {
            return 0;
        }

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        foreach ($tablesColumns as $tableName => $columns) {

            $whereClause = array();
            $params = array();
            $stmt = null;
            try {
                $sql = 'UPDATE ';
                if ($queryComment = $this->getComment()) {
                    $sql .= '/* ' . $queryComment . ' */ ';
                }
                // is it a table alias?
                if ($realTableName = $this->getTableForAlias($tableName)) {
                    $updateTable = $realTableName . ' ' . $tableName;
                    $tableName = $realTableName;
                } else {
                    $updateTable = $tableName;
                }
                $sql .= $this->quoteIdentifierTable($updateTable);
                $sql .= " SET ";
                $p = 1;
                foreach ($updateTablesColumns[$tableName] as $col) {
                    $updateColumnName = substr($col, strrpos($col, '.') + 1);
                    // add identifiers for the actual database?
                    $updateColumnName = $this->quoteIdentifier($updateColumnName, $tableName);
                    if ($updateValues->getComparison($col) != Criteria::CUSTOM_EQUAL) {
                        $sql .= $updateColumnName . '=:p'.$p++.', ';
                    } else {
                        $param = $updateValues->get($col);
                        $sql .= $updateColumnName . ' = ';
                        if (is_array($param)) {
                            if (isset($param['raw'])) {
                                $raw = $param['raw'];
                                $rawcvt = '';
                                // parse the $params['raw'] for ? chars
                                for ($r = 0, $len = strlen($raw); $r < $len; $r++) {
                                    if ($raw{$r} == '?') {
                                        $rawcvt .= ':p'.$p++;
                                    } else {
                                        $rawcvt .= $raw{$r};
                                    }
                                }
                                $sql .= $rawcvt . ', ';
                            } else {
                                $sql .= ':p'.$p++.', ';
                            }
                            if (isset($param['value'])) {
                                $updateValues->put($col, $param['value']);
                            }
                        } else {
                            $updateValues->remove($col);
                            $sql .= $param . ', ';
                        }
                    }
                }

                $params = $this->buildParams($updateTablesColumns[$tableName], $updateValues);

                $sql = substr($sql, 0, -2);
                if (!empty($columns)) {
                    foreach ($columns as $colName) {
                        $sb = '';
                        $this->getCriterion($colName)->appendPsTo($sb, $params);
                        $this->replaceNames($sb);
                        $whereClause[] = $sb;
                    }
                    $sql .= ' WHERE ' .  implode(' AND ', $whereClause);
                }

                $db->cleanupSQL($sql, $params, $updateValues, $dbMap);

                $stmt = $con->prepare($sql);

                // Replace ':p?' with the actual values
                $db->bindValues($stmt, $params, $dbMap, $db);

                $stmt->execute();

                $affectedRows = $stmt->rowCount();

                $stmt = null; // close

            } catch (\Exception $e) {
                if ($stmt) {
                    $stmt = null; // close
                }
                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException(sprintf('Unable to execute UPDATE statement [%s]', $sql), 0, $e);
            }

        } // foreach table in the criteria

        return $affectedRows;
    }

    public function buildParams($columns, Criteria $values = null)
    {
        if (!$values) {
            $values = $this;
        }
        $params = array();
        foreach ($columns as $key) {
            if ($values->containsKey($key)) {
                $crit = $values->getCriterion($key);
                $params[] = array(
                    'column' => $crit->getColumn(),
                    'table' => $crit->getTable(),
                    'value' => $crit->getValue()
                );
            }
        }

        return $params;
    }

    public function doCount(ConnectionInterface $con = null)
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());
        $db = Propel::getServiceContainer()->getAdapter($this->getDbName());

        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $needsComplexCount = $this->getGroupByColumns()
            || $this->getOffset()
            || $this->getLimit() >= 0
            || $this->getHaving()
            || in_array(Criteria::DISTINCT, $this->getSelectModifiers())
            || count($this->selectQueries) > 0
        ;

        $params = array();
        if ($needsComplexCount) {
            if ($this->needsSelectAliases()) {
                if ($this->getHaving()) {
                    throw new LogicException('Propel cannot create a COUNT query when using HAVING and  duplicate column names in the SELECT part');
                }
                $db->turnSelectColumnsToAliases($this);
            }
            $selectSql = $this->createSelectSql($params);
            $sql = 'SELECT COUNT(*) FROM (' . $selectSql . ') propelmatch4cnt';
        } else {
            // Replace SELECT columns with COUNT(*)
            $this->clearSelectColumns()->addSelectColumn('COUNT(*)');
            $sql = $this->createSelectSql($params);
        }
        try {
            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap);
            $stmt->execute();
        } catch (\Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute COUNT statement [%s]', $sql));
        }

        return $con->getDataFetcher($stmt);
    }

    /**
     * Checks whether the Criteria needs to use column aliasing
     * This is implemented in a service class rather than in Criteria itself
     * in order to avoid doing the tests when it's not necessary (e.g. for SELECTs)
     */
    public function needsSelectAliases()
    {
        $columnNames = array();
        foreach ($this->getSelectColumns() as $fullyQualifiedColumnName) {
            if ($pos = strrpos($fullyQualifiedColumnName, '.')) {
                $columnName = substr($fullyQualifiedColumnName, $pos);
                if (isset($columnNames[$columnName])) {
                    // more than one column with the same name, so aliasing is required
                    return true;
                }
                $columnNames[$columnName] = true;
            }
        }

        return false;
    }

    /**
     * Issue a DELETE query based on the current ModelCriteria
     * This method is called by ModelCriteria::delete() inside a transaction
     *
     * @param ConnectionInterface $con a connection object
     *
     * @return int             the number of deleted rows
     * @throws PropelException
     */
    public function doDelete(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection($this->getDbName());
        }

        $adapter = Propel::getServiceContainer()->getAdapter($this->getDbName());
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());

        // join are not supported with DELETE statement
        if (count($this->getJoins())) {
            throw new PropelException('Delete does not support join');
        }

        // Set up a list of required tables (one DELETE statement will
        // be executed per table)
        $tables = $this->getTablesColumns();
        if (empty($tables)) {
            throw new PropelException("Cannot delete from an empty Criteria");
        }

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        foreach ($tables as $tableName => $columns) {

            $whereClause = array();
            $params = array();
            $stmt = null;
            try {
                $sql = $adapter->getDeleteFromClause($this, $tableName);

                foreach ($columns as $colName) {
                    $sb = '';
                    $this->getCriterion($colName)->appendPsTo($sb, $params);
                    $this->replaceNames($sb);
                    $whereClause[] = $sb;
                }
                $sql .= ' WHERE ' .  implode(' AND ', $whereClause);

                $stmt = $con->prepare($sql);

                $adapter->bindValues($stmt, $params, $dbMap);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
            } catch (\Exception $e) {
                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException(sprintf('Unable to execute DELETE statement [%s]', $sql), 0, $e);
            }

        } // for each table

        return $affectedRows;
    }

    /**
     * Builds, binds and executes a SELECT query based on the current object.
     *
     * @param ConnectionInterface $con A connection object
     *
     * @return DataFetcherInterface A dataFetcher using the connection, ready to be fetched
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function doSelect(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName());
        $db = Propel::getServiceContainer()->getAdapter($this->getDbName());

        $params = array();
        $sql = $this->createSelectSql($params);
        try {
            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap);
            $stmt->execute();
        } catch (\Exception $e) {
            if (isset($stmt)) {
                $stmt = null; // close
            }
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), null, $e);
        }

        return $con->getDataFetcher($stmt);
    }

    // Fluid operators

    public function _or()
    {
        $this->defaultCombineOperator = Criteria::LOGICAL_OR;

        return $this;
    }

    public function _and()
    {
        $this->defaultCombineOperator = Criteria::LOGICAL_AND;

        return $this;
    }

    // Fluid Conditions

    /**
     * Returns the current object if the condition is true,
     * or a PropelConditionalProxy instance otherwise.
     * Allows for conditional statements in a fluid interface.
     *
     * @param boolean $cond
     *
     * @return PropelConditionalProxy|$this|Criteria
     */
    public function _if($cond)
    {
        $this->conditionalProxy = new PropelConditionalProxy($this, $cond, $this->conditionalProxy);

        return $this->conditionalProxy->getCriteriaOrProxy();
    }

    /**
     * Returns a PropelConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @param boolean $cond ignored
     *
     * @return PropelConditionalProxy|$this|Criteria
     */
    public function _elseif($cond)
    {
        if (!$this->conditionalProxy) {
            throw new LogicException(__METHOD__ .' must be called after _if()');
        }

        return $this->conditionalProxy->_elseif($cond);
    }

    /**
     * Returns a PropelConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @return PropelConditionalProxy|$this|Criteria
     */
    public function _else()
    {
        if (!$this->conditionalProxy) {
            throw new LogicException(__METHOD__ .' must be called after _if()');
        }

        return $this->conditionalProxy->_else();
    }

    /**
     * Returns the current object
     * Allows for conditional statements in a fluid interface.
     *
     * @return $this|Criteria
     */
    public function _endif()
    {
        if (!$this->conditionalProxy) {
            throw new LogicException(__METHOD__ .' must be called after _if()');
        }

        $this->conditionalProxy = $this->conditionalProxy->getParentProxy();

        if ($this->conditionalProxy) {
            return $this->conditionalProxy->getCriteriaOrProxy();
        }

        // reached last level
        return $this;
    }

    /**
     * Ensures deep cloning of attached objects
     */
    public function __clone()
    {
        foreach ($this->map as $key => $criterion) {
            $this->map[$key] = clone $criterion;
        }

        foreach ($this->joins as $key => $join) {
            $this->joins[$key] = clone $join;
        }

        if (null !== $this->having) {
            $this->having = clone $this->having;
        }
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $identifierQuoting
     */
    public function setIdentifierQuoting($identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
    }

}
