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
class MoveDownMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
if (\$this->isLast(\$entity)) {
    return;
}
if (null === \$con) {
    \$con = Configuration::getCurrentConfiguration()->getConnectionManager({$this->getEntityMapClassName()}::DATABASE_NAME)->getWriteConnection();
}
\$con->transaction(function () use (\$entity, \$con) {
    \$next = \$this->getNext(\$entity);
    \$this->swapWith(\$entity, \$next, \$con);
});
";

        $this->addMethod('moveDown')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setDescription('Move the object higher in the list, i.e. exchanges its rank with the one of the next object')
            ->setBody($body)
        ;
    }
}
