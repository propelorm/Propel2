<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\NestedManager;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class MakeRootMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
if (\$node->getLeftValue() || \$node->getRightValue()) {
    throw new PropelException('Cannot turn an existing node into a root node.');
}

\$node->setLeftValue(1);
\$node->setRightValue(2);
\$node->setLevel(0);
";

        $this->addMethod('makeRoot')
            ->setDescription('Creates the supplied node as the root node.')
            ->addSimpleDescParameter('node', "{$this->getObjectClassName()}", "The node to make root.")
            ->setBody($body)
        ;
    }
}
