<?php

namespace Propel\Generator\Builder\Om\Component\Proxy;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the __get/__set method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class MagicMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {

        $body = '
';

        $loadableProperties = [];
        $codePerField = [];

        foreach ($this->getEntity()->getFields() as $field) {
            $loadableProperties[] = $field->getName();
        }


        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            foreach ($crossRelation->getRelations() as $relation) {
                $loadableProperties[] = $this->getCrossRelationRelationVarName($relation);
            }
        }
        foreach ($this->getEntity()->getRelations() as $relation) {
            $loadableProperties[] = $relation->getField();
        }

        foreach ($loadableProperties as $fieldName) {
            $fieldLazyLoading = "\$this->_repository->getEntityMap()->loadField(\$this, '$fieldName');";
            $codePerField[$fieldName] = $fieldLazyLoading;
        }

        foreach ($codePerField as $fieldName => $code) {
            $body .= "
if (!isset(\$this->__duringInitializing__) && '{$fieldName}' === \$name && !isset(\$this->{$fieldName})) {

    \$this->__duringInitializing__ = true;

    $code

    unset(\$this->__duringInitializing__);
}
";
        }

        $getBody = $body . "
return \$this->\$name;
";
        $this->addMethod('__get')
            ->addSimpleParameter('name')
            ->setBody($getBody);

        $setBody = $body . "
\$this->\$name = \$value;
";
        $this->addMethod('__set')
            ->addSimpleParameter('name')
            ->addSimpleParameter('value')
            ->setBody($setBody);
    }
}