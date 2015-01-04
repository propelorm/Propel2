<?php


namespace Propel\Generator\Behavior\Sluggable\Component\Query;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class FilterBySlugMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $fieldName = $this->getBehavior()->getParameter('slug_field');
        $this->getDefinition()->declareUse($this->getQueryClassName(true));
        $fieldConstant = $this->getEntity()->getField($fieldName)->getConstantName();

        $body = "
return \$this->addUsingAlias($fieldConstant, \$slug, Criteria::EQUAL);
";

        $this->addMethod('filterBySlug')
            ->addSimpleParameter('slug', 'string', 'The value to use as filter')
            ->setDescription('Filter the query on the slug column')
            ->setType('$this|' . $this->getQueryClassName())
            ->setTypeDescription('The current query, for fluid interface')
            ->setBody($body);
    }
}