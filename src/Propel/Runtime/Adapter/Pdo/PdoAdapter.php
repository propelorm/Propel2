<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter\Pdo;

use PDO;
use PDOException;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Base for PDO database adapters.
 */
abstract class PdoAdapter
{
    /**
     * Indicates if the database system can process DELETE statements with
     * aliases like 'DELETE t FROM my_table t JOIN my_other_table o ON ...'
     *
     * @see PdoAdapter::supportsAliasesInDelete()
     *
     * @var bool
     */
    protected const SUPPORTS_ALIASES_IN_DELETE = true;

    /**
     * Build database connection
     *
     * @param array $params connection parameters
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     * @throws \Propel\Runtime\Adapter\Exception\AdapterException
     *
     * @return \Propel\Runtime\Connection\PdoConnection
     */
    public function getConnection(array $params): PdoConnection
    {
        $params = $this->prepareParams($params);

        if (!isset($params['dsn'])) {
            throw new InvalidArgumentException('No dsn specified in your connection parameters');
        }

        $dsn = $params['dsn'];
        $user = $params['user'] ?? null;
        $password = $params['password'] ?? null;

        // load any driver options from the config file
        // driver options are those PDO settings that have to be passed during the connection construction
        $driverOptions = [];
        if (isset($params['options']) && is_array($params['options'])) {
            foreach ($params['options'] as $option => $optiondata) {
                $value = $optiondata;
                if (is_string($value) && strpos($value, '::') !== false) {
                    if (!defined($value)) {
                        throw new InvalidArgumentException(sprintf('Error processing driver options for dsn "%s"', $dsn));
                    }
                    $value = constant($value);
                }
                $driverOptions[$option] = $value;
            }
        }

        try {
            $con = new PdoConnection($dsn, $user, $password, $driverOptions);
            $this->initConnection($con, isset($params['settings']) && is_array($params['settings']) ? $params['settings'] : []);
        } catch (PDOException $e) {
            throw new AdapterException('Unable to open PDO connection', 0, $e);
        }

        return $con;
    }

    /**
     * @param string $left
     * @param string $right
     *
     * @return string
     */
    public function compareRegex(string $left, string $right): string
    {
        return sprintf('%s REGEXP %s', $left, $right);
    }

    /**
     * @return string
     */
    public function getAdapterId(): string
    {
        $class = str_replace('Adapter', '', static::class);
        $lastSlash = strrpos($class, '\\');

        return strtolower(substr($class, $lastSlash + 1));
    }

    /**
     * Prepare the parameters for a Connection
     *
     * @param array $params the connection parameters from the configuration
     *
     * @return array the modified parameters
     */
    protected function prepareParams(array $params): array
    {
        return $params;
    }

    /**
     * This method is called after a connection was created to run necessary
     * post-initialization queries or code.
     *
     * If a charset was specified, this will be set before any other queries
     * are executed.
     *
     * This base method runs queries specified using the "query" setting.
     *
     * @see setCharset()
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param array $settings An array of settings.
     *
     * @return void
     */
    public function initConnection(ConnectionInterface $con, array $settings): void
    {
        if (isset($settings['charset'])) {
            $this->setCharset($con, $settings['charset']);
        }

        if (isset($settings['queries']) && is_array($settings['queries'])) {
            foreach ($settings['queries'] as $query) {
                $con->exec($query);
            }
        }
    }

    /**
     * Sets the character encoding using SQL standard SET NAMES statement.
     *
     * This method is invoked from the default initConnection() method and must
     * be overridden for an RDMBS which does _not_ support this SQL standard.
     *
     * @see initConnection()
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param string $charset The $string charset encoding.
     *
     * @return void
     */
    public function setCharset(ConnectionInterface $con, string $charset): void
    {
        $con->exec(sprintf("SET NAMES '%s'", $charset));
    }

    /**
     * This method is used to ignore case.
     *
     * @param string $in The string to transform to upper case.
     *
     * @return string The upper case string.
     */
    public function toUpperCase(string $in): string
    {
        return sprintf('UPPER(%s)', $in);
    }

    /**
     * This method is used to ignore case.
     *
     * @param string $in The string whose case to ignore.
     *
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCase(string $in): string
    {
        return sprintf('UPPER(%s)', $in);
    }

    /**
     * This method is used to ignore case in an ORDER BY clause.
     * Usually it is the same as ignoreCase, but some databases
     * (Interbase for example) does not use the same SQL in ORDER BY
     * and other clauses.
     *
     * @param string $in The string whose case to ignore.
     *
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCaseInOrderBy(string $in): string
    {
        return $this->ignoreCase($in);
    }

    /**
     * Returns the character used to indicate the beginning and end of
     * a piece of text used in a SQL statement (generally a single
     * quote).
     *
     * @return string The text delimiter.
     */
    public function getStringDelimiter(): string
    {
        return '\'';
    }

    /**
     * Quotes database object identifiers (table names, col names, sequences, etc.).
     *
     * @param string $text The identifier to quote.
     *
     * @return string The quoted identifier.
     */
    public function quoteIdentifier(string $text): string
    {
        return '"' . $text . '"';
    }

    /**
     * Quotes full qualified column names and table names.
     *
     * book.author_id => `book`.`author_id`
     * author_id => `author_id`
     *
     * @param string $text
     *
     * @return string
     */
    public function quote(string $text): string
    {
        $pos = strrpos($text, '.');
        if ($pos !== false) {
            $table = substr($text, 0, $pos);
            $column = substr($text, $pos + 1);
        } else {
            $table = '';
            $column = $text;
        }

        if ($table) {
            return $this->quoteIdentifierTable($table) . '.' . $this->quoteIdentifier($column);
        }

        return $this->quoteIdentifier($column);
    }

    /**
     * Quotes a database table which could have space separating it from an alias,
     * both should be identified separately. This doesn't take care of dots which
     * separate schema names from table names. Adapters for RDBMs which support
     * schemas have to implement that in the platform-specific way.
     *
     * @param string $table The table name to quo
     *
     * @return string The quoted table name
     */
    public function quoteIdentifierTable(string $table): string
    {
        return implode(' ', array_map([$this, 'quoteIdentifier'], explode(' ', $table)));
    }

