<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Object;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RankAccessorMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
return \$this->{$behavior->getFieldForParameter('rank_field')->getName()};
";

        $this->addMethod('getRank')
            ->setDescription("Wrap the getter for rank value")
            ->setType('integer')
            ->setBody($body)
        ;

        $body = "
\$this->{$behavior->getFieldForParameter('rank_field')->getName()} = \$v;

return \$this;
";

        $this->addMethod('setRank')
            ->addSimpleParameter('v')
            ->setDescription("Wrap the setter for rank value")
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body)
        ;
    }
}
