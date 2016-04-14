<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Counters extends BuildComponent
{
    public function process()
    {
        $this->addCountChildren();
        $this->addCountDescendants();
    }

    protected function addCountChildren()
    {
        $body = "
return \$this->getRepository()->getNestedManager()->countChildren(\$this, \$criteria, \$con);
";
        $this->addMethod('countChildren')
            ->setDescription('Gets number of children for the given node.')
            ->setType('int', 'Number of children')
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addCountDescendants()
    {
        $body = "
return \$this->getRepository()->getNestedManager()->countDescendants(\$this, \$criteria, \$con);
";

        $this->addMethod('countDescendants')
            ->setDescription('Gets number of descendants for the given node.')
            ->setType('int', 'Number of descendant')
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }
}
