<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class NestedSetEntityPoolMethods extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addAddEntityToPool();
        $this->addRemoveEntityFromPool();
        $this->addClearEntityPool();
        $this->addGetEntityFromPool();
    }

    public function addAddEntityToPool()
    {
        $body = <<<EOF
if (null === \$key) {
    \$key = \$this->getNestedManager()->getPk(\$object);
}

if (!isset(\$this->nestedSetEntityPool[\$key])) {
    \$this->nestedSetEntityPool[\$key] = \$object;
}
EOF;
        $this->addMethod('addEntityToPool', 'protected')
            ->setDescription('Add an entity to the internal Nested Set entity pool')
            ->addSimpleDescParameter('object', $this->getObjectClassName(), 'The entity to add to the pool')
            ->addSimpleDescParameter('key', 'string', 'The string to use as array key.', null)
            ->setBody($body)
        ;
    }

    public function addRemoveEntityFromPool()
    {
        $body = <<<EOF
if (null === \$object) {
    return;
}
\$manager = \$this->getNestedManager();

unset(\$this->nestedSetEntityPool[\$manager->getPk(\$object)]);
EOF;

        $this->addMethod('removeEntityFromPool', 'protected')
            ->setDescription('Remove an entity from the internal nested set entity pool.')
            ->addSimpleDescParameter('object', $this->getObjectClassName(), 'The object to remove from the pool')
            ->setBody($body)
        ;
    }

    public function addGetEntityFromPool()
    {
        $body = <<<EOF
if (isset(\$this->nestedSetEntityPool[\$key])) {
    return \$this->nestedSetEntityPool[\$key];
}

return null;
EOF;
        $this->addMethod('getEntityFromPool', 'protected')
            ->setDescription('Get an entity from the internal Nested Set entity pool.')
            ->setType($this->getObjectClassName())
            ->addSimpleDescParameter('key', 'string', 'The hash of the entity to retrieve')
            ->setBody($body)
        ;
    }

    public function addClearEntityPool()
    {
        $this->addMethod('clearEntityPool', 'protected')
            ->setDescription('Remove all entities from the Nested Set entity pool.')
            ->setBody("\$this->nestedSetEntityPool = [];")
        ;
    }
}
