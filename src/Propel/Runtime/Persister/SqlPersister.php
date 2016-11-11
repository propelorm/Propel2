<?php

namespace Propel\Runtime\Persister;


use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\EntityProxyInterface;
use Propel\Runtime\Event\DeleteEvent;
use Propel\Runtime\Event\InsertEvent;
use Propel\Runtime\Event\SaveEvent;
use Propel\Runtime\Event\UpdateEvent;
use Propel\Runtime\Events;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Persister\Exception\PersisterException;
use Propel\Runtime\Persister\Exception\UniqueConstraintException;
use Propel\Runtime\Session\Session;

class SqlPersister implements PersisterInterface
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
     * @return Configuration
     */
    protected function getConfiguration()
    {
        return $this->getSession()->getConfiguration();
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

    /**
     * @param object[] $entities
     */
    public function remove($entities)
    {
        if (!$entities) {
            return false;
        }

        $event = new DeleteEvent($this->getSession(), $this->entityMap, $entities);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_DELETE, $event);

        $whereClauses = [];
        $params = [];
        foreach ($entities as $entity) {
            $whereClauses[] = '(' . $this->entityMap->buildSqlPrimaryCondition($entity, $params) . ')';
        }

        $query = sprintf('DELETE FROM %s WHERE %s', $this->getFQTableName(), implode(' OR ', $whereClauses));

        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        $stmt = $connection->prepare($query);
        try {
            $this->getConfiguration()->debug("delete-sql: $query (".implode(',', $params).")");
            $stmt->execute($params);

            $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::DELETE, $event);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Could not execute query %s', $query), 0, $e);
        }

    }

    /**
     * @param object[] $entities
     */
    public function persist($entities)
    {
        $inserts = $updates = [];
        foreach ($entities as $entity) {
            if ($this->getSession()->isNew($entity)) {
                $inserts[] = $entity;
            } else {
                if ($this->getSession()->hasKnownValues($entity) && $changeSet = $this->getRepository()->buildChangeSet($entity)) {
                    //only add to $updates if really changes are detected
                    $updates[] = $entity;
                }
            }
        }

        if (!$inserts && !$updates) {
            $this->getConfiguration()->debug(sprintf('No changes detected'));
            return;
        }

        $event = new SaveEvent($this->getSession(), $this->entityMap, $inserts, $updates);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_SAVE, $event);

        $this->getConfiguration()->debug(sprintf('doInsert(%d) for %s', count($inserts), $this->entityMap->getFullClassName()));
        $this->getConfiguration()->debug(sprintf('doUpdates(%d) for %s', count($updates), $this->entityMap->getFullClassName()));

        if ($inserts) {
            $this->doInsert($inserts);
        }
        if ($updates) {
            $this->doUpdates($updates);
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::SAVE, $event);
    }

    /**
     * @param ConnectionInterface $connection
     */
    protected function prepareAutoIncrement(ConnectionInterface $connection, $count)
    {
        $fieldNames = $this->entityMap->getAutoIncrementFieldNames();
        $object = [];

        if (1 < count($fieldNames)) {
            throw new RuntimeException(
                sprintf('Entity `%s` has more than one autoIncrement field. This is currently not supported'),
                $this->entityMap->getFullClassName()
            );
        }

        $firstFieldName = $fieldNames[0];

        //mysql returns the lastInsertId of the first bulk-insert part
        $lastInsertId = (int) $connection->lastInsertId($firstFieldName);

        $this->getConfiguration()->debug("prepareAutoIncrement lastInsertId=$lastInsertId, for $count items");
        $object[$firstFieldName] = $lastInsertId;

        $this->getConfiguration()->debug('prepareAutoIncrement for ' . $this->entityMap->getFullClassName(). ': ' . json_encode($object));
        $this->autoIncrementValues = (object)$object;
    }

    /**
     * @param array $inserts
     *
     * @throws PersisterException
     */
    protected function doInsert(array $inserts)
    {
        if (!$inserts) {
            return;
        }

        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        $event = new InsertEvent($this->getSession(), $this->entityMap, $inserts);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_INSERT, $event);


        $fieldObjects = $this->entityMap->getFields();
        $fields = [];
        foreach ($fieldObjects as $field) {
            if (!$this->entityMap->isAllowPkInsert() && $field->isAutoIncrement()) {
                continue;
            }
            if ($field->isImplementationDetail()) {
                continue;
            }
            $fields[$field->getColumnName()] = $field->getColumnName();
        }

        foreach ($this->entityMap->getRelations() as $relation) {
            if ($relation->isOutgoingRelation()) {
                foreach ($relation->getLocalFields() as $field) {
                    $fields[$field->getColumnName()] = $field->getColumnName();
                }
            }
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES ', $this->getFQTableName(), implode(', ', $fields));

        $params = [];
        $firstRound = true;
        foreach ($inserts as $entity) {
            if ($firstRound) {
                $firstRound = false;
            } else {
                $sql .= ', ';
            }

            $this->getSession()->setPersisted(spl_object_hash($entity));
            $sql .= $this->entityMap->buildSqlBulkInsertPart($entity, $params);
        }

        $paramsReplace = $params;

        $paramsReplaceReadable = $paramsReplace;
        $readable = preg_replace_callback('/\?/', function() use (&$paramsReplaceReadable) {
            $value = array_shift($paramsReplaceReadable);
            if (is_string($value) && strlen($value) > 64) {
                $value = substr($value, 0, 64) . '...';
            }
            return var_export($value, true);
        }, $sql);
        $this->getConfiguration()->debug("sql-insert: $readable");

        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute($params);

            if ($this->entityMap->hasAutoIncrement()) {
                $this->prepareAutoIncrement($connection, count($inserts));
            }
        } catch (\Exception $e) {
            if ($e instanceof \PDOException) {
                if ($normalizedException = $this->normalizePdoException($e)) {
                    throw $normalizedException;
                }
            }

            $paramsReplace = array_map(function($v) {
                return json_encode($v);
            }, $paramsReplace);

            throw new RuntimeException(sprintf(
                'Can not execute INSERT query for entity %s: %s [%s]',
                $this->entityMap->getFullClassName(),
                $sql,
                implode(',', $paramsReplace)
            ), 0, $e);
        }

        if ($this->entityMap->hasAutoIncrement()) {
            foreach ($inserts as $entity) {
                $this->getConfiguration()->debug(sprintf('set auto-increment value %s for %s', json_encode($this->autoIncrementValues), get_class($entity)));
                $this->entityMap->populateAutoIncrementFields($entity, $this->autoIncrementValues);
            }
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::INSERT, $event);
    }

    /**
     * @param \PDOException $PDOException
     *
     * @return PersisterException|null
     */
    protected function normalizePdoException(\PDOException $PDOException)
    {
    }

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }

    protected function doUpdates(array $updates)
    {
        $event = new UpdateEvent($this->getSession(), $this->entityMap, $updates);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_UPDATE, $event);

        //todo, use CASE WHEN THEN to improve performance
        $sqlStart = 'UPDATE ' . $this->getFQTableName();
        $where = [];
        foreach ($this->entityMap->getPrimaryKeys() as $pk) {
            $where[] = $pk->getColumnName() . ' = ?';
        }

        $where = ' WHERE ' . implode(' AND ', $where);
        $connection = $this->getSession()->getConfiguration()->getConnectionManager($this->entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        foreach($updates as $entity) {
            //regenerate changeSet since PRE_UPDATE/PRE_SAVE could have changed entities
            $changeSet = $this->getRepository()->buildChangeSet($entity);
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
                    $params[] = $this->entityMap->snapshotToProperty($originValues[$fieldName], $fieldName);
                }

                $query = $sqlStart . ' SET ' . implode(', ', $sets).$where;

                $paramsReplace = $params;
                $readable = preg_replace_callback('/\?/', function() use (&$paramsReplace) {
                    $value = array_shift($paramsReplace);
                    if (is_string($value) && strlen($value) > 64) {
                        $value = substr($value, 0, 64) . '...';
                    }
                    return var_export($value, true);
                }, $query);
                $this->getConfiguration()->debug("sql-update: $readable");

                $stmt = $connection->prepare($query);
                try {
                    $stmt->execute($params);
                } catch (\Exception $e) {
                    throw new RuntimeException(
                        sprintf(
                            'Could not execute query %s',
                            $readable
                        ), 0, $e
                    );
                }
            }
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::UPDATE, $event);
    }

    /**
     * @return string
     */
    public function getFQTableName()
    {
        return $this->entityMap->getFQTableName();
    }
}