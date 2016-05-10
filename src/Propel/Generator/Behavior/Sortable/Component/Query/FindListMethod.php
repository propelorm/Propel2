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
class findListMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        list($methodSignature) = $behavior->generateScopePhp();
        $listSignature = $this->parameterToString($methodSignature);

        $body = "
return \$this";

        if ($useScope) {
            $body .= "
    ->inList($listSignature)";
        }

        $body .= "
    ->orderByRank()
    ->find();
";

        $this->addMethod('findList')
            ->setParameters($methodSignature)
            ->setDescription("Returns " . ($useScope ? 'a' : 'the') ." list of objects")
            ->setType($this->getObjectClassName().'[]')
            ->setTypeDescription("The list of results, formatted by the current formatter")
            ->setBody($body)
        ;
    }
}
