<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class GetSortableManagerMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = <<<EOF
if (!isset(\$this->sortableManager)) {
    \$this->sortableManager = new \\{$this->getObjectClassName(true)}SortableManager();
}

return \$this->sortableManager;
EOF;

        $this->addMethod('getSortableManager')
            ->setDescription('Return the Sortable entity manager object')
            ->setType('\Propel\Runtime\EntityManager\SortableManagerInterface')
            ->setBody($body)
        ;
    }
}
