<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\ConcreteInheritance;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\ForeignKey;

/**
 * Makes a model inherit another one. The model with this behavior gets a copy
 * of the structure of the parent model. In addition, both the ActiveRecord and
 * ActiveQuery classes will extend the related classes of the parent model.
 * Lastly (an optionally), the data from a model with this behavior is copied
 * to the parent model.
 *
 * @author François Zaninotto
 */
class ConcreteInheritanceBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'extends'             => '',
        'descendant_column'   => 'descendant_class',
        'copy_data_to_parent' => 'true',
        'schema'              => ''
    );

    public function modifyTable()
    {
        $table = $this->getTable();
        $parentTable = $this->getParentTable();

        if ($this->isCopyData()) {
            // tell the parent table that it has a descendant
            if (!$parentTable->hasBehavior('concrete_inheritance_parent')) {
                $parentBehavior = new ConcreteInheritanceParentBehavior();
                $parentBehavior->setName('concrete_inheritance_parent');
                $parentBehavior->addParameter(array('name' => 'descendant_column', 'value' => $this->getParameter('descendant_column')));
                $parentTable->addBehavior($parentBehavior);
                // The parent table's behavior modifyTable() must be executed before this one
                $parentBehavior->getTableModifier()->modifyTable();
                $parentBehavior->setTableModified(true);
            }
        }

        // Add the columns of the parent table
        foreach ($parentTable->getColumns() as $column) {
            if ($column->getName() == $this->getParameter('descendant_column')) {
                continue;
            }
            if ($table->hasColumn($column->getName())) {
                continue;
            }
            $copiedColumn = clone $column;
            if ($column->isAutoIncrement() && $this->isCopyData()) {
                $copiedColumn->setAutoIncrement(false);
            }
            $table->addColumn($copiedColumn);
            if ($column->isPrimaryKey() && $this->isCopyData()) {
                $fk = new ForeignKey();
                $fk->setForeignTableCommonName($column->getTable()->getCommonName());
                $fk->setForeignSchemaName($column->getTable()->getSchema());
                $fk->setOnDelete('CASCADE');
                $fk->setOnUpdate(null);
                $fk->addReference($copiedColumn, $column);
                $fk->isParentChild = true;
                $table->addForeignKey($fk);
            }
        }

        // add the foreign keys of the parent table
        foreach ($parentTable->getForeignKeys() as $fk) {
            $copiedFk = clone $fk;
            $copiedFk->setName('');
            $copiedFk->setRefPhpName('');
            $this->getTable()->addForeignKey($copiedFk);
        }

        // add the indices of the parent table
        foreach ($parentTable->getIndices() as $index) {
            $copiedIndex = clone $index;
            $copiedIndex->setName('');
            $this->getTable()->addIndex($copiedIndex);
        }

        // add the unique indices of the parent table
        foreach ($parentTable->getUnices() as $unique) {
            $copiedUnique = clone $unique;
            $copiedUnique->setName('');
            $this->getTable()->addUnique($copiedUnique);
        }

        // add the Behaviors of the parent table
        foreach ($parentTable->getBehaviors() as $behavior) {
            if ($behavior->getName() == 'concrete_inheritance_parent' || $behavior->getName() == 'concrete_inheritance') {
                continue;
            }
            // validate behavior. If validate behavior already exists, clone only rules from parent
            if ('validate' === $behavior->getName() && $table->hasBehavior('validate')) {
                $table->getBehavior('validate')->mergeParameters($behavior->getParameters());

                continue;
            }
            $copiedBehavior = clone $behavior;
            $copiedBehavior->setTableModified(false);
            $this->getTable()->addBehavior($copiedBehavior);
        }

    }

    public function getParentTable()
    {
        $database = $this->getTable()->getDatabase();
        $tableName = $database->getTablePrefix() . $this->getParameter('extends');
        if ($database->getPlatform()->supportsSchemas() && $this->getParameter('schema')) {
            $tableName = $this->getParameter('schema').$database->getPlatform()->getSchemaDelimiter().$tableName;
        }

        return $database->getTable($tableName);
    }

    public function isCopyData()
    {
        return 'true' === $this->getParameter('copy_data_to_parent');
    }

    public function parentClass($builder)
    {
        $parentTable = $this->getParentTable();
        switch (get_class($builder)) {
            case 'Propel\Generator\Builder\Om\ObjectBuilder':
                return $builder->declareClassFromBuilder($builder->getNewStubObjectBuilder($parentTable), true);
            case 'Propel\Generator\Builder\Om\QueryBuilder':
                return $builder->declareClassFromBuilder($builder->getNewStubQueryBuilder($parentTable), true);
        }

        return null;
    }
}
