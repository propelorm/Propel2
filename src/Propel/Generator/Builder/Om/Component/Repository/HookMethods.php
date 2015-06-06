<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Tests\Bookstore\BookstoreQuery;

/**
 * Adds the (pre|post)(save|insert|update|commit) method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class HookMethods extends BuildComponent
{
    public function process()
    {
        $hooks = ['save', 'insert', 'update', 'delete', 'commit'];

        foreach ($hooks as $hook) {
            $pre = 'pre' . ucfirst($hook);
            $post = 'post' . ucfirst($hook);

            $eventType = sprintf('\Propel\Runtime\Event\%sEvent', ucfirst($hook));
            $eventParameter = new PhpParameter('event');
            $eventParameter->setType($eventType);

            $code = $this->getBuilder()->applyBehaviorHooks($pre);
            if ($code) {
                $this
                    ->addMethod($pre)
                    ->addParameter($eventParameter)
                    ->setBody($code);
            }

            $code = $this->getBuilder()->applyBehaviorHooks($post);
            if ($code) {
                $this
                    ->addMethod($post)
                    ->addParameter($eventParameter)
                    ->setBody($code);
            }
        }
    }
}