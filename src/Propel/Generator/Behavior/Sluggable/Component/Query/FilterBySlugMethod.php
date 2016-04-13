<?php


namespace Propel\Generator\Behavior\Sluggable\Component\Query;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class FilterBySlugMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
return \$this->filterBy{$this->getBehavior()->getFieldForParameter('slug_field')->getName()}(\$slug);
";

        $this->addMethod('filterBySlug')
            ->addSimpleParameter('slug','mixed', 'The value, or the array of values, to use as filter')
            ->setDescription('Filter the query on the slug field')
            ->setType('$this|' . $this->getQueryClassName())
            ->setTypeDescription('The current query, for fluid interface')
            ->setBody($body);
    }
}