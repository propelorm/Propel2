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

    function __construct(Session $session, EntityMap $entityMap)
    {
        $this->session = $session;
        $this->entityMap = $entityMap;
        $this->repository = $session->getConfiguration()->getRepository($entityMap->getFullClassName());
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    public function persist($entities)
    {
        $inserts = [];
        $updates = [];
        foreach ($entities as $entity) {
            if ($this->repository->isNew($entity)) {
                $inserts[] = $entity;
            } else {
                $updates[] = $entity;
            }
        }

        $event = new SaveEvent($this->getSession(), $this->entityMap, $updates, $inserts);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_SAVE, $event);

//        echo sprintf(
//            "%s: %d inserts, %d updates\n",
//            $this->entityMap->getClassName(),
//            count($inserts),
//            count($updates)
//        );

        $this->doInsert($inserts);
        $this->doUpdates($inserts);

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
        $stmt->execute([$this->entityMap->getName()]);

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

//        var_dump($sql);

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

    protected function doUpdates($updates)
    {
        $event = new UpdateEvent($this->getSession(), $this->entityMap, $updates);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_UPDATE, $event);

        //todo, use CASE WHEN THEN to improve performance

        $sqlStart = 'UPDATE ' . $this->entityMap->getTableName();
        $where = [];
        foreach ($this->entityMap->getPrimaryKeys() as $pk) {
            if ($pk->isAutoIncrement()) {
                continue;
            }

            $where[] = $pk->getColumnName() . ' = ?';
        }

        $where = ' WHERE ' . implode(' AND ', $where);
        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        foreach($updates as $entity) {
            $changeSet = $this->entityMap->getChangeSet($entity);
            if ($changeSet) {
                $params = [];
                $changes = $changeSet->getMap();
                $sets = [];
                foreach ($changes as $columnName => $value) {
                    $sets[] = $columnName . ' = ?';
                    $params[] = $value;
                }

                foreach ($this->entityMap->getPrimaryKeys() as $pk) {
                    if ($pk->isAutoIncrement()) {
                        continue;
                    }

                    $fieldName = $pk->getName();
                    $params[] = $this->repository->getOriginalValues($entity)[$fieldName];
                }

                $query = $sqlStart . ' SET ' . implode(', ', $sets).$where;
                $stmt = $connection->prepare($query);
                $stmt->execute($params);
            }
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::UPDATE, $event);
    }
}