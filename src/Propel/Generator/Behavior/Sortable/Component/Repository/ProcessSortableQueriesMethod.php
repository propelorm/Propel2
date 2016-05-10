<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ProcessSortableQueriesMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
foreach (\$this->sortableQueries as \$query) {
    \$query['arguments'][] = \$con;
    call_user_func_array([\$this, \$query['callable']], \$query['arguments']);
}
\$this->sortableQueries = [];
";
        $this->addMethod('processSortableQueries')
            ->setDescription('Execute queries that were saved to be run inside the save transaction.')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection object', null)
            ->setBody($body)
        ;
    }
}
