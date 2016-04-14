<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sluggable\Component\Query;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Runtime\ActiveQuery\Criteria;

class OrderBySlugMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
return \$this->OrderBy('{$this->getBehavior()->getFieldForParameter('slug_field')->getName()}', \$order);
";

        $this->addMethod('orderBySlug')
            ->addSimpleDescParameter('order', 'string', 'If ascending or descending order', Criteria::ASC)
            ->setDescription('Filter the query on the slug field')
            ->setType('$this|' . $this->getQueryClassName())
            ->setTypeDescription('The current query, for fluid interface')
            ->setBody($body);
    }
}