<?php

namespace Propel\Generator\Builder\Om\Component\Proxy;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the __construct method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Constructor extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $propertiesToUnset = [];
        $repositoryClass = $this->getRepositoryClassName();
        $this->getDefinition()->declareUse($this->getRepositoryClassName(true));

        //reset lazy loaded properties
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isPrimaryKey()) continue;
            if (!$field->isLazyLoad()) continue;

            $propertiesToUnset[] = '$this->' . $field->getName();
        }

        $body = '
$this->_repository = $repository;
';
        if ($propertiesToUnset) {
            $body .= sprintf('unset(%s);', implode(', ', $propertiesToUnset));
        }


        $this->addProperty('_repository', null, 'private');
//        $this->addProperty('__duringInitializing__', false, 'public');

        $this->addMethod('__construct')
            ->addSimpleParameter('repository', $repositoryClass)
            ->setBody($body);
    }
}