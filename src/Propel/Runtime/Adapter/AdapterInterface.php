<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\ColumnMap;

/**
 * Interface for adapters.
 */
interface AdapterInterface
{
    /**
     * @var int
     */
    public const ID_METHOD_NONE = 0;

    /**
     * @var int
     */
    public const ID_METHOD_AUTOINCREMENT = 1;

    /**
     * @var int
     */
    public const ID_METHOD_SEQUENCE = 2;

    /**
     * Build database connection
     *
     * @param array $params connection parameters
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getConnection(array $params): ConnectionInterface;

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
    public function setCharset(ConnectionInterface $con, string $charset): void;

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
    public function ignoreCaseInOrderBy(string $in): string;

    /**
     * Returns the character used to indicate the beginning and end of
     * a piece of text used in a SQL statement (generally a single
     * quote).
     *
     * @return string The text delimiter.
     */
    public function getStringDelimiter(): string;

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param string $s1 String to concatenate.
     * @param string $s2 String to append.
     *
     * @return string
     */
    public function concatString(string $s1, string $s2): string;

    /**
     * Returns SQL which extracts a substring.
     *
     * @param string $s String to extract from.
     * @param int $pos Offset to start from.
     * @param int $len Number of characters to extract.
     *
     * @return string
     */
    public function subString(string $s, int $pos, int $len): string;

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param string $s String to calculate length of.
     *
     * @return string
     */
    public function strLength(string $s): string;

    /**
     * Quotes database object identifiers (table names, col names, sequences, etc.).
     *
     * @param string $text The identifier to quote.
     *
     * @return string The quoted identifier.
     */
    public function quoteIdentifier(string $text): string;

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
    public function quoteIdentifierTable(string $table): string;

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
    public function quote(string $text): string;

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return bool
     */
    public function isGetIdBeforeInsert(): bool;

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return bool
     */
    public function isGetIdAfterInsert(): bool;

    /**
     * Gets the generated ID (either last ID for autoincrement or next sequence ID).
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param string|null $name
     *
     * @return string|int|null
     */
    public function getId(ConnectionInterface $con, ?string $name = null);

    /**
     * Formats a temporal value before binding, given a ColumnMap object
     *
     * @param mixed $value The temporal value
     * @param \Propel\Runtime\Map\ColumnMap $cMap
     *
     * @return string The formatted temporal value
     */
    public function formatTemporalValue($value, ColumnMap $cMap): string;

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimestampFormatter(): string;

    /**
     * Returns date formatter string for use in date() function.
     *
     * @return string
     */
    public function getDateFormatter(): string;

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimeFormatter(): string;

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return string
     */
    public function getGroupBy(Criteria $criteria): string;
}
