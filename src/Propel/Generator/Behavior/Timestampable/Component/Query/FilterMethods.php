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
            $updateFieldName = var_export($this->getEntity()->getField($behavior->getParameter('update_field'))->getName(), true);

            $this->addMethod('recentlyUpdated')
                ->setDescription('Filter by the latest updated')
                ->addSimpleDescParameter('days', 'integer', 'Maximum age of the latest update in days', 7)
                ->setBody("return \$this->filterBy($updateFieldName, time() - \$days * 24 * 60 * 60, Criteria::GREATER_EQUAL);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('lastUpdatedFirst')
                ->setDescription('Order by update date desc')
                ->setBody("return \$this->orderBy($updateFieldName, Criteria::DESC);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('firstUpdatedFirst')
                ->setDescription('Order by update date asc')
                ->setBody("return \$this->orderBy($updateFieldName, Criteria::ASC);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');
        }

        if ($behavior->withCreatedAt()) {
            $createFieldName = var_export($this->getEntity()->getField($behavior->getParameter('create_field'))->getName(), true);

            $this->addMethod('recentlyCreated')
                ->setDescription('Filter by the latest created')
                ->addSimpleDescParameter('days', 'integer', 'Maximum age of in days', 7)
                ->setBody("return \$this->filterBy($createFieldName, time() - \$days * 24 * 60 * 60, Criteria::GREATER_EQUAL);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('lastCreatedFirst')
                ->setDescription('Order by create date desc')
                ->setBody("return \$this->orderBy($createFieldName, Criteria::DESC);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');

            $this->addMethod('firstCreatedFirst')
                ->setDescription('Order by create date asc')
                ->setBody("return \$this->orderBy($createFieldName, Criteria::ASC);")
                ->setType('$this|' . $queryClassName)
                ->setTypeDescription('The current query, for fluid interface');
        }
    }
}