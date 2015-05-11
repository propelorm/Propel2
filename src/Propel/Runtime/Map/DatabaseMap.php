<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\Map\Exception\EntityNotFoundException;
use Propel\Runtime\Propel;

/**
 * DatabaseMap is used to model a database.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 */
class DatabaseMap
{
    /**
     * Name of the database.
     *
     * @var string
     */
    protected $name;

    /**
     * Entities in the database, using full class name as key.
     *
     * @var EntityMap[]
     */
    protected $entities = array();
    protected $entitiesByName = array();

//    /**
//     * @var string[]
//     */
//    protected $registeredEntities = [];
//    protected $registeredEntitiesByName = [];

    /**
     * Constructor.
     *
     * @param string $name Name of the database.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this database.
     *
     * @return string The name of the database.
     */
    public function getName()
    {
        return $this->name;
    }

//    /**
//     * Add a new entity to the database by name.
//     *
//     * @param  string $entityName The name of the entity.
//     *
//     * @return \Propel\Runtime\Map\EntityMap The newly created EntityMap.
//     */
//    public function addEntity($entityName)
//    {
//        $this->entities[$entityName] = new EntityMap($entityName, $this);
//
//        return $this->entities[$entityName];
//    }

    /**
     * Add a new entity object to the database.
     *
     * @param EntityMap $entity The entity to add
     */
    public function addEntityMap(EntityMap $entity)
    {
        $this->entities[$entity->getFullClassName()] = $entity;
        $this->entitiesByName[$entity->getName()] = $entity;
    }

    /**
     * Add a new entity to the database, using the entitymap class name.
     *
     * @param  string $entityMapClass The name of the entity map to add
     *
     * @return \Propel\Runtime\Map\EntityMap The EntityMap object
     */
    public function addEntityFromMapClass($entityMapClass)
    {
        /** @var EntityMap $entity */
        $entity = new $entityMapClass();
        if (!$this->hasEntity($entity->getName())) {
            $this->addEntityObject($entity);

            return $entity;
        }

        return $this->getEntity($entity->getName());
    }

    /**
     * Does this database contain this specific entity?
     *
     * @param  string $name The String representation of the entity.
     *
     * @return boolean True if the database contains the entity.
     */
    public function hasEntity($name)
    {
        if (strpos($name, '.') > 0) {
            $name = substr($name, 0, strpos($name, '.'));
        }

        return isset($this->entities[$name]);
    }

    /**
     * Get a EntityMap for the entity by name.
     *
     * @param  string $name Name of the entity.
     *
     * @return EntityMap
     * @throws \Propel\Runtime\Map\Exception\EntityNotFoundException If the entity is undefined
     */
    public function getEntity($name)
    {
        if (!isset($this->entities[$name])) {
            throw new EntityNotFoundException(
                sprintf(
                    'Cannot fetch EntityMap for undefined entity `%s` [%s]',
                    $name,
                    implode(',', array_keys($this->entities))
                )
            );
        }

        return $this->entities[$name];
    }

    /**
     * Get a EntityMap[] of all of the entities in the database.
     *
     * @return EntityMap[]
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Get a FieldMap for the field by name.
     * Name must be fully qualified, e.g. book.AUTHOR_ID
     *
     * @param  string $qualifiedFieldName Name of the field.
     *
     * @return \Propel\Runtime\Map\FieldMap A EntityMap
     * @throws EntityNotFoundException        If the entity is undefined, or if the entity is undefined
     */
    public function getField($qualifiedFieldName)
    {
        list($entityName, $fieldName) = explode('.', $qualifiedFieldName);

        return $this->getEntity($entityName)->getField($fieldName, false);
    }

//    /**
//     * @param string $phpName
//     *
//     * @return EntityMap
//     */
//    public function getEntityByPhpName($phpName)
//    {
//        if ('\\' !== $phpName[0]) {
//            $phpName = '\\' . $phpName;
//        }
//        if (isset($this->entitiesByPhpName[$phpName])) {
//            return $this->entitiesByPhpName[$phpName];
//        }
//
//        if (class_exists($tmClass = $phpName . 'EntityMap')) {
//            $this->addEntityFromMapClass($tmClass);
//
//            return $this->entitiesByPhpName[$phpName];
//        }
//
//        if (class_exists($tmClass = substr_replace($phpName, '\\Map\\', strrpos($phpName, '\\'), 1) . 'EntityMap')
//            || class_exists($tmClass = '\\Map\\' . $phpName . 'EntityMap')
//        ) {
//            $this->addEntityFromMapClass($tmClass);
//
//            if (isset($this->entitiesByPhpName[$phpName])) {
//                return $this->entitiesByPhpName[$phpName];
//            }
//
//            if (isset($this->entitiesByPhpName[$phpName])) {
//                return $this->entitiesByPhpName[$phpName];
//            }
//        }
//
//        throw new EntityNotFoundException(
//            sprintf('Cannot fetch EntityMap for undefined entity phpName: %s.', $phpName)
//        );
//    }

    /**
     * Convenience method to get the AdapterInterface registered with Propel for this database.
     *
     * @see Propel::getServiceContainer()->getAdapter(string) .
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAbstractAdapter()
    {
        return Propel::getServiceContainer()->getAdapter($this->name);
    }
}
