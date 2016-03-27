<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;

class QueryKeyManipulation extends BuildComponent
{
    public function process()
    {
        $this->addSetQueryKey();
        $this->addGetQueryKey();
    }

    protected function addSetQueryKey()
    {
        $this->addMethod('setQueryKey')
            ->addSimpleParameter('key')
            ->setBody("
\$this->queryKey = \$key;

return \$this;
"
            );
    }

    protected function addGetQueryKey()
    {
        $this->addMethod('getQueryKey')
            ->setBody("return \$this->queryKey;");
    }
}
