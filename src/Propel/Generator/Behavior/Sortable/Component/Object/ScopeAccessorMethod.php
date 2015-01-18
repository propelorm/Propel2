<?php

namespace Propel\Generator\Behavior\SortableBehavior\Component\Object;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ScopeAccessorMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addGetter();
        $this->addSetter();
    }

    protected function addGetter()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "";

        if ($behavior->hasMultipleScopes()) {
            $body .= "
\$result = array();
\$onlyNulls = true;
";
            foreach ($behavior->getScopes() as $scopeField) {
                $body .= "
\$onlyNulls &= null === (\$result[] = \$this->{$scopeField});
";
            }

            $body .= "

return \$onlyNulls && \$returnNulls ? null : \$result;
";
        } else {

            $body .= "

return \$this->{$behavior->getFieldForParameter('scope_field')->getName()}();
";
        }

        $this->addMethod('getScopeValue')
            ->addSimpleDescParameter(
                'returnNulls',
                'boolean',
                'If true and all scope values are null, this will return null instead of a array full with nulls',
                true
            )
            ->setDescription("Wrap the getter for scope value")
            ->setType('boolean|array')
            ->setBody($body);
    }

    protected function addSetter()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = '';
        if ($behavior->hasMultipleScopes()) {

            foreach ($behavior->getScopes() as $idx => $scopeField) {
                $body .= "
\$this->{$scopeField} = \$v === null ? null : \$v[$idx];
";
            }

        } else {
            $body .= "
\$this->{$behavior->getFieldForParameter('scope_field')->getName()} = \$v;
";
        }

        $body .= "return \$this;";

        $this->addMethod('setScopeValue')
            ->addSimpleDescParameter('v')
            ->setDescription("Wrap the setter for scope value")
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body);
    }
}