<?php


namespace Propel\Runtime\Session;

use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\GroupedStringSort;

class DependencyGraph {

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var string[]
     */
    protected $orderedList;

    function __construct($session)
    {
        $this->session = $session;
        $this->sorter = new GroupedStringSort([], true);
        $this->sorter->setCircularInterceptor([$this, 'interceptor']);
        $this->sorter->setSameTypeExtraGrouping(true);
    }

    public function interceptor($nodes)
    {
        $entities = [];
        foreach ($nodes as $node) {
            $entity = $this->session->getEntityById($node);
            $className = get_class($entity);
            $pk = $this->session->getConfiguration()->getEntityMap($className)->getPrimaryKey($entity);
            $entities[] = [$className, $pk];
        }

        var_dump('Circular:', $entities);
        throw new \Exception();
    }

    public function add($entity, $dependencies = [])
    {
        $id = spl_object_hash($entity);
        $class = get_class($entity);

        $depIds = [];

        foreach ($dependencies as $depEntity) {
            $depIds[] = spl_object_hash($depEntity);
        }

        $this->sorter->add($id, $class, $depIds);
    }

    public function getList()
    {
        if (!$this->orderedList) {
            try {
                $this->orderedList = $this->sorter->sort();
            } catch (ElementNotFoundException $e) {
                $source = $this->session->getEntityById($e->getSource());
                $target = $this->session->getEntityById($e->getTarget());
                throw new \Exception(sprintf('Dependency not found. From %s to %s. Did you forget to persist() it?', get_class($source), get_class($target)), 0, $e);
            }
        }
        return $this->orderedList;
    }

    public function getGroups(){

        if (!$this->orderedList) {
            $this->getList();
        }

        return $this->sorter->getGroups();
    }
}