<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PostDelete extends BuildComponent
{
    use NamingTrait;

    /**
     * @return string
     */
    public function process()
    {
        $behavior = $this->getBehavior();

        $i18nEntity = $behavior->getI18nEntity();

        $code = "
// emulate delete cascade
/** @var {$this->getRepositoryClassNameForEntity($i18nEntity, true)} \$i18nRepository */
\$i18nRepository = \$this->getConfiguration()->getRepository('{$i18nEntity->getFullClassName()}');

foreach (\$event->getEntities() as \$entity) {
    if (\$entity instanceof {$i18nEntity->getFullClassName()}) {
        \$i18nRepository->createQuery()
            ->filterBy{$this->getObjectClassName($behavior->getEntity()->getFullClassName())}(\$entity)
            ->delete(\$con);
    }
}
";

        return $code;
    }
}