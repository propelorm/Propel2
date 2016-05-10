<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\SortableManager;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class IsLastMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
{$this->getRepositoryAssignment()}
\$q = \$repository->createQuery();";
        if ($behavior->useScope()) {
            if ($behavior->hasMultipleScopes()) {
                $body .= "
\$last = call_user_func_array([\$q, 'getMaxRank'], \$entity->getScopeValue());";
            } else {
                $body .= "
\$last = \$q->getMaxRank(\$entity->getScopeValue());";
            }
        } else {
            $body .= "
\$last = \$q->getMaxRank();";
        }
        $body .= "
        
return \$last == \$entity->getRank();
";

        $this->addMethod('isLast')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->setDescription('Check if the object is last in the list, i.e. if its rank is the highest rank')
            ->setType('bool')
            ->setBody($body)
        ;
    }
}
