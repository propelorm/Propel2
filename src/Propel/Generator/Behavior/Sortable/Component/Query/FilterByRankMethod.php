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
class FilterByRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        list($methodSignature, $buildScope) = $behavior->generateScopePhp();
        $listSignature = $this->parameterToString($methodSignature);

        if ($useScope) {
            $listSignature = str_replace(' = null', '', $listSignature);
        }

        $body = "
return \$this";

        if ($useScope) {
            $body .= "
    ->inList($listSignature)";
        }

        $body .= "
    ->addUsingAlias({$this->getEntityMapClassName()}::RANK_COL, \$rank, Criteria::EQUAL);
";

        $rankParam = PhpParameter::create('rank')->setType('integer');
        array_unshift($methodSignature, $rankParam);

        $this->addMethod('filterByRank')
            ->setParameters($methodSignature)
            ->setDescription("Filter the query based on a rank in the list")
            ->setTypeDescription("The current query, for fluid interface")
            ->setType('$this|' . $this->getQueryClassName())
            ->setBody($body)
        ;
    }
}
