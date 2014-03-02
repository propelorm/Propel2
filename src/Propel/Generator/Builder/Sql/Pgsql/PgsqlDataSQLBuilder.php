<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Sql\Pgsql;

use Propel\Generator\Builder\Sql\DataSQLBuilder;
use Propel\Generator\Builder\Util\DataRow;
use Propel\Generator\Model\IdMethod;

/**
 * PostgreSQL class for building data dump SQL.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class PgsqlDataSQLBuilder extends DataSQLBuilder
{
    /**
     * The largest serial value encountered this far.
     *
     * @var int
     */
    private $maxSeqVal;

    /**
     * The main method in this class, returns the SQL for INSERTing data into a
     * row.
     *
     * @param  DataRow $row
     * @return string
     */
    public function buildRowSql(DataRow $row)
    {
        $sql = parent::buildRowSql($row);

        $table = $this->getTable();

        if ($table->hasAutoIncrementPrimaryKey() && $table->getIdMethod() == IdMethod::NATIVE) {
            foreach ($row->getColumnValues() as $colValue) {
                if ($colValue->getColumn()->isAutoIncrement()) {
                    if ($colValue->getValue() > $this->maxSeqVal) {
                        $this->maxSeqVal = $colValue->getValue();
                    }
                }
            }
        }

        return $sql;
    }

    public function getTableEndSql()
    {
        $table = $this->getTable();
        $sql = '';
        if ($table->hasAutoIncrementPrimaryKey() && $table->getIdMethod() == IdMethod::NATIVE) {
            $seqname = $this->getPlatform()->getSequenceName($table);
            $sql .= "SELECT pg_catalog.setval('$seqname', ".((int) $this->maxSeqVal).");
";
        }

        return $sql;
    }

    /**
     * Returns the SQL value to insert for PostGres BOOLEAN column.
     *
     * @param  boolean $value
     * @return string
     */
    protected function getBooleanSql($value)
    {
        if (in_array($value, array('f', 'false', '0'))) {
            $value = false;
        }

        return $value ? "'t'" : "'f'";
    }

    /**
     * Returns the SQL code for inserting a Blob value.
     *
     * @param  mixed  $blob
     * @return string
     */
    protected function getBlobSql($blob)
    {
       if (is_resource($blob)) {
            return fopen($blob, 'rb');
        }

        return (string) $blob;
    }
}
