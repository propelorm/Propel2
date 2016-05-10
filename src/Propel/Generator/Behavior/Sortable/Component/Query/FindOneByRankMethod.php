<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Query;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FindOneByRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        list($methodSignature) = $behavior->generateScopePhp();
        $listSignature = $this->parameterToString($methodSignature);

        if ($useScope) {
            $listSignature = str_replace(' = null', '', $listSignature);
        }

        $body = "
return \$this
    ->filterByRank(\$rank" . ($useScope ? ", $listSignature" : "") . ")
    ->findOne();
";

        $rankParam = PhpParameter::create('rank')->setType('integer');
        array_unshift($methodSignature, $rankParam);

        $this->addMethod('findOneByRank')
            ->setParameters($methodSignature)
            ->setDescription("Get an item from the list based on its rank")
            ->setType($this->getObjectClassName())
            ->setBody($body)
        ;
    }
}
