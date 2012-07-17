<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Sql;

use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Builder\Util\ColumnValue;
use Propel\Generator\Builder\Util\DataRow;

/**
 * Baseclass for SQL data dump SQL building classes.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class DataSQLBuilder extends DataModelBuilder
{
    /**
     * Performs any reset between runs of this builder.
     *
     * This can be used, for example, to clear any stored start/end SQL.
     */
    public static function reset()
    {
        // does nothing by default
    }

    /**
     * Returns any SQL to place at the start of all the row inserts.
     *
     * @return string
     */
    public static function getDatabaseStartSql()
    {
        return '';
    }

    /**
     * Returns any SQL to place at the end of all the row inserts.
     *
     * @return string
     */
    public static function getDatabaseEndSql()
    {
        return '';
    }

    /**
     * Returns any SQL to place before row inserts for a new table.
     *
     * @return string
     */
    public function getTableStartSql()
    {
        return '';
    }

    /**
     * Returns any SQL to place at the end of row inserts for a table.
     *
     * @return string
     */
    public function getTableEndSql()
    {
        return '';
    }

    /**
     * The main method in this class, returns the SQL for INSERTing data into a
     * row.
     *
     * @param  DataRow $row
     * @return string
     */
    public function buildRowSql(DataRow $row)
    {
        // add column names to SQL
        $colNames = array();
        foreach ($row->getColumnValues() as $colValue) {
            $colNames[] = $this->quoteIdentifier($colValue->getColumn()->getName());
        }

        $colVals = array();
        foreach ($row->getColumnValues() as $colValue) {
            $colVals[] = $this->getColumnValueSql($colValue);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s);',
            $this->quoteIdentifier($this->getTable()->getName()),
            implode(',', $colNames),
            implode(',', $colVals)
        );

        return $sql."\n";
    }

    /**
     * Returns the property escaped (and quoted) value for a column.
     *
     * @param  ColumnValue $colValue
     * @return mixed
     */
    protected function getColumnValueSql(ColumnValue $colValue)
    {
        $column = $colValue->getColumn();
        $method = 'get' . $column->getPhpNative() . 'Sql';

        return $this->$method($colValue->getValue());
    }

    /**
     * Returns a representation of a binary value suitable for use in a SQL
     * statement. Default behavior is true = 1, false = 0.
     *
     * @param  boolean $value
     * @return integer
     */
    protected function getBooleanSql($value)
    {
        return (int) $value;
    }

    /**
     * Returns a representation of a BLOB/LONGVARBINARY value suitable for use
     * in a SQL statement.
     *
     * @param  mixed  $blob
     * @return string
     */
    protected function getBlobSql($blob)
    {
        // they took magic __toString() out of PHP5.0.0; this sucks
        if (is_object($blob)) {
            return $this->getPlatform()->quote($blob->__toString());
        }

        return $this->getPlatform()->quote($blob);
    }

    /**
     * Returns a representation of a CLOB/LONGVARCHAR value suitable for use
     * in a SQL statement.
     *
     * @param  mixed  $clob
     * @return string
     */
    protected function getClobSql($clob)
    {
        // they took magic __toString() out of PHP5.0.0; this sucks
        if (is_object($clob)) {
            return $this->getPlatform()->quote($clob->__toString());
        }

        return $this->getPlatform()->quote($clob);
    }

    /**
     * Returns a representation of a date value suitable for use in a SQL
     * statement.
     *
     * @param  string $value
     * @return string
     */
    protected function getDateSql($value)
    {
        return sprintf("'%s'", date('Y-m-d', strtotime($value)));
    }

    /**
     * Returns a representation of a decimal value suitable for use in a SQL
     * statement.
     *
     * @param  double $value
     * @return float
     */
    protected function getDecimalSql($value)
    {
        return (float) $value;
    }

    /**
     * Returns a representation of a double value suitable for use in a SQL
     * statement.
     *
     * @param  double $value
     * @return double
     */
    protected function getDoubleSql($value)
    {
        return (double) $value;
    }

    /**
     * Returns a representation of a float value suitable for use in a SQL
     * statement.
     *
     * @param  float $value
     * @return float
     */
    protected function getFloatSql($value)
    {
        return (float) $value;
    }

    /**
     * Returns a representation of an integer value suitable for use in a SQL
     * statement.
     *
     * @param  integer $value
     * @return integer
     */
    protected function getIntSql($value)
    {
        return (int) $value;
    }

    /**
     * Returns a representation of a NULL value suitable for use in a SQL
     * statement.
     *
     * @return string
     */
    protected function getNullSql()
    {
        return 'NULL';
    }

    /**
     * Returns a representation of a string value suitable for use in a SQL
     * statement.
     *
     * @param  string $value
     * @return string
     */
    protected function getStringSql($value)
    {
        return $this->getPlatform()->quote($value);
    }

    /**
     * Returns a representation of a time value suitable for use in a SQL
     * statement.
     *
     * @param  string $value
     * @return string
     */
    protected function getTimeSql($paramIndex, $value)
    {
        return sprintf("'%s'", date('H:i:s', strtotime($value)));
    }

    /**
     * Returns a representation of a timestamp value suitable for use in a SQL
     * statement.
     *
     * @param  string $value
     * @return string
     */
    public function getTimestampSql($value)
    {
        return sprintf("'%s'", date('Y-m-d H:i:s', strtotime($value)));
    }
}
