<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Lock;
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * This is used in order to connect to a SQLite database.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqliteAdapter extends PdoAdapter implements SqlAdapterInterface
{
    /**
     * For SQLite this method has no effect, since SQLite doesn't support specifying a character
     * set (or, another way to look at it, it doesn't require a single character set per DB).
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con A PDO connection instance.
     * @param string $charset The charset encoding.
     *
     * @return void
     */
    public function setCharset(ConnectionInterface $con, string $charset): void
    {
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param array $settings
     *
     * @return void
     */
    public function initConnection(ConnectionInterface $con, array $settings): void
    {
        $con->query('PRAGMA foreign_keys = ON');
        parent::initConnection($con, $settings);

        //add regex support
        $con->sqliteCreateFunction('regexp', function ($pattern, $value) {
            mb_regex_encoding('UTF-8');

            return (mb_ereg($pattern, $value) !== false) ? 1 : 0;
        });
    }

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param string $s1 String to concatenate.
     * @param string $s2 String to append.
     *
     * @return string
     */
    public function concatString(string $s1, string $s2): string
    {
        return "($s1 || $s2)";
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param string $s String to extract from.
     * @param int $pos Offset to start from.
     * @param int $len Number of characters to extract.
     *
     * @return string
     */
    public function subString(string $s, int $pos, int $len): string
    {
        return "substr($s, $pos, $len)";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param string $s String to calculate length of.
     *
     * @return string
     */
    public function strLength(string $s): string
    {
        return "length($s)";
    }

    /**
     * @see \Propel\Runtime\Adapter\AdapterInterface::quoteIdentifier()
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        return "[$text]";
    }

    /**
     * @see SqlAdapterInterface::applyLimit()
     *
     * @param string $sql
     * @param int $offset
     * @param int $limit
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     *
     * @return void
     */
    public function applyLimit(string &$sql, int $offset, int $limit, ?Criteria $criteria = null): void
    {
        if ($limit >= 0) {
            $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . $offset : '');
        } elseif ($offset > 0) {
            $sql .= sprintf(' LIMIT -1 OFFSET %s', $offset);
        }
    }

    /**
     * @param string|null $seed
     *
     * @return string
     */
    public function random(?string $seed = null): string
    {
        return 'random()';
    }

    /**
     * @see SqlAdapterInterface::applyLock()
     *
     * @param string $sql
     * @param \Propel\Runtime\ActiveQuery\Lock $lock
     *
     * @return void
     */
    public function applyLock(string &$sql, Lock $lock): void
    {
    }
}
