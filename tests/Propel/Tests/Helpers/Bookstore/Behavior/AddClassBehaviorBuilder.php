<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Generator\Builder\Om\AbstractBuilder;

class AddClassBehaviorBuilder extends AbstractBuilder
{
    public $overwrite = true;

    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        return parent::getFullClassName() . 'FooClass';
    }

    /**
     * In this method the actual builder will define the class definition in $this->definition.
     *
     * @return false|null return false if this class should not be generated.
     */
    protected function buildClass()
    {
        $tableName = $this->getEntity()->getTableName();
        $this->getDefinition()->setDescription("Test class for Additional builder enabled on the '$tableName' table.");
    }
}
