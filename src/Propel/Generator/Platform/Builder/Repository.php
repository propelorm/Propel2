<?php


namespace Propel\Generator\Platform\Builder;


use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\PhpModel\ClassDefinition;

class Repository extends AbstractBuilder
{
    public function buildClass()
    {
        $this->applyComponent('Repository\\DoFindMethod');
        $this->applyComponent('Repository\\DoDeleteAllMethod');
    }
}