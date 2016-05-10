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
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com> 
 */
class InsertAtRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$this->getRepository()->getSortableManager()->insertAtRank(\$this, \$rank);

return \$this;
";

        $this->addMethod('insertAtRank')
            ->addSimpleParameter('rank', 'int')
            ->setDescription('Insert at specified rank. The modifications are not persisted until the object is saved.')
            ->setType('$this|' . $this->getObjectClassName(), 'The current object, for fluid interface')
            ->setBody($body)
        ;
    }
}
