<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\NestedManager;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Counters extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addCountChildren();
        $this->addCountDescendants();
    }

    protected function addCountChildren()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isLeaf(\$node) || (Configuration::getCurrentConfiguration()->getSession()->isNew(\$node))) {
    return 0;
}

return \$repository->createQuery(null, \$criteria)
    ->childrenOf(\$node)
    ->count(\$con);
";
        $this->addMethod('countChildren')
            ->setDescription('Gets number of children for the given node.')
            ->setType('int', 'Number of children')
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addCountDescendants()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isLeaf(\$node)) {
    // save one query
    return 0;
}

return \$repository->createQuery(null, \$criteria)
    ->descendantsOf(\$node)
    ->count(\$con);
";

        $this->addMethod('countDescendants')
            ->setDescription('Gets number of descendants for the given node.')
            ->setType('int', 'Number of descendant')
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }
}
