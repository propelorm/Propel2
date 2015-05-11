<?php


namespace Propel\Generator\Builder\Om\Component\Query;


use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Runtime\ActiveQuery\ModelJoin;

/**
 * Adsd all join* methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class JoinMethods extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;


    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\ActiveQuery\ModelJoin');
        $this->getDefinition()->declareUse('Propel\Runtime\ActiveQuery\Criteria');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $queryClass = $this->getQueryClassName();
            $foreignEntry = $relation->getForeignEntity();
            $joinType = $this->getJoinType($relation);

            $relationName = $this->getRelationPhpName($relation);

            $this->addJoin($relationName, $queryClass, $joinType);
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $queryClass = $this->getQueryClassName();
            $foreignEntry = $relation->getEntity();
            $joinType = $this->getJoinType($relation);

            $relationName = $this->getRefRelationPhpName($relation);

            $this->addJoin($relationName, $queryClass, $joinType);
        }
    }

    public function addJoin($relationName, $queryClass, $joinType)
    {

        $body = <<<EOF
\$entityMap = \$this->getEntityMap();
\$relationMap = \$entityMap->getRelation('$relationName');

// create a ModelJoin object for this join
\$join = new ModelJoin();
\$join->setJoinType(\$joinType);
\$join->setRelationMap(\$relationMap, \$this->useAliasInSQL ? \$this->getEntityAlias() : null, \$relationAlias);
if (\$previousJoin = \$this->getPreviousJoin()) {
    \$join->setPreviousJoin(\$previousJoin);
}

// add the ModelJoin to the current object
if (\$relationAlias) {
    \$this->addAlias(\$relationAlias, \$relationMap->getRightEntity()->getName());
    \$this->addJoinObject(\$join, \$relationAlias);
} else {
    \$this->addJoinObject(\$join, '$relationName');
}

return \$this;
EOF;

        $this->addMethod('join' . $relationName)
            ->addSimpleDescParameter('relationAlias', 'string', 'optional alias for the relation', null)
            ->addSimpleDescParameter('joinType', 'string', "Accepted values are null, 'left join', 'right join', 'inner join'", $joinType)
            ->setDescription("Adds a JOIN clause to the query using the $relationName relation.")
            ->setType("\$this|$queryClass")
            ->setTypeDescription("The current query, for fluid interface")
            ->setBody($body);
    }
}