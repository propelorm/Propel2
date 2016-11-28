<?php

namespace Propel\Runtime\Persister\SQL;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Map\FieldMap;
use Propel\Runtime\Persister\Exception\UniqueConstraintException;
use Propel\Runtime\Persister\SqlPersister;

class SQLitePersister extends SqlPersister
{
    /**
     * Return the next value of an autoincrement field.
     *
     * @see http://www.sqlite.org/fileformat2.html#seqtab
     *
     * @param ConnectionInterface $connection
     *
     * @return string
     */
    protected function readAutoIncrement(EntityMap $entityMap, ConnectionInterface $connection)
    {

        $autoIncrementField = current($entityMap->getPrimaryKeys());
        $tableName = $autoIncrementField->getEntity()->getFQTableName();

        $stmt = $connection->prepare("SELECT seq FROM sqlite_sequence WHERE name = ?");
        $stmt->bindValue(1, $tableName, \PDO::PARAM_STR);
        $stmt->execute();
        $value = (integer) $stmt->fetchColumn();

        if ($value > 0) {
            return $value + 1;
        }

        return $this->readAutoincrementWithoutSequenceTable($autoIncrementField, $connection);
    }

    protected function normalizePdoException(EntityMap $entityMap, \PDOException $PDOException)
    {
        $message = $PDOException->getMessage();

        if (false !== strpos($message, 'Integrity constraint violation:')) {
            if(preg_match('/UNIQUE constraint failed: ([^\.]+)\.([^\.]+)/', $message, $matches)) {
                return UniqueConstraintException::createForField($entityMap, $matches[2]);
            }
        }

        return parent::normalizePdoException($PDOException);
    }

    /**
     * Return the next value of an autoincrement field, if the internal sqlite_sequence table entry,
     * relative to the  field,  doesn't exists.
     *
     * @param FieldMap            $autoIncrementField
     * @param ConnectionInterface $connection
     *
     * @return int
     */
    protected function readAutoincrementWithoutSequenceTable(FieldMap $autoIncrementField, ConnectionInterface $connection)
    {
          $sql = <<<EOF
    SELECT "%s"
    FROM  "%s"
    ORDER BY "%s" DESC
    LIMIT 1
EOF;

        $columnName = $autoIncrementField->getColumnName();
        $tableName = $autoIncrementField->getEntity()->getFQTableName();
        $sql = sprintf($sql, $columnName, $tableName, $columnName);

        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $value = (integer) $stmt->fetchColumn();

        if ($value > 0) {
            return $value + 1;
        }

        return 1;
    }
}
