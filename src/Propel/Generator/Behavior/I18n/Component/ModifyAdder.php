<?php

namespace Propel\Generator\Behavior\I18n\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\NamingTool;

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
        $behavior = $this->getBehavior();
        $localeFieldName = NamingTool::toUpperCamelCase($behavior->getLocaleField()->getName());
        $adderName = 'add' . $this->getClassNameFromEntity($behavior->getI18nEntity());
        $definition = $this->getBuilder()->getDefinition();

        if ($definition->hasMethod($adderName)) {
            $adder = $definition->getMethod($adderName);
            //adder method has only one parameter
            $param = $adder->getParameter(0);
            $body = "
if (\${$param->getName()} && \$locale = \${$param->getName()}->get{$localeFieldName}()) {
    \$this->set{$localeFieldName}(\$locale);
    \$this->currentTranslations[\$locale] = \${$param->getName()};
}
";
            $body .= $adder->getBody();
            $adder->setBody($body);
        }
    }
}