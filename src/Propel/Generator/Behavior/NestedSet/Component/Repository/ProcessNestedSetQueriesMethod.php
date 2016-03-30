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
class ProcessNestedSetQueriesMethod extends BuildComponent
{
    public function process()
    {
        $body = "
foreach (\$this->nestedSetQueries as \$query) {
    \$query['arguments'][]= \$con;
    call_user_func_array([\$this, \$query['callable']], \$query['arguments']);
}
\$this->nestedSetQueries = array();
";
        $this->addMethod('processNestedSetQueries')
            ->setDescription('Execute queries that were saved to be run inside the save transaction.')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection object', null)
            ->setBody($body)
        ;
    }
}
