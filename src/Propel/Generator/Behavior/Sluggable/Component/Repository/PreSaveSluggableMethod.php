<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sluggable\Component\Repository;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PreSaveSluggableMethod extends BuildComponent
{
    use NamingTrait;
    use SimpleTemplateTrait;

    public function process()
    {
        $behavior = $this->getBehavior();
        $pattern = $behavior->getParameter('slug_pattern');

        $variables['slugFiled'] = $behavior->getParameter('slug_field');
        $variables['slugColumn'] = "\\{$this->getEntityMapClassName(true)}::{$behavior->getFieldForParameter('slug_field')->getConstantName()}";
        $variables['fieldSize'] = $behavior->getFieldForParameter('slug_field')->getSize();
        $variables['primaryStringField'] = $behavior->getPrimaryStringFieldName();
        $variables['replacement'] = $behavior->getParameter('replacement');
        $variables['separator'] = $behavior->getParameter('separator');
        $variables['notPermanent'] = ('false' === $behavior->getParameter('permanent'));
        $variables['scopeField'] = ('' == $behavior->getParameter('scope_field')) ? null : $behavior->getFieldForParameter('scope_field')->getName();
        $variables['replacePattern'] = $behavior->getParameter('replace_pattern');
        if ($pattern) {
            $variables['createSlugFunction'] = '\'' . str_replace(['{', '}'], ['\' . $cleanupSlugPart($entity->get', '()) . \''], $pattern) . '\'';
            $variables['pattern'] = (bool) $pattern;
        } else {
            $variables['createSlugFunction'] = "\$entity->__toString()";
        }

        $this->addMethod('preSaveSluggable')
            ->addSimpleParameter('event')
            ->setBody($this->renderTemplate($variables))
        ;
    }
}
