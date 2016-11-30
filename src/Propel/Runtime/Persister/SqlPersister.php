<?php

namespace Propel\Runtime\Persister;


use Propel\Generator\Model\NamingTool;
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
     * @var Session
     */
    protected $session;

    /**
     * Map of inserted table names, to track bi-direciton many-to-many relations so the pivot table
     * is not deleted twice.
     *
     * @var boolean[]
     */
    protected $insertedManyToManyRelation = [];

    /**
     * @var object
     */
    protected $autoIncrementValues;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return Configuration
     */
    protected function getConfiguration()
    {
        return $this->getSession()->getConfiguration();
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
    public function remove(EntityMap $entityMap, $entities)
    {
        if (!$entities) {
            return false;
        }

        $event = new DeleteEvent($this->getSession(), $entityMap, $entities);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_DELETE, $event);

        $whereClauses = [];
        $params = [];
        foreach ($entities as $entity) {
            $whereClauses[] = '(' . $entityMap->buildSqlPrimaryCondition($entity, $params) . ')';
        }

        $query = sprintf('DELETE FROM %s WHERE %s', $entityMap->getFQTableName(), implode(' OR ', $whereClauses));

        $connection = $this->getSession()->getConfiguration()->getConnectionManager($entityMap->getDatabaseName());
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
    public function commit(EntityMap $entityMap, $entities)
    {
        $event = new SaveEvent($this->getSession(), $entityMap, $entities);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_SAVE, $event);

        $inserts = $updates = [];
        foreach ($entities as $entity) {
            if ($this->getSession()->isNew($entity)) {
                $inserts[] = $entity;
            } else {
                if ($this->getSession()->hasKnownValues($entity) && $changeSet = $entityMap->buildChangeSet($entity)) {
                    //only add to $updates if really changes are detected
                    $updates[] = $entity;
                }
            }
        }

        $this->getConfiguration()->debug(sprintf(
            ' COMMIT PERSISTER with %d entities of %s', count($entities), $entityMap->getFullClassName()
        ), Configuration::LOG_GREEN);

        if (!$inserts && !$updates) {
            $this->getConfiguration()->debug(sprintf('   No changes detected'), Configuration::LOG_GREEN);
            return;
        }

        $name = $entityMap->getFullClassName();
        foreach ($inserts as $entity) {
            $name .= ', #' . substr(md5(spl_object_hash($entity)), 0, 9);
        }
        $this->getConfiguration()->debug(sprintf('   doInsert(%d) for %s', count($inserts), $name), Configuration::LOG_GREEN);

        $name = $entityMap->getFullClassName();
        foreach ($updates as $entity) {
            $name .= ', #' . substr(md5(spl_object_hash($entity)), 0, 9);
        }
        $this->getConfiguration()->debug(sprintf('   doUpdates(%d) for %s', count($updates), $name), Configuration::LOG_GREEN);

        if ($inserts) {
            $this->doInsert($entityMap, $inserts);
        }

        if ($updates) {
            $this->doUpdates($entityMap, $updates);
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::SAVE, $event);
    }

    /**
     * @param ConnectionInterface $connection
     */
    protected function prepareAutoIncrement(EntityMap $entityMap, ConnectionInterface $connection, $count)
    {
        $fieldNames = $entityMap->getAutoIncrementFieldNames();
        $object = [];

        if (1 < count($fieldNames)) {
            throw new RuntimeException(
                sprintf('Entity `%s` has more than one autoIncrement field. This is currently not supported'),
                $entityMap->getFullClassName()
            );
        }

        $firstFieldName = $fieldNames[0];

        //mysql returns the lastInsertId of the first bulk-insert part
        $lastInsertId = (int) $connection->lastInsertId($firstFieldName);

        $this->getConfiguration()->debug("prepareAutoIncrement lastInsertId=$lastInsertId, for $count items");
        $object[$firstFieldName] = $lastInsertId;

        $this->getConfiguration()->debug('prepareAutoIncrement for ' . $entityMap->getFullClassName(). ': ' . json_encode($object));
        $this->autoIncrementValues = (object)$object;
    }

    /**
     * @param array $inserts
     *
     * @throws PersisterException
     */
    protected function doInsert(EntityMap $entityMap, array $inserts)
    {
        if (!$inserts) {
            return;
        }

        $connection = $this->getSession()->getConfiguration()->getConnectionManager($entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        $event = new InsertEvent($this->getSession(), $entityMap, $inserts);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_INSERT, $event);

        $fieldObjects = $entityMap->getFields();
        $fields = [];
        foreach ($fieldObjects as $field) {
            if (!$entityMap->isAllowPkInsert() && $field->isAutoIncrement()) {
                continue;
            }
            if ($field->isImplementationDetail()) {
                continue;
            }
            $fields[$field->getColumnName()] = $field->getColumnName();
        }

        foreach ($entityMap->getRelations() as $relation) {
            if ($relation->isOutgoingRelation()) {
                foreach ($relation->getLocalFields() as $field) {
                    $fields[$field->getColumnName()] = $field->getColumnName();
                }
            }
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES ', $entityMap->getFQTableName(), implode(', ', $fields));

        $params = [];
        $firstRound = true;
        foreach ($inserts as $entity) {
            if ($firstRound) {
                $firstRound = false;
            } else {
                $sql .= ', ';
            }

            $this->getSession()->setPersisted(spl_object_hash($entity));
            $sql .= $entityMap->buildSqlBulkInsertPart($entity, $params);
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

            if ($entityMap->hasAutoIncrement()) {
                $this->prepareAutoIncrement($entityMap, $connection, count($inserts));
            }
        } catch (\Exception $e) {
            if ($e instanceof \PDOException) {
                if ($normalizedException = $this->normalizePdoException($entityMap, $e)) {
                    throw $normalizedException;
                }
            }

            $paramsReplace = array_map(function($v) {
                return json_encode($v);
            }, $paramsReplace);

            throw new RuntimeException(sprintf(
                'Can not execute INSERT query for entity %s: %s [%s]',
                $entityMap->getFullClassName(),
                $sql,
                implode(',', $paramsReplace)
            ), 0, $e);
        }

        if ($entityMap->hasAutoIncrement()) {
            foreach ($inserts as $entity) {
                $this->getConfiguration()->debug(sprintf('set auto-increment value %s for %s', json_encode($this->autoIncrementValues), get_class($entity)));
                $entityMap->populateAutoIncrementFields($entity, $this->autoIncrementValues);
            }
        }

        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::INSERT, $event);

        if ($entityMap->isReloadOnInsert()) {
            foreach ($inserts as $entity) {
                $entityMap->load($entity);
            }
        }

        foreach ($entityMap->getRelations() as $relation) {
            if ($relation->isManyToMany()) {
                $this->addCrossRelations($entityMap, $inserts, $relation);
            }
        }
    }

    public function sessionCommitEnd()
    {
        $this->insertedManyToManyRelation = [];
    }

    /**
     * Ads cross relation entities to the database when necessary for many-to-many relations.
     *
     * @param array $inserts
     * @param RelationMap $relation
     */
    protected function addCrossRelations(EntityMap $entityMap, $inserts, RelationMap $relation)
    {
        //todo, make sure the symmetrical relation hasn't been saved yet.
        //if so, we return immediately since we would save the cross entities twice.

        $reader = $entityMap->getPropReader();
        $isset = $entityMap->getPropIsset();

        foreach ($inserts as $entity) {

            $id = substr(md5(spl_object_hash($entity)), 0, 9);
            $debugName = "{$entityMap->getFullClassName()} #$id {$relation->getName()}";

            if (!$isset($entity, $relation->getPluralName())) {
                //we don't update relations when they haven't been loaded.
                $this->getConfiguration()->debug("many-to-many $debugName: no update, since not loaded.");
                continue;
            }

            $foreignItems = $reader($entity, $relation->getPluralName());

            //delete first
            $query = $relation->getMiddleEntity()->createQuery();
            foreach ($relation->getFieldMappingIncoming() as $middleTableField => $myId) {
                $query->filterBy($middleTableField, $reader($entity, $myId));
                $debugName .= " $myId=" . $reader($entity, $myId);
            }
            $query->delete();

            if (null !== $foreignItems && count($foreignItems)) {

                $this->getConfiguration()->debug("many-to-many $debugName: update with " . count($foreignItems) . " foreign items.");

                if ($relation->isImplementationDetail()) {
                    //do manual SQL insert. It's a implementationDetail when the crossEntity hasn't been created
                    //in the future it's something like <relation target="user' many>
                    throw new RuntimeException('Relation as implementation detail not implemented yet.');
                } else {
                    //use Propel entities
                    $writer = $relation->getMiddleEntity()->getPropWriter();

                    foreach ($foreignItems as $foreignItem) {

                        $id = [spl_object_hash($entity)];

                        if ($relation->isPolymorphic()) {
                            throw new RuntimeException('Polymorphic not implemented.');
                        } else {
                            $id[] = spl_object_hash($foreignItem);
                        }

                        sort($id);
                        $id = implode("\0", $id);
                        if (isset($this->insertedManyToManyRelation[$id])) {
                            continue;
                        }

                        $object = $this->getConfiguration()->getEntityMap($relation->getMiddleEntity()->getFullClassName())->createObject();

                        $writer($object, $relation->getFieldMappingIncomingName(), $entity);
                        foreach ($relation->getFieldMappingOutgoing() as $relationName => $mapping) {
                            //todo, when we have multiple outgoing relations, what then?
                            //a: then $foreignItem needs to be an array and CombinedObjectColleciton is necessary.
                            $writer($object, $relationName, $foreignItem);
                        }

                        $foreignId = NamingTool::shortEntityId($foreignItem);
                        $this->getConfiguration()->debug("many-to-many $debugName: created relation entry to #$foreignId via #" . NamingTool::shortEntityId($object));
                        $this->insertedManyToManyRelation[$id] = true;
                        $this->getSession()->persist($object, true);
                    }
                }
            } else {
                $this->getConfiguration()->debug("many-to-many $debugName: no update, since no items.");
            }

        }
    }

    protected function deleteAll(EntityMap $entityMap)
    {
        $query = $entityMap->createQuery();
        $query->doDeleteAll();
    }

    /**
     * @param \PDOException $PDOException
     *
     * @return PersisterException|null
     */
    protected function normalizePdoException(EntityMap $entityMap, \PDOException $PDOException)
    {
    }


    protected function doUpdates(EntityMap $entityMap, array $updates)
    {
        $event = new UpdateEvent($this->getSession(), $entityMap, $updates);
        $this->getSession()->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_UPDATE, $event);

        //todo, use CASE WHEN THEN to improve performance
        $sqlStart = 'UPDATE ' . $entityMap->getFQTableName();
        $where = [];
        foreach ($entityMap->getPrimaryKeys() as $pk) {
            $where[] = $pk->getColumnName() . ' = ?';
        }

        $where = ' WHERE ' . implode(' AND ', $where);
        $connection = $this->getSession()->getConfiguration()->getConnectionManager($entityMap->getDatabaseName());
        $connection = $connection->getWriteConnection();

        $updateCrossRelations = [];

        foreach($updates as $entity) {
            //regenerate changeSet since PRE_UPDATE/PRE_SAVE could have changed entities
            $changeSet = $entityMap->buildChangeSet($entity);
            if ($changeSet) {
                $params = [];
                $sets = [];
                foreach ($changeSet as $fieldName => $value) {
                    if ($entityMap->hasRelation($fieldName)) {
                        if ($entityMap->getRelation($fieldName)->isManyToMany()) {
                            $updateCrossRelations[$fieldName][] = $entity;
                        }
                        continue;
                    }

                    $columnName = $entityMap->getField($fieldName)->getColumnName();
                    $sets[] = $columnName . ' = ?';
                    $params[] = $value;
                }

                if (!$sets) {
                    continue;
                }

                $originValues = $entityMap->getLastKnownValues($entity);

                foreach ($entityMap->getPrimaryKeys() as $pk) {
                    $fieldName = $pk->getName();
                    $params[] = $entityMap->snapshotToProperty($originValues[$fieldName], $fieldName);
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


        if ($entityMap->isReloadOnInsert()) {
            foreach ($updates as $entity) {
                $entityMap->load($entity);
            }
        }

        if ($updateCrossRelations) {
            $this->getConfiguration()->debug(sprintf('many-to-many update %d entities', count($updateCrossRelations)));
        }

        foreach ($updateCrossRelations as $relationName => $entities) {
            $this->addCrossRelations($entityMap, $entities, $entityMap->getRelation($relationName));
        }
    }
}