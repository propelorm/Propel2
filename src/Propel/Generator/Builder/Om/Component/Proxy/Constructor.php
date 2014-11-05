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
            if (!$field->isLazyLoad()) {
                continue;
            }

            $this->addProperty('_' . $field->getName().'_loaded', false)
                ->setType($repositoryClass);

            $propertiesToUnset[] = '$this->' . $field->getName();
        }

        $body = '
$this->_repository = $repository;
';
        if ($propertiesToUnset) {
            $body .= sprintf('unset(%s);', implode(', ', $propertiesToUnset));
        }


        $this->addProperty('_repository', null, 'private');

        $this->addMethod('__construct')
            ->addSimpleParameter('repository', $repositoryClass)
            ->setBody($body);
    }
}