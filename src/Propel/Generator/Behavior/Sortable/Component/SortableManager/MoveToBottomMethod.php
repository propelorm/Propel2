<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\SortableManager;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class MoveToBottomMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
{$this->getRepositoryAssignment()}

if (\$this->isLast(\$entity)) {
    return;
}
if (null === \$con) {
    \$con = \$repository->getConfiguration()->getConnectionManager({$this->getEntityMapClassName()}::DATABASE_NAME)->getWriteConnection();
}

return \$con->transaction(function () use (\$entity, \$con, \$repository) {
    \$bottom = \$repository->createQuery()->getMaxRank(" . ($this->getBehavior()->useScope() ? "\$entity->getScopeValue(), " : '') . "\$con);

    return \$this->moveToRank(\$entity, \$bottom, \$con);
});
";

        $this->addMethod('moveToBottom')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setDescription('Move the object to the bottom of the list')
            ->setBody($body)
        ;
    }
}
