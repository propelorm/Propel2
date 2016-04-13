<?php


namespace Propel\Generator\Behavior\Sluggable\Component\Repository;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CleanupSlugPartMethod extends BuildComponent
{

    use NamingTrait;
    use SimpleTemplateTrait;

    public function process()
    {
        $body = $this->renderTemplate([
            'replacePattern' => var_export($this->getBehavior()->getParameter('replace_pattern'), true),
            'maxSize' => $this->getBehavior()->getFieldForParameter('slug_field')->getSize() ?: 255
        ]);

        $this->addMethod('cleanupSlugPart')
            ->addSimpleParameter('slug', 'string')
            ->addSimpleParameter('replacement', 'string', $this->getBehavior()->getParameter('replacement'))
            ->setType('string')
            ->setTypeDescription('The slugified value')
            ->setBody($body)
        ;
    }
}