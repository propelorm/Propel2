<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Query\Criteria;


/**
 * Interface for adapters.
 *
 */
interface AdapterInterface
{
    const ID_METHOD_NONE            = 0;
    const ID_METHOD_AUTOINCREMENT   = 1;
    const ID_METHOD_SEQUENCE        = 2;

    /**
     * Build database connection
     *
     * @param array    $conparams connection parameters
     *
     * @return Propel\Runtime\Connection\ConnectionInterface
     */
    function getConnection($conparams);

    /**
     * Sets the character encoding using SQL standard SET NAMES statement.
     *
     * This method is invoked from the default initConnection() method and must
     * be overridden for an RDMBS which does _not_ support this SQL standard.
     *
     * @see       initConnection()
     *
     * @param     Propel\Runtime\Connection\ConnectionInterface $con
     * @param     string  $charset  The $string charset encoding.
     */
    function setCharset(ConnectionInterface $con, $charset);

    /**
     * This method is used to ignore case.
     *
     * @param     string  $in The string to transform to upper case.
     * @return    string  The upper case string.
     */
    function toUpperCase($in);

    /**
     * This method is used to ignore case.
     *
     * @param     string  $in The string whose case to ignore.
     * @return    string  The string in a case that can be ignored.
     */
    function ignoreCase($in);

    /**
     * This method is used to ignore case in an ORDER BY clause.
     * Usually it is the same as ignoreCase, but some databases
     * (Interbase for example) does not use the same SQL in ORDER BY
     * and other clauses.
     *
     * @param     string  $in  The string whose case to ignore.
     * @return    string  The string in a case that can be ignored.
     */
    function ignoreCaseInOrderBy($in);

    /**
     * Returns the character used to indicate the beginning and end of
     * a piece of text used in a SQL statement (generally a single
     * quote).
     *
     * @return    string  The text delimeter.
     */
    function getStringDelimiter();

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param     string  $s1  String to concatenate.
     * @param     string  $s2  String to append.
     *
     * @return    string
     */
    function concatString($s1, $s2);

    /**
     * Returns SQL which extracts a substring.
     *
     * @param     string   $s  String to extract from.
     * @param     integer  $pos  Offset to start from.
     * @param     integer  $len  Number of characters to extract.
     *
     * @return    string
     */
    function subString($s, $pos, $len);

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param     string  $s  String to calculate length of.
     * @return    string
     */
    function strLength($s);

    /**
     * Quotes database objec identifiers (table names, col names, sequences, etc.).
     * @param     string  $text  The identifier to quote.
     * @return    string  The quoted identifier.
     */
    function quoteIdentifier($text);

    /**
     * Quotes a database table which could have space seperating it from an alias,
     * both should be identified separately. This doesn't take care of dots which
     * separate schema names from table names. Adapters for RDBMs which support
     * schemas have to implement that in the platform-specific way.
     *
     * @param     string  $table  The table name to quo
     * @return    string  The quoted table name
     **/
    function quoteIdentifierTable($table);

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return    boolean
     */
    function isGetIdBeforeInsert();

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return    boolean
     */
    function isGetIdAfterInsert();

    /**
     * Gets the generated ID (either last ID for autoincrement or next sequence ID).
     *
     * @param     Propel\Runtime\Connection\ConnectionInterface $con
     * @param     string  $name
     *
     * @return    mixed
     */
    function getId(ConnectionInterface $con, $name = null);

    /**
     * Formats a temporal value before binding, given a ColumnMap object
     *
     * @param     mixed      $value  The temporal value
     * @param     Propel\Runtime\Map\ColumnMap  $cMap
     *
     * @return    string  The formatted temporal value
     */
    function formatTemporalValue($value, ColumnMap $cMap);

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return    string
     */
    function getTimestampFormatter();

    /**
     * Returns date formatter string for use in date() function.
     *
     * @return    string
     */
    function getDateFormatter();

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return    string
     */
    function getTimeFormatter();