    /**
     * Returns the native ID method for this RDBMS.
     *
     * @return int One of AdapterInterface:ID_METHOD_SEQUENCE, AdapterInterface::ID_METHOD_AUTOINCREMENT.
     */
    protected function getIdMethod(): int
    {
        return AdapterInterface::ID_METHOD_AUTOINCREMENT;
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return bool
     */
    public function isGetIdBeforeInsert(): bool
    {
        return $this->getIdMethod() === AdapterInterface::ID_METHOD_SEQUENCE;
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return bool
     */
    public function isGetIdAfterInsert(): bool
    {
        return $this->getIdMethod() === AdapterInterface::ID_METHOD_AUTOINCREMENT;
    }

    /**
     * Gets the generated ID (either last ID for autoincrement or next sequence ID).
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param string|null $name
     *
     * @return mixed
     */
    public function getId(ConnectionInterface $con, ?string $name = null)
    {
        return $con->lastInsertId($name);
    }

    /**
     * Formats a temporal value before binding, given a ColumnMap object
     *
     * @param mixed $value The temporal value
     * @param \Propel\Runtime\Map\ColumnMap $cMap
     *
     * @return string The formatted temporal value
     */
    public function formatTemporalValue($value, ColumnMap $cMap): string
    {
        /** @var \Propel\Runtime\Util\PropelDateTime|null $dt */
        $dt = PropelDateTime::newInstance($value);
        if ($dt) {
            switch ($cMap->getType()) {
                case PropelTypes::TIMESTAMP:
                case PropelTypes::BU_TIMESTAMP:
                case PropelTypes::DATETIME:
                    $value = $dt->format($this->getTimestampFormatter());

                    break;
                case PropelTypes::DATE:
                case PropelTypes::BU_DATE:
                    $value = $dt->format($this->getDateFormatter());

                    break;
                case PropelTypes::TIME:
                    $value = $dt->format($this->getTimeFormatter());

                    break;
            }
        }

        return $value;
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimestampFormatter(): string
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return string
     */
    public function getGroupBy(Criteria $criteria): string
    {
        $groupBy = $criteria->getGroupByColumns();
        if ($groupBy) {
            return 'GROUP BY ' . implode(',', $groupBy);
        }

        return '';
    }

    /**
     * Returns date formatter string for use in date() function.
     *
     * @return string
     */
    public function getDateFormatter(): string
    {
        return 'Y-m-d';
    }

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimeFormatter(): string
    {
        return 'H:i:s.u';
    }

    /**
     * Allows manipulation of the query string before StatementPdo is instantiated.
     *
     * @param string $sql The sql statement
     * @param array $params array('column' => ..., 'table' => ..., 'value' => ...)
     * @param \Propel\Runtime\ActiveQuery\Criteria $values
     * @param \Propel\Runtime\Map\DatabaseMap $dbMap
     *
     * @return void
     */
    public function cleanupSQL(string &$sql, array &$params, Criteria $values, DatabaseMap $dbMap): void
    {
    }

    /**
     * Builds the SELECT part of a SQL statement based on a Criteria
     * taking into account select columns and 'as' columns (i.e. columns aliases)
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param array $fromClause
     * @param bool $aliasAll
     *
     * @return string
     */
    public function createSelectSqlPart(Criteria $criteria, array &$fromClause, bool $aliasAll = false): string
    {
        $selectClause = [];

        if ($aliasAll) {
            $this->turnSelectColumnsToAliases($criteria);
            // no select columns after that, they are all aliases
        } else {
            foreach ($criteria->getSelectColumns() as $columnName) {
                // expect every column to be of "table.column" formation
                // it could be a function:  e.g. MAX(books.price)
                $selectClause[] = $columnName; // the full column name: e.g. MAX(books.price)

                $parenPos = strrpos($columnName, '(');
                $dotPos = strrpos($columnName, '.', ($parenPos !== false ? $parenPos : 0));

                if ($dotPos === false) {
                    continue;
                }

                if ($parenPos === false) { // table.column
                    $tableName = substr($columnName, 0, $dotPos);
                } else { // FUNC(table.column)
                    // functions may contain qualifiers so only take the last
                    // word as the table name.
                    // COUNT(DISTINCT books.price)
                    $tableName = substr($columnName, $parenPos + 1, $dotPos - ($parenPos + 1));
                    $lastSpace = strrpos($tableName, ' ');
                    if ($lastSpace !== false) { // COUNT(DISTINCT books.price)
                        $tableName = substr($tableName, $lastSpace + 1);
                    }
                }
                // resolve table alias
                $sourceTableName = $criteria->getTableForAlias($tableName);
                $fromClause[] = ($sourceTableName) ? $sourceTableName . ' ' . $tableName : $tableName;
            }
        }

        // set the aliases
        foreach ($criteria->getAsColumns() as $alias => $col) {
            $selectClause[] = $col . ' AS ' . $alias;
        }

        $selectModifiers = $criteria->getSelectModifiers();
        $queryComment = $criteria->getComment();

        // Build the SQL from the arrays we compiled
        $sql = 'SELECT '
            . ($queryComment ? '/* ' . $queryComment . ' */ ' : '')
            . ($selectModifiers ? (implode(' ', $selectModifiers) . ' ') : '')
            . implode(', ', $selectClause);

        return $sql;
    }

    /**
     * Returns all selected columns that are selected without a aggregate function.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return list<string>
     */
    public function getPlainSelectedColumns(Criteria $criteria): array
    {
        $selected = [];
        foreach ($criteria->getSelectColumns() as $columnName) {
            if (strpos($columnName, '(') === false) {
                $selected[] = $columnName;
            }
        }

        foreach ($criteria->getAsColumns() as $col) {
            if (strpos($col, '(') === false && !in_array($col, $selected, true)) {
                $selected[] = $col;
            }
        }

        return $selected;
    }

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return \Propel\Runtime\ActiveQuery\Criteria The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria): Criteria
    {
        $selectColumns = $criteria->getSelectColumns();
        // clearSelectColumns also clears the aliases, so get them too
        $asColumns = $criteria->getAsColumns();
        $criteria->clearSelectColumns();
        $columnAliases = $asColumns;
        // add the select columns back
        foreach ($selectColumns as $clause) {
            // Generate a unique alias
            $baseAlias = preg_replace('/\W/', '_', $clause);
            $alias = $baseAlias;
            // If it already exists, add a unique suffix
            $i = 0;
            while (isset($columnAliases[$alias])) {
                $i++;
                $alias = $baseAlias . '_' . $i;
            }
            // Add it as an alias
            $criteria->addAsColumn($alias, $clause);
            $columnAliases[$alias] = $clause;
        }
        // Add the aliases back, don't modify them
        foreach ($asColumns as $name => $clause) {
            $criteria->addAsColumn($name, $clause);
        }

        return $criteria;
    }

    /**
     * Binds values in a prepared statement.
     *
     * This method is designed to work with the Criteria::createSelectSql() method, which creates
     * both the SELECT SQL statement and populates a passed-in array of parameter
     * values that should be substituted.
     *
     * <code>
     * $adapter = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
     * $sql = $criteria->createSelectSql($params);
     * $stmt = $con->prepare($sql);
     * $params = [];
     * $adapter->populateStmtValues($stmt, $params, Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName()));
     * $stmt->execute();
     * </code>
     *
     * @param \Propel\Runtime\Connection\StatementInterface $stmt
     * @param array $params array('column' => ..., 'table' => ..., 'value' => ...)
     * @param \Propel\Runtime\Map\DatabaseMap $dbMap
     *
     * @return void
     */
    public function bindValues(StatementInterface $stmt, array $params, DatabaseMap $dbMap): void
    {
        $position = 0;
        foreach ($params as $param) {
            $position++;
            $parameter = ':p' . $position;
            $value = $param['value'];
            if ($value === null) {
                $stmt->bindValue($parameter, null, PDO::PARAM_NULL);

                continue;
            }
            $tableName = $param['table'];
            if ($tableName === null) {
                $type = $param['type'] ?? PDO::PARAM_STR;
                $stmt->bindValue($parameter, $value, $type);

                continue;
            }
            $cMap = $dbMap->getTable($tableName)->getColumn($param['column']);
            $this->bindValue($stmt, $parameter, $value, $cMap, $position);
        }
    }

    /**
     * Binds a value to a positioned parameter in a statement,
     * given a ColumnMap object to infer the binding type.
     *
     * @param \Propel\Runtime\Connection\StatementInterface $stmt The statement to bind
     * @param string $parameter Parameter identifier
     * @param mixed $value The value to bind
     * @param \Propel\Runtime\Map\ColumnMap $cMap The ColumnMap of the column to bind
     * @param int|null $position The position of the parameter to bind
     *
     * @return bool
     */
    public function bindValue(StatementInterface $stmt, string $parameter, $value, ColumnMap $cMap, ?int $position = null): bool
    {
        if ($cMap->isTemporal()) {
            $value = $this->formatTemporalValue($value, $cMap);
        } elseif (is_resource($value) && $cMap->isLob()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            rewind($value);
        }

        return $stmt->bindValue($parameter, $value, $cMap->getPdoType());
    }

    /**
     * @see \Propel\Runtime\Adapter\SqlAdapterInterface::supportsAliasesInDelete()
     *
     * @return bool
     */
    public function supportsAliasesInDelete(): bool
    {
        return static::SUPPORTS_ALIASES_IN_DELETE;
    }
}
