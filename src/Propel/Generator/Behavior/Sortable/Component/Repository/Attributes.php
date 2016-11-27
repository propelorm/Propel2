<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Attributes extends BuildComponent
{
    public function process()
    {
        $this->addProperty('sortableQueries')
            ->setType('array', 'Array of queries to execute while saving into the database')
            ->setValue(PhpConstant::create('[]'));

        $this->addProperty('sortableManager', null)
            ->setType('SortableManagerInterface')
            ->setDescription('Instance of SortableManagerInterface');
    }
}
