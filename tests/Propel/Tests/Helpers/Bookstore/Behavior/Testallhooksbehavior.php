<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Generator\Model\Behavior;

class TestAllHooksBehavior extends Behavior
{
    protected $tableModifier, $objectBuilderModifier, $queryBuilderModifier;

    public function getTableModifier()
    {
        if (is_null($this->tableModifier)) {
            $this->tableModifier = new TestAllHooksTableModifier($this);
        }

        return $this->tableModifier;
    }

    public function getObjectBuilderModifier()
    {
        if (is_null($this->objectBuilderModifier)) {
            $this->objectBuilderModifier = new TestAllHooksObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    public function getQueryBuilderModifier()
    {
        if (is_null($this->queryBuilderModifier)) {
            $this->queryBuilderModifier = new TestAllHooksQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }
}

class TestAllHooksTableModifier
{
    protected $behavior, $table;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    public function modifyTable()
    {
        $this->table->addColumn(array(
            'name' => 'test',
            'type' => 'TIMESTAMP'
        ));
    }
}

class TestAllHooksObjectBuilderModifier
{
    public function objectAttributes($builder)
    {
        return 'public $customAttribute = 1;';
    }

    public function preSave($builder)
    {
        return '$this->preSave = 1;$this->preSaveIsAfterSave = isset($affectedRows);$this->preSaveBuilder="' . get_class($builder) . '";';
    }

    public function postSave($builder)
    {
        return '$this->postSave = 1;$this->postSaveIsAfterSave = isset($affectedRows);$this->postSaveBuilder="' . get_class($builder) . '";';
    }

    public function preInsert($builder)
    {
        return '$this->preInsert = 1;$this->preInsertIsAfterSave = isset($affectedRows);$this->preInsertBuilder="' . get_class($builder) . '";';
    }

    public function postInsert($builder)
    {
        return '$this->postInsert = 1;$this->postInsertIsAfterSave = isset($affectedRows);$this->postInsertBuilder="' . get_class($builder) . '";';
    }

    public function preUpdate($builder)
    {
        return '$this->preUpdate = 1;$this->preUpdateIsAfterSave = isset($affectedRows);$this->preUpdateBuilder="' . get_class($builder) . '";';
    }

    public function postUpdate($builder)
    {
        return '$this->postUpdate = 1;$this->postUpdateIsAfterSave = isset($affectedRows);$this->postUpdateBuilder="' . get_class($builder) . '";';
    }

    public function preDelete($builder)
    {
        return '$this->preDelete = 1;$this->preDeleteIsBeforeDelete = isset(Table3TableMap::$instances[$this->id]);$this->preDeleteBuilder="' . get_class($builder) . '";';
    }

    public function postDelete($builder)
    {
        return '$this->postDelete = 1;$this->postDeleteIsBeforeDelete = isset(Table3TableMap::$instances[$this->id]);$this->postDeleteBuilder="' . get_class($builder) . '";';
    }

    public function objectMethods($builder)
    {
        return 'public function hello() { return "' . get_class($builder) .'"; }';
    }

    public function objectCall($builder)
    {
        return 'if ($name == "foo") return "bar";';
    }

    public function objectFilter(&$string, $builder)
    {
        $string .= 'class testObjectFilter { const FOO = "' . get_class($builder) . '"; }';
    }
}

class TestAllHooksQueryBuilderModifier
{

    public function staticAttributes($builder)
    {
        return 'static public $customStaticAttribute = 1;public static $staticAttributeBuilder = "' . get_class($builder) . '";';
    }

    public function staticMethods($builder)
    {
        return 'static public function hello() { return "' . get_class($builder) . '"; }';
    }

    public function queryFilter(&$string, $builder)
    {
        $string .= 'class testQueryFilter { const FOO = "' . get_class($builder) . '"; }';
    }

    public function preSelectQuery($builder)
    {
        return '// foo';
    }

    public function preDeleteQuery($builder)
    {
        return '// foo';
    }

    public function postDeleteQuery($builder)
    {
        return '// foo';
    }

    public function preUpdateQuery($builder)
    {
        return '// foo';
    }

    public function postUpdateQuery($builder)
    {
        return '// foo';
    }
}
