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
        $replaceSlugPattern = '';

        if ($slugPattern = $this->getBehavior()->getParameter('slug_pattern')) {
            preg_match_all('/\{([a-zA-Z\_]+)\}/', $slugPattern, $matches);
            $replaceSlugPattern = '$slug = ' . var_export($slugPattern, true) . ';';

            if (isset($matches[1])) {

                foreach ($matches[1] as $fieldName) {
                    $getter = 'get' . ucfirst($fieldName);
                    $replaceSlugPattern .= "
    \$slug = str_replace('{{$fieldName}}', \$this->cleanupSlugPart(\$entity->$getter()), \$slug);";
                }
            }
        }

        $body = $this->renderTemplate([
            'queryClass' => $this->getQueryClassName(),
            'separator' => var_export($this->getBehavior()->getParameter('separator') ?: '-', true),
            'slugField' => var_export($this->getBehavior()->getParameter('slug_field') ?: 'slug', true),
            'replaceSlugPattern' => $replaceSlugPattern,
            'withSlugPattern' => !!$replaceSlugPattern
        ]);

        $this->addMethod('preSaveSluggable')
            ->addSimpleParameter('event')
            ->setBody($body);
    }
}