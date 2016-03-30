<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class MakeRootMethod extends NestedSetBuildComponent
{
    public function process()
    {
        $body = "
{$this->getNestedManagerAssignment()}
\$manager->makeRoot(\$this);

return \$this;
";

        $this->addMethod('makeRoot')
            ->setDescription('Creates the supplied node as the root node.')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->setBody($body)
        ;
    }
}
