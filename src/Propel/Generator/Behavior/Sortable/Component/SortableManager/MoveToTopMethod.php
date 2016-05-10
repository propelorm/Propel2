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
class MoveToTopMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
if (\$this->isFirst(\$entity)) {
    return;
}

return \$this->moveToRank(\$entity, 1, \$con);
";

        $this->addMethod('moveToTop')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setDescription('Move the object to the top of the list')
            ->setBody($body)
        ;
    }
}
