<?php

namespace Propel\Generator\Behavior\I18n\Component\EntityMap;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Model\NamingTool;

/**
 * Add a specialized `populateObject` method to the EntityMap.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PopulateObject extends BuildComponent
{
    public function process()
    {
        $method = $this->getDefinition()->getMethod('populateObject');

        $doMethod = clone $method;
        $doMethod->setName('doPopulateObject');
        $this->getDefinition()->setMethod($doMethod);

        $body = "
\$object = \$this->doPopulateObject(\$row, \$offset, \$indexType, \$entity);

if (isset(\$row[3])) {
    \$object->set{$this->getBehavior()->getLocaleField()->getMethodName()}(\$row[3]);
}

return \$object;
";
        $method->setBody($body);
    }
}
