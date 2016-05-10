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
class SwapWithMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
    if (null === \$con) {
        \$con = Configuration::getCurrentConfiguration()->getConnectionManager({$this->getEntityMapClassName()}::DATABASE_NAME)->getWriteConnection();
    }
    \$con->transaction(function () use (\$entity, \$exchange) {";

        if ($this->getBehavior()->useScope()) {
            $body .= "
        \$oldScope = \$entity->getScopeValue();
        \$newScope = \$exchange->getScopeValue();
        if (\$oldScope != \$newScope) {
            \$entity->setScopeValue(\$newScope);
            \$exchange->setScopeValue(\$oldScope);
        }";
        }

        $body .= "
        \$oldRank = \$entity->getRank();
        \$newRank = \$exchange->getRank();

        \$entity->setRank(\$newRank);
        \$exchange->setRank(\$oldRank);
        
        \$session = Configuration::getCurrentConfiguration()->getSession();
        \$session->persist(\$entity);
        \$session->persist(\$exchange);
        \$session->commit();
    });
";

        $this->addMethod('swapWith')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->addSimpleDescParameter('exchange', $this->getObjectClassName(), 'The object to exchange the rank with')
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setDescription('Exchange the rank of the object with the one passed as argument, and saves both objects')
            ->setBody($body)
        ;
    }
}
