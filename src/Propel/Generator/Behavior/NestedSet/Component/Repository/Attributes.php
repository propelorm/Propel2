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

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Attributes extends BuildComponent
{
    public function process()
    {
        $this->addProperty('nestedManager', null)
            ->setType('NestedManagerInterface')
            ->setDescription('Instance of NestedManagerInterface');
        $this->addProperty('nestedSetQueries', array())
            ->setDescription('Queries to be executed in the save transaction');
        $this->addProperty('nestedSetEntityPool', array())
            ->setDescription('Array of Nested Set entities. This property is useful to maintain the entities in sync with the database.');
    }
}
