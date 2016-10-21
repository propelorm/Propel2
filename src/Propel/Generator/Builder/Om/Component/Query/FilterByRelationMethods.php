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
 * Adds all filterBy$relationName methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FilterByRelationMethods extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
        $this->getDefinition()->declareUse('Propel\Runtime\Exception\PropelException');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $this->addFilterByRelation($relation);
        }
    }

    /**
     * Adds the filterByFk method for this object.
     *
*@param Relation $relation
     */
    protected function addFilterByRelation($relation)
    {
        $entity = $this->getEntity();
//        $queryClass = $this->getQueryClassName();
        $foreignEntity = $relation->getForeignEntity();

//        $foreignBuilder = $this->getObjectClassName();
        $fkStubObjectBuilder = $this->getBuilder()->getNewObjectBuilder($foreignEntity);
        $this->getDefinition()->declareUse($fkStubObjectBuilder->getFullClassName());

        $fkName = '\\' . $this->getClassNameFromBuilder($fkStubObjectBuilder, true);
        $relationName = $this->getRelationName($relation);
        $objectName = '$' . $foreignEntity->getCamelCaseName();

        $description = "Filter the query by a related $fkName object.";

        $body = "
if ($objectName instanceof $fkName) {
    return \$this";
        foreach ($relation->getFieldObjectsMapArray() as $map) {
            list ($localColumnObject, $foreignColumnObject) = $map;
            $body .= "
        ->addUsingAlias(" . $localColumnObject->getFQConstantName() . ", " . $objectName . "->get" . $foreignColumnObject->getName() . "(), \$comparison)";
        }
        $body .= ";";
        if (!$relation->isComposite()) {
            $localColumnConstant = $relation->getLocalField()->getFQConstantName();
            $foreignColumnName = $relation->getForeignField()->getName();
            $keyColumn = $relation->getForeignEntity()->hasCompositePrimaryKey() ? $foreignColumnName : 'PrimaryKey';
            $body .= "
} elseif ($objectName instanceof ObjectCollection) {
    if (null === \$comparison) {
        \$comparison = Criteria::IN;
    }

    return \$this
        ->addUsingAlias($localColumnConstant, {$objectName}->toKeyValue('$keyColumn', '$foreignColumnName'), \$comparison);";
}
        $body .= "
} else {";
        if ($relation->isComposite()) {
            $body .= "
    throw new PropelException('filterBy$relationName() only accepts arguments of type $fkName');";
        } else {
            $body .= "
    throw new PropelException('filterBy$relationName() only accepts arguments of type $fkName or Collection');";
        }
        $body .= "
}
";

        $methodName = "filterBy$relationName";
        $variableParameter = new PhpParameter($foreignEntity->getCamelCaseName());

        if ($relation->isComposite()) {
            $variableParameter->setType($fkName);
            $variableParameter->setTypeDescription("The related object to use as filter");
        } else {
            $variableParameter->setType("$fkName|ObjectCollection");
            $variableParameter->setTypeDescription("The related object(s) to use as filter");
        }

        $this->addMethod($methodName)
            ->addParameter($variableParameter)
            ->addSimpleDescParameter('comparison', 'string', 'Operator to use for the column comparison, defaults to Criteria::EQUAL', null)
            ->setDescription($description)
            ->setType("\$this|" . $this->getQueryClassName())
            ->setTypeDescription("The current query, for fluid interface")
            ->setBody($body);

    }
}