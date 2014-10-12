<?php


namespace Propel\Generator\Behavior\Timestampable\Component\Query;


use Propel\Generator\Behavior\Timestampable\TimestampableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class FilterMethods extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var TimestampableBehavior $behavior */
        $behavior = $this->getBehavior();
        $queryClassName = $this->getQueryClassName();

        if ($behavior->withUpdatedAt()) {
            $updateFieldConstant = $this->getEntity()->getField($behavior->getParameter('update_field'))->getConstantName();

            $this->addMethod('recentlyUpdated')
                ->setDescription('Filter by the latest updated')
                ->addSimpleDescParameter('days', 'integer', 'Maximum age of the latest update in days', 7)
                ->setBody("return \$this->addUsingAlias($updateFieldConstant, time() - \$days * 24 * 60 * 60, Criteria::GREATER_EQUAL);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('lastUpdatedFirst')
                ->setDescription('Order by update date desc')
                ->setBody("return \$this->addDescendingOrderByField($updateFieldConstant);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('firstUpdatedFirst')
                ->setDescription('Order by update date asc')
                ->setBody("return \$this->addAscendingOrderByField($updateFieldConstant);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');
        }

        if ($behavior->withCreatedAt()) {
            $createFieldConstant = $this->getEntity()->getField($behavior->getParameter('create_field'))->getConstantName();

            $this->addMethod('recentlyCreated')
                ->setDescription('Filter by the latest created')
                ->addSimpleDescParameter('days', 'integer', 'Maximum age of in days', 7)
                ->setBody("return \$this->addUsingAlias($createFieldConstant, time() - \$days * 24 * 60 * 60, Criteria::GREATER_EQUAL);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('lastCreatedFirst')
                ->setDescription('Order by create date desc')
                ->setBody("return \$this->addDescendingOrderByField($createFieldConstant);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('firstCreatedFirst')
                ->setDescription('Order by create date asc')
                ->setBody("return \$this->addAscendingOrderByField($createFieldConstant);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');
        }
    }
}