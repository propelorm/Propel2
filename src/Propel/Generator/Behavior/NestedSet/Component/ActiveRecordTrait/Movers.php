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
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Movers extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addMoveToFirstChildOf();
        $this->addMoveToLastChildOf();
        $this->addMoveToPrevSiblingOf();
        $this->addMoveToNextSiblingOf();
        $this->addMoveSubtreeTo();
    }

    protected function addMoveToFirstChildOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "
\$this->getRepository()->getNestedManager()->moveToFirstChildOf(\$this, \$parent, \$con);

return \$this;
";
        $this->addMethod('moveToFirstChildOf')
            ->setDescription('Moves current node and its subtree to be the first child of $parent
The modifications in the current object and the tree are immediate')
            ->setType("\$this|{$objectClassName}", 'The current object (for fluent API support)')
            ->addSimpleDescParameter('parent', $objectClassName, 'Propel object for parent node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveToLastChildOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "
\$this->getRepository()->getNestedManager()->moveToLastChildOf(\$this, \$parent, \$con);

return \$this;
";

        $this->addMethod('moveToLastChildOf')
            ->setDescription('Moves current node and its subtree to be the last child of $parent
The modifications in the current object and the tree are immediate')
            ->setType("\$this|{$objectClassName}", 'The current object (for fluent API support)')
            ->addSimpleDescParameter('parent', $objectClassName, 'Propel object for parent node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveToPrevSiblingOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "
\$this->getRepository()->getNestedManager()->moveToPrevSiblingOf(\$this, \$sibling, \$con);

return \$this;
";

        $this->addMethod('moveToPrevSiblingOf')
            ->setDescription('Moves current node and its subtree to be the previous sibling of $sibling
The modifications in the current object and the tree are immediate')
            ->setType("\$this|{$objectClassName}", 'The current object (for fluent API support)')
            ->addSimpleDescParameter('sibling', $objectClassName, 'Propel object for sibling node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveToNextSiblingOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "
\$this->getRepository()->getNestedManager()->moveToNextSiblingOf(\$this, \$sibling, \$con);

return \$this;
";

        $this->addMethod('moveToNextSiblingOf')
            ->setDescription('Moves current node and its subtree to be the next sibling of $sibling
The modifications in the current object and the tree are immediate')
            ->setType("\$this|{$objectClassName}", 'The current object (for fluent API support)')
            ->addSimpleDescParameter('sibling', $objectClassName, 'Propel object for sibling node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveSubtreeTo()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
\$this->getRepository()->getNestedManager()->moveSubtreeTo(\$this, \$destLeft, \$levelDelta, \$con";
        if ($useScope) {
            $body .= ", \$targetScope";
        }
        $body .= ");

return \$this;
";
        $this->addMethod('moveSubtreeTo')
            ->setDescription('Move current node and its children to location $destLeft and updates rest of tree')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->addSimpleDescParameter('destLeft', 'int', 'Destination left value')
            ->addSimpleDescParameter('levelDelta', 'int', 'Delta to add to the levels')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;

        if ($useScope) {
            $method = $this->getDefinition()->getMethod('moveSubtreeTo');
            $method->addSimpleParameter('targetScope', 'int', null);
        }
    }
}
