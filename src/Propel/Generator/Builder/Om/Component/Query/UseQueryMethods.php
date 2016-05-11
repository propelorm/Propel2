<?php


namespace Propel\Generator\Builder\Om\Component\Query;


use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Relation;
use Propel\Runtime\ActiveQuery\ModelJoin;

/**
 * Adds all use{$relationName}Query methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class UseQueryMethods extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
//        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
//        $this->getDefinition()->declareUse('Propel\Runtime\Exception\PropelException');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $this->addUseRelationMethod($relation);
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $this->addUseRefRelationMethod($relation);
        }
    }

    /**
     * Adds the filterByFk method for this object.
     *
     * @param Relation $relation
     */
    protected function addUseRefRelationMethod(Relation $relation)
    {
        $foreignEntity = $relation->getEntity();
        $relationName = $this->getRefRelationPhpName($relation);
        $queryClass = $this->getQueryClassNameForEntity($foreignEntity);
        $this->addUseQueryMethod($relationName, $queryClass, $relation);
    }

    /**
     * Adds the filterByFk method for this object.
     *
     * @param Relation $relation
     */
    protected function addUseRelationMethod(Relation $relation)
    {
        $foreignEntity = $relation->getForeignEntity();
        $relationName = $this->getRelationPhpName($relation);
        $queryClass = $this->getQueryClassNameForEntity($foreignEntity);

        $this->addUseQueryMethod($relationName, $queryClass, $relation);
    }

    protected function addUseQueryMethod($relationName, $queryClass, Relation $relation)
    {
        $methodName = "use{$relationName}Query";
        $relationVarName = lcfirst($relationName);

        $body = "
return \$this
    ->join" . $relationName . "(\$relationAlias, \$joinType)
    ->useQuery(\$relationAlias ? \$relationAlias : '$relationVarName');
";

        $joinType = $this->getJoinType($relation);

        $this->addMethod($methodName)
            ->addSimpleDescParameter('relationAlias', 'string', 'optional alias for the relation, to be used as main alias in the secondary query', null)
            ->addSimpleDescParameter('joinType', 'string', "Accepted values are null, 'left join', 'right join', 'inner join'", $joinType)
            ->setDescription("Use the $relationVarName relation " . $relation->getForeignEntity()->getName() . " object

@see useQuery()")
            ->setType($queryClass)
            ->setTypeDescription("A secondary query class using the current class as primary query")
            ->setBody($body);

    }
}