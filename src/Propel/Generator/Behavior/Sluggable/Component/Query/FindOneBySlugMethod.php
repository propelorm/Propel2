<?php


namespace Propel\Generator\Behavior\Sluggable\Component\Query;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class FindOneBySlugMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $scope = ('' != $this->getBehavior()->getParameter('scope_field'));
        $body = "return \$this->filterBySlug(\$slug)";
        if ($scope) {
            $body .= "->filterByScope(\$scope)";
        }
        $body .= "->findOne();";

        $method = $this->addMethod('findOneBySlug')
            ->addSimpleParameter('slug', 'string', 'The value to use as filter')
            ->setDescription('Find one object based on its slug')
            ->setType($this->getObjectClassName())
            ->setTypeDescription('the result, formatted by the current formatter')
            ->setBody($body);
        if ($scope) {
            $method->addSimpleParameter('scope', 'int');
        }
    }
}