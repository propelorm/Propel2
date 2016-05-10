<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Object;

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

    return \$this->get{$behavior->getFieldForParameter('scope_field')->getMethodName()}();
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
\$this->set{$behavior->getEntity()->getField($scopeField)->getMethodName()}(null ? null : \$v[$idx]);
";
            }
        } else {
            $body .= "
\$this->set{$behavior->getFieldForParameter('scope_field')->getMethodName()}(\$v);
";
        }

        $body .= "

return \$this;
";

        $this->addMethod('setScopeValue')
            ->addSimpleParameter('v')
            ->setDescription("Wrap the setter for scope value")
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body);
    }
}
