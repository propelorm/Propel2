<?php


namespace Propel\Runtime\Session;

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
            $this->orderedList = $this->sorter->sort();
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