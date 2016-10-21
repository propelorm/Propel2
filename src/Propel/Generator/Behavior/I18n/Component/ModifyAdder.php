<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n\Component;

use Propel\Generator\Behavior\I18n\I18nBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Add some instructions to the i18n adder.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class ModifyAdder extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var I18nBehavior $behavior */
        $behavior = $this->getBehavior();
        //$localeFieldName = NamingTool::toUpperCamelCase($behavior->getLocaleField()->getName());
        $adderName = 'add' . $this->getClassNameFromEntity($behavior->getI18nEntity());
        $definition = $this->getBuilder()->getDefinition();

        if ($definition->hasMethod($adderName)) {
            $adder = $definition->getMethod($adderName);
            //adder method has only one parameter
            $param = $adder->getParameter(0);
            $body = "
if (\${$param->getName()} && \$locale = \${$param->getName()}->get{$behavior->getLocaleField()->getMethodName()}()) {
    \$this->set{$behavior->getLocaleField()->getMethodName()}(\$locale);
    \$this->currentTranslations[\$locale] = \${$param->getName()};
}
";
            $body .= $adder->getBody();
            $adder->setBody($body);
        }
    }
}
