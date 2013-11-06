<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateColumn;

use Propel\Generator\Model\Behavior;

/**
 * Keeps an aggregate column updated with related table
 *
 * @author François Zaninotto
 */
class AggregateColumnRelationBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'foreign_table' => '',
        'update_method' => '',
    );

    public function objectFilter(&$script, $builder)
    {
        $relationName = $this->getRelationName($builder);
        $relatedClass = $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($this->getForeignTable()));
        $search = "    public function set{$relationName}({$relatedClass} \$v = null)
    {";
        $replace = $search . "
        // aggregate_column_relation behavior
        if (null !== \$this->a{$relationName} && \$v !== \$this->a{$relationName}) {
            \$this->old{$relationName} = \$this->a{$relationName};
        }";
        $script = str_replace($search, $replace, $script);
    }

    public function preUpdateQuery($builder)
    {
        return $this->getFindRelated($builder);
    }

    public function preDeleteQuery($builder)
    {
        return $this->getFindRelated($builder);
    }

    protected function getFindRelated($builder)
    {
        $relationName = $this->getRelationName($builder);

        return "\$this->findRelated{$relationName}s(\$con);";
    }

    public function postUpdateQuery($builder)
    {
        return $this->getUpdateRelated($builder);
    }

    public function postDeleteQuery($builder)
    {
        return $this->getUpdateRelated($builder);
    }

    protected function getUpdateRelated($builder)
    {
        $relationName = $this->getRelationName($builder);

        return "\$this->updateRelated{$relationName}s(\$con);";
    }

    public function queryMethods($builder)
    {
        $script = '';
        $script .= $this->addQueryFindRelated($builder);
        $script .= $this->addQueryUpdateRelated($builder);

        return $script;
    }

    protected function addQueryFindRelated($builder)
    {
        $foreignKey = $this->getForeignKey();
        $foreignQueryBuilder = $builder->getNewStubQueryBuilder($foreignKey->getForeignTable());
        $relationName = $this->getRelationName($builder);

        $builder->declareClassNamespace(
            $foreignKey->getForeignTable()->getPhpName() . 'Query',
            $foreignKey->getForeignTable()->getNamespace()
        );

        return $this->renderTemplate('queryFindRelated', array(
            'foreignTable'     => $this->getForeignTable(),
            'relationName'     => $relationName,
            'variableName'     => lcfirst($relationName),
            'foreignQueryName' => $foreignQueryBuilder->getClassName(),
            'refRelationName'  => $builder->getRefFKPhpNameAffix($foreignKey),
        ));
    }

    protected function addQueryUpdateRelated($builder)
    {
        $relationName = $this->getRelationName($builder);

        return $this->renderTemplate('queryUpdateRelated', array(
            'relationName'     => $relationName,
            'variableName'     => lcfirst($relationName),
            'updateMethodName' => $this->getParameter('update_method'),
        ));
    }

    protected function getForeignTable()
    {
        return $this->getTable()->getDatabase()->getTable($this->getParameter('foreign_table'));
    }

    protected function getForeignKey()
    {
        $foreignTable = $this->getForeignTable();
        // let's infer the relation from the foreign table
        $fks = $this->getTable()->getForeignKeysReferencingTable($foreignTable->getName());
        // FIXME doesn't work when more than one fk to the same table
        return array_shift($fks);
    }

    public function getRelationName($builder)
    {
        return $builder->getFKPhpNameAffix($this->getForeignKey());
    }

    public function getTemplateDirectory()
    {
        $path = parent::getTemplateDirectory();

        if($path !== null) {
            return $path . '_relation';
        }

        return $path;
    }
}
