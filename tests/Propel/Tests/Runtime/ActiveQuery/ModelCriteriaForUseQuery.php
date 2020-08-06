<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class ModelCriteriaForUseQuery extends ModelCriteria
{
    /**
     * @param string $dbName
     * @param string $modelName
     * @param string|null $modelAlias
     */
    public function __construct($dbName = 'bookstore', $modelName = 'Propel\Tests\Bookstore\Author', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * @return \Propel\Tests\Runtime\ActiveQuery\ModelCriteriaForUseQuery
     */
    public function withNoName()
    {
        return $this
            ->filterBy('FirstName', null, Criteria::ISNOTNULL)
            ->where($this->getModelAliasOrName() . '.LastName IS NOT NULL');
    }
}
