<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>*
 */
class MoveToRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$this->getRepository()->getSortableManager()->moveToRank(\$this, \$newRank, \$con);

return \$this;
";

        $this->addMethod('moveToRank')
            ->setDescription('Move the object to a new rank, and shifts the rank of the objects in between the old and new rank accordingly')
            ->addSimpleDescParameter('newRank', 'integer', 'New rank value')
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object for fluid interface')
            ->setBody($body)
        ;
    }
}