    /**
     * Should Column-Names get identifiers for inserts or updates.
     * By default false is returned -> backwards compability.
     *
     * it`s a workaround...!!!
     *
     * @todo       should be abstract
     * @deprecated
     *
     * @return    boolean
     */
    function useQuoteIdentifier();

    /**
     * Allows manipulation of the query string before StatementPdo is instantiated.
     *
     * @param     string       $sql  The sql statement
     * @param     array        $params  array('column' => ..., 'table' => ..., 'value' => ...)
     * @param     Propel\Runtime\Map\Criteria     $values
     * @param     Propel\Runtime\Map\DatabaseMap  $dbMap
     */
    function cleanupSQL(&$sql, array &$params, Criteria $values, DatabaseMap $dbMap);

    /**
     * Modifies the passed-in SQL to add LIMIT and/or OFFSET.
     *
     * @param     string   $sql
     * @param     integer  $offset
     * @param     integer  $limit
     */
    function applyLimit(&$sql, $offset, $limit);

    /**
     * Gets the SQL string that this adapter uses for getting a random number.
     *
     * @param     mixed $seed (optional) seed value for databases that support this
     */
    function random($seed = null);

    /**
     * Returns the "DELETE FROM <table> [AS <alias>]" part of DELETE query.
     *
     * @param     Propel\Runtime\Map\Criteria  $criteria
     * @param     string    $tableName
     *
     * @return    string
     */
    function getDeleteFromClause(Criteria $criteria, $tableName);

    /**
     * Builds the SELECT part of a SQL statement based on a Criteria
     * taking into account select columns and 'as' columns (i.e. columns aliases)
     * Move from BasePeer to AbstractAdapter and turn from static to non static
     *
     * @param     Propel\Runtime\Map\Criteria  $criteria
     * @param     array     $fromClause
     * @param     boolean   $aliasAll
     *
     * @return    string
     */
    function createSelectSqlPart(Criteria $criteria, &$fromClause, $aliasAll = false);

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     * Moved from BasePeer to AbstractAdapter and turned from static to non static
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param     Propel\Runtime\Map\Criteria  $criteria
     * @return    Propel\Runtime\Map\Criteria  The input, with Select columns replaced by aliases
     */
    function turnSelectColumnsToAliases(Criteria $criteria);

    /**
     * Binds values in a prepared statement.
     *
     * This method is designed to work with the BasePeer::createSelectSql() method, which creates
     * both the SELECT SQL statement and populates a passed-in array of parameter
     * values that should be substituted.
     *
     * <code>
     * $db = Propel::getAdapter($criteria->getDbName());
     * $sql = BasePeer::createSelectSql($criteria, $params);
     * $stmt = $con->prepare($sql);
     * $params = array();
     * $db->populateStmtValues($stmt, $params, Configuration::getInstance()->getDatabaseMap($critera->getDbName()));
     * $stmt->execute();
     * </code>
     *
     * @param     Propel\Runtime\Connection\StatementInterface $stmt
     * @param     array         $params  array('column' => ..., 'table' => ..., 'value' => ...)
     * @param     Propel\Runtime\Map\DatabaseMap   $dbMap
     */
    function bindValues(StatementInterface $stmt, array $params, DatabaseMap $dbMap);

    /**
     * Binds a value to a positioned parameted in a statement,
     * given a ColumnMap object to infer the binding type.
     *
     * @param     Propel\Runtime\Connection\StatementInterface $stmt  The statement to bind
     * @param     string        $parameter  Parameter identifier
     * @param     mixed         $value  The value to bind
     * @param     Propel\Runtime\Map\ColumnMap     $cMap  The ColumnMap of the column to bind
     * @param     null|integer  $position  The position of the parameter to bind
     *
     * @return    boolean
     */
    function bindValue(StatementInterface $stmt, $parameter, $value, ColumnMap $cMap, $position = null);
}
