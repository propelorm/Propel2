<?php
//
//namespace Propel\Runtime;
//
//use Propel\Runtime\Connection\ConnectionManagerInterface;
//use Propel\Runtime\Exception\RuntimeException;
//use Propel\Runtime\Map\DatabaseMap;
//use Propel\Runtime\Map\EntityMap;
//use Propel\Runtime\Repository\Repository;
//use Propel\Runtime\UnitOfWork;
//use Symfony\Component\EventDispatcher\EventDispatcherInterface;
//
//class EntityManager
//{
//    /**
//     * @var ConnectionManagerInterface
//     */
//    protected $connectionManager;
//
//    /**
//     * @var DatabaseMap
//     */
//    protected $database;
//
//    /**
//     * @var Repository[]
//     */
//    protected $repositories = [];
//
//    /**
//     * @var UnitOfWork
//     */
//    protected $unitOfWork;
//
//    /**
//     * @var EntityMap[]
//     */
//    protected $entityMaps = [];
//
//    /**
//     * @var EventDispatcherInterface
//     */
//    protected $eventDispatcher;
//
//    /**
//     * @param DatabaseMap $database
//     * @param ConnectionManagerInterface $connectionManager
//     * @param EventDispatcherInterface $eventDispatcher
//     */
//    public function __construct(
//        DatabaseMap $database,
//        ConnectionManagerInterface $connectionManager,
//        EventDispatcherInterface $eventDispatcher
//    ) {
//        $this->database = $database;
//        $this->connectionManager = $connectionManager;
//        $this->eventDispatcher = $eventDispatcher;
//    }
//
//    /**
//     * @param string $entityName
//     * @return Repository
//     */
//    public function getRepository($entityName)
//    {
//        $entityMap = $this->getEntityMap($entityName);
//        $class = $entityMap->getRepositoryClass();
//
//        if (!isset($this->repositories[$class])) {
//            $this->repositories[$class] = new $class($this, $entityMap);
//        }
//
//        return $this->repositories[$class];
//    }
//
//    public function persist($entity)
//    {
//        $this->getUnitOfWork()->persist($entity);
//    }
//
//    public function commit()
//    {
//        return $this->getUnitOfWork()->commit();
//    }
//
//    /**
//     * @param object $entity
//     *
//     * @return Repository
//     */
//    public function getRepositoryForEntity($entity)
//    {
//        return $this->getRepository(get_class($entity));
//    }
//
//    /**
//     * @return UnitOfWork
//     */
//    public function getUnitOfWork()
//    {
//        if (null === $this->unitOfWork) {
//            $this->unitOfWork = new UnitOfWork($this);
//        }
//
//        return $this->unitOfWork;
//    }
//
//    /**
//     * @param object $entity
//     * @param string $className
//     *
//     * @return \Closure
//     */
//    public function createEntityReader($className)
//    {
//        return \Closure::bind(function(&$entity, $prop) {
//            return $entity->$prop;
//        }, null, $className);
//    }
//
//    /**
//     * @return EventDispatcherInterface
//     */
//    public function getEventDispatcher()
//    {
//        return $this->eventDispatcher;
//    }
//
//    /**
//     * @param EventDispatcherInterface $eventDispatcher
//     */
//    public function setEventDispatcher($eventDispatcher)
//    {
//        $this->eventDispatcher = $eventDispatcher;
//    }
//
//    /**
//     * @return DatabaseMap
//     */
//    public function getDatabase()
//    {
//        return $this->database;
//    }
//
//    /**
//     * @param DatabaseMap $database
//     */
//    public function setDatabase($database)
//    {
//        $this->database = $database;
//    }
//
//    /**
//     * @return ConnectionManagerInterface
//     */
//    public function getConnectionManager()
//    {
//        return $this->connectionManager;
//    }
//
//    /**
//     * @param ConnectionManagerInterface $connectionManager
//     */
//    public function setConnectionManager($connectionManager)
//    {
//        $this->connectionManager = $connectionManager;
//    }
//
//    /**
//     * @param string $entityName
//     * @return EntityMap
//     */
//    public function getEntityMap($entityName)
//    {
//        $entityName = trim($entityName, '\\');
//        if (isset($this->entityMaps[$entityName])) {
//            return $this->entityMaps[$entityName];
//        }
//
//        $namespaces = explode('\\', $entityName);
//        $className = array_pop($namespaces);
//
//        $entityMapClass = implode('\\', $namespaces) . '\\Map\\' . $className . 'EntityMap';
//
//        if (!class_exists($entityMapClass)) {
//            throw new RuntimeException(sprintf('EntityMap `%s` for entity `%s` not found', $entityMapClass, $entityName));
//        }
//
//        $map = new $entityMapClass();
//        $this->entityMaps[$entityName] = $map;
//
//        return $map;
//    }
//
//    /**
//     * @param object $entity
//     *
//     * @return EntityMap
//     */
//    public function getEntityMapForEntity($entity)
//    {
//        if ($entity instanceof EntityProxyInterface) {
//            $entityName = get_parent_class($entity);
//        } else {
//            $entityName = get_class($entity);
//        }
//
//        return $this->getEntityMap($entityName);
//    }
//}