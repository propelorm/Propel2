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
class InsertAtTopMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$this->getRepository()->getSortableManager()->insertAtRank(\$this, 1);

return \$this;
";

        $this->addMethod('insertAtTop')
            ->setDescription('Insert in the first rank. The modifications are not persisted until the object is saved.')
            ->setType('$this|' . $this->getObjectClassName(), 'The current object, for fluid interface')
            ->setBody($body)
        ;
    }
}
