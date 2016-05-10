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
 * @author Cristiano Cinotti
 */
class OldScope extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addProperty('oldScope')->setDescription('The old scope value.');

        $this->addMethod('getOldScope')->setBody("return \$this->oldScope;");

        $this->modifyScopeSetter();
    }

    protected function modifyScopeSetter()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        if ($behavior->hasMultipleScopes()) {
            foreach ($behavior->getScopes() as $idx => $scope) {
                $method = $this->getDefinition()->getMethod("set{$behavior->getEntity()->getField($scope)->getMethodName()}");
                $body = $method->getBody();

                $body = "
// sortable behavior
\$this->oldScope[$idx] = \$this->{$behavior->getEntity()->getField($scope)->getName()};
"
                    . $body;
                $method->setBody($body);
            }
        } else {
            $scope = current($behavior->getScopes());
            $method = $this->getDefinition()->getMethod("set{$behavior->getEntity()->getField($scope)->getMethodName()}");
            $body = $method->getBody();

            $body = "
// sortable behavior
\$this->oldScope = \$this->{$behavior->getEntity()->getField($scope)->getName()};
"
                . $body;
            $method->setBody($body);
        }
    }
}
