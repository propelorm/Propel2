<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class UpdateLoadedNodesMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$manager = \$this->getNestedManager();

\$pks = array_keys(\$this->nestedSetEntityPool);
if (null !== \$prune) {
    \$prunePks[] = \$manager->getPk(\$prune);
    \$pks = array_diff(\$pks, \$prunePks);
}

\$objects = \$this->createQuery()->findPks(\$pks, \$con);

foreach (\$objects as \$object) {
    \$node = \$this->nestedSetEntityPool[\$manager->getPk(\$object)];
    \$node->setLeftValue(\$object->getLeftValue());
    \$node->setRightValue(\$object->getRightValue());
    \$node->setLevel(\$object->getLevel());";

        if ($this->getBehavior()->useScope()) {
            $body .= "
    \$node->setScopeValue(\$object->getScopeValue());
";
        }
        $body .= "
}
";
        $this->addMethod('updateLoadedNodes')
            ->setDescription('Synchronize the loaded nodes with the database.')
            ->addSimpleDescParameter('prune', $this->getObjectClassName(), 'The node to prune from the update.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'The connection to use', null)
            ->setBody($body)
        ;
    }
}
