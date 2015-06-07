<?php

namespace Propel\Runtime\Persister\SQL;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Persister\SqlPersister;

class SQLitePersister extends SqlPersister
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return string
     */
    protected function readAutoIncrement(ConnectionInterface $connection)
    {
        $sql = <<<EOF
    SELECT "%s"
    FROM  "%s"
    ORDER BY "%s" DESC
    LIMIT 1
EOF;

        $autoIncrementField = current($this->entityMap->getPrimaryKeys());

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