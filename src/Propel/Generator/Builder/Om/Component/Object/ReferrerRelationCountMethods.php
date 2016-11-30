<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all referrer count methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ReferrerRelationCountMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        foreach ($this->getEntity()->getReferrers() as $refRelation) {
            if ($refRelation->isLocalPrimaryKey()) {
                //one-to-one
                continue;
            }
            $this->addRefCountMethod($refRelation);
        }
    }

    /**
     * Adds the method that returns the size of the referrer fkey collection.
     *
     * @param Relation $refRelation
     */
    protected function addRefCountMethod(Relation $refRelation)
    {
        $fkQueryClassName = $this->getClassNameFromBuilder($this->getBuilder()->getNewStubQueryBuilder($refRelation->getEntity()));
        $relCol = $this->getRefRelationPhpName($refRelation, $plural = true);
        $collName = $this->getRefRelationCollVarName($refRelation);

        $className = $this->useClass($refRelation->getEntity()->getFullClassName());

        $description = <<<EOF
Returns the number of related $className objects.
EOF;

        $body = <<<EOF
if (func_num_args() === 0 || \$this->isNew()) {
    return count(\$this->$collName);
}

\$query = $fkQueryClassName::create(null, \$criteria);
if (\$distinct) {
    \$query->distinct();
}

return \$query
    ->filterBy{$this->getRelationPhpName($refRelation)}(\$this)
    ->count();
EOF;


        $this->addMethod('count' . $relCol)
            ->setDescription($description)
            ->addSimpleParameter('criteria', 'Criteria', null)
            ->addSimpleParameter('distinct', 'boolean', false)
            ->setBody($body)
            ->setType('integer')
            ->setTypeDescription("Count of related $className objects")
        ;
    }
} 