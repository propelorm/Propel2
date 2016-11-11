<?php


namespace Propel\Generator\Builder\Om\Component\Query;


use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\CrossRelation;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Relation;
use Propel\Runtime\ActiveQuery\ModelJoin;

/**
 * Adds all filterBy$relationName methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FilterByCrossRelationMethods extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
        $this->getDefinition()->declareUse('Propel\Runtime\Exception\PropelException');

        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addFilterByCrossRelation($crossRelation);
        }
    }

    /**
     * Adds the filterByFk method for this object.
     *
     * @param CrossRelation $crossRelation
     */
    protected function addFilterByCrossRelation(CrossRelation $crossRelation)
    {
        $relationName = $this->getRefRelationPhpName($crossRelation->getIncomingRelation(), $plural = false);

        foreach ($crossRelation->getRelations() as $relation) {
            $queryClass = $this->getQueryClassName();
            $foreignEntity = $relation->getForeignEntity();
            $fkPhpName = $foreignEntity->getFullClassName();
            $relName = $this->getRelationPhpName($relation, $plural = false);
            $objectName = '$' . $foreignEntity->getCamelCaseName();

            $description = "Filter the query by a related $fkPhpName object
using the {$relation->getEntity()->getName()} entity as cross reference";

            $body = "
return \$this
    ->use{$relationName}Query()
    ->filterBy{$relName}($objectName, \$comparison)
    ->endUse();
";

            $methodName = "filterBy$relName";
            $variableParameter = new PhpParameter($foreignEntity->getCamelCaseName());

//            if ($relation->isComposite()) {
                $variableParameter->setType('\\'.$fkPhpName);
                $variableParameter->setTypeDescription("The related object to use as filter");
//            } else {
//                $variableParameter->setType("$fkPhpName|ObjectCollection");
//                $variableParameter->setTypeDescription("The related object(s) to use as filter");
//            }

            $this->addMethod($methodName)
                ->addParameter($variableParameter)
                ->addSimpleDescParameter(
                    'comparison',
                    'string',
                    'Operator to use for the column comparison, defaults to Criteria::EQUAL',
                    null
                )
                ->setDescription($description)
                ->setType("\$this|" . $queryClass)
                ->setTypeDescription("The current query, for fluid interface")
                ->setBody($body);
        }
    }
}