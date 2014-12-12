<?php

namespace Propel\Runtime\Persister;


use Propel\Runtime\Configuration;
use Propel\Runtime\EntityProxyInterface;
use Propel\Runtime\Event\InsertEvent;
use Propel\Runtime\Event\SaveEvent;
use Propel\Runtime\Event\UpdateEvent;
use Propel\Runtime\Events;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Session\Session;

class SqlPersister
{

    /**
     * @var array
     */
    protected $inserts;

    /**
     * @var array
     */
    protected $updates;

    /**
     * @var EntityMap
     */
    protected $entityMap;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var object
     */
    protected $autoIncrementValues;

    public function __construct(Session $session, EntityMap $entityMap)
    {
        $this->session = $session;
        $this->entityMap = $entityMap;
        $this->repository = $session->getConfiguration()->getRepository($entityMap->getFullClassName());
    }

    /**
     * @return \Propel\Runtime\Repository\Repository
     */
    protected function getRepository()
    {
        return $this->session->getConfiguration()->getRepository($this->entityMap->getFullClassName());
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    public function remove($entities)
    {
        $whereClauses = [];
        $params = [];
        foreach ($entities as $entity) {
            $whereClauses = '(' . $this->entityMap->buildSqlRemoveWhereClause($entity, $params) . ')';
        }

        $query = sprintf('DELETE %s WHERE ', $this->entityMap->getTableName(), implode(' OR ', $whereClauses));

        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        $stmt = $connection->prepare($query);
        try {
            $stmt->execute($params);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Could not execute query %s', $query), 0, $e);
        }
    }
    public function persist($entities)
    {
        $changeSets = $inserts = $updates = [];
        foreach ($entities as $entity) {
            if ($this->repository->isNew($entity)) {
                $inserts[] = $entity;
            } else {
                if ($changeSet = $this->getRepository()->buildChangeSet($entity)) {
                    $updates[] = $entity;
                    $changeSets[spl_object_hash($entity)] = $changeSet;
                }
            }
        }

        $event = new SaveEvent($this->getSession(), $this->entityMap, $inserts, $updates);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_SAVE, $event);
//
//        echo sprintf(
//            "%s: %d inserts, %d updates\n",
//            $this->entityMap->getFullClassName(),
//            count($inserts),
//            count($updates)
//        );

        $this->doInsert($inserts);
        $this->doUpdates($updates, $changeSets);

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::SAVE, $event);
    }

    /**
     * @param string $field
     *
     * @return integer
     */
    protected function getAutoIncrementStartValue($field)
    {
    }

    protected function readAutoIncrement($connection)
    {
        $sql = <<<EOF
    SELECT `AUTO_INCREMENT`
    FROM  INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND   TABLE_NAME   = ?
EOF;

        $stmt = $connection->prepare($sql);
        $stmt->execute([$this->entityMap->getTableName()]);

        return $stmt->fetchColumn();
    }

    protected function prepareAutoIncrement($connection)
    {
        $fieldNames = $this->entityMap->getAutoIncrementFieldNames();
        $object = [];

        if (1 < count($fieldNames)) {
            throw new RuntimeException(
                sprintf('Entity `%s` has more than one autoIncrement field. This is currently not supported'),
                $this->entityMap->getClassName()
            );
        }

        $firstFieldName = $fieldNames[0];
        $object[$firstFieldName] = $this->readAutoIncrement($connection);

        $this->autoIncrementValues = (object)$object;
    }

    protected function doInsert($inserts)
    {
        if (!$inserts) {
            return;
        }

        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        $event = new InsertEvent($this->getSession(), $this->entityMap, $inserts);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_INSERT, $event);

        if ($this->entityMap->hasAutoIncrement()) {
            $this->prepareAutoIncrement($connection);
        }

        $fieldObjects = $this->entityMap->getFields();
        $fields = [];
        foreach ($fieldObjects as $field) {
            if ($field->isAutoIncrement()) {
                continue;
            }
            $fields[] = $field->getColumnName();
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES ', $this->entityMap->getTableName(), implode(', ', $fields));

        $params = [];
        $firstRound = true;
        foreach ($inserts as $entity) {
            if ($firstRound) {
                $firstRound = false;
            } else {
                $sql .= ', ';
            }

            $sql .= $this->entityMap->buildSqlBulkInsertPart($entity, $params);
        }

        $paramsReplace = $params;
        $readable = preg_replace_callback('/\?/', function() use (&$paramsReplace) {
            return var_export(array_shift($paramsReplace), true);
        }, $sql);
        echo "sql-insert: $readable\n";

        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute($params);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf(
                'Can not execute INSERT query for entity %s: %s [%s]',
                $this->entityMap->getFullClassName(),
                $sql,
                implode(',', $params)
            ), 0, $e);
        }

        if ($this->entityMap->hasAutoIncrement()) {
            foreach ($inserts as $entity) {
                $this->entityMap->populateAutoIncrementFields($entity, $this->autoIncrementValues);
            }
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::INSERT, $event);
    }

    protected function doUpdates($updates, $changeSets)
    {
        $event = new UpdateEvent($this->getSession(), $this->entityMap, $updates);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_UPDATE, $event);

        //todo, use CASE WHEN THEN to improve performance

        $sqlStart = 'UPDATE ' . $this->entityMap->getTableName();
        $where = [];
        foreach ($this->entityMap->getPrimaryKeys() as $pk) {
            $where[] = $pk->getColumnName() . ' = ?';
        }

        $where = ' WHERE ' . implode(' AND ', $where);
        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        foreach($updates as $entity) {
            $changeSet = $changeSets[spl_object_hash($entity)]; //$this->getRepository()->buildChangeSet($entity);
            if ($changeSet) {
                $params = [];
                $sets = [];
                foreach ($changeSet as $fieldName => $value) {
                    $columnName = $this->entityMap->getField($fieldName)->getColumnName();
                    $sets[] = $columnName . ' = ?';
                    $params[] = $value;
                }

                $originValues = $this->getRepository()->getLastKnownValues($entity);

                foreach ($this->entityMap->getPrimaryKeys() as $pk) {
                    $fieldName = $pk->getName();
                    $params[] = $originValues[$fieldName];
                }

                $query = $sqlStart . ' SET ' . implode(', ', $sets).$where;

                $paramsReplace = $params;
                $readable = preg_replace_callback('/\?/', function() use (&$paramsReplace) {
                    return var_export(array_shift($paramsReplace), true);
                }, $query);
                echo "sql-update: $readable\n";

                $stmt = $connection->prepare($query);
                try {
                    $stmt->execute($params);
                } catch (\Exception $e) {
                    throw new RuntimeException(sprintf('Could not execute query %s', $query), 0, $e);
                }
            }
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::UPDATE, $event);
    }
}