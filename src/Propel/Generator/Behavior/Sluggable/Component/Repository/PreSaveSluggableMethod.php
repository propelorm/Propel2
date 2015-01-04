<?php


namespace Propel\Generator\Behavior\Sluggable\Component\Repository;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PreSaveSluggableMethod extends BuildComponent
{
    use NamingTrait;
    use SimpleTemplateTrait;

    public function process()
    {
        $body = $this->renderTemplate([
            'queryClass' => $this->getQueryClassName()
        ]);

        $this->addMethod('preSaveSluggable')
            ->addSimpleParameter('event')
            ->setBody($body)
        ;
    }
}