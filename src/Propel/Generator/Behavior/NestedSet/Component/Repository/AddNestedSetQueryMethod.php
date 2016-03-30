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
class AddNestedSetQueryMethod extends BuildComponent
{
    public function process()
    {
        $body = "
if (!isset(\$query['callable']) || !isset(\$query['arguments']) || !is_array(\$query['arguments'])) {
    throw new PropelException('Malformed query: the array representing a query should contain a `callable` key and an `arguments` key');
}

\$this->nestedSetQueries[] = \$query;
";
        $this->addMethod('addNestedSetQuery')
            ->setDescription("Add an associative array, representing a query, to `nestedSetQueries` property.")
            ->addSimpleDescParameter('query', 'array', 'Array of queries to add')
            ->setBody($body)
        ;
    }
}
