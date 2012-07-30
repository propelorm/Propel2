<?php

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class ModelCriteriaForUseQuery extends ModelCriteria
{
    public function __construct($dbName = 'bookstore', $modelName = 'Propel\Tests\Bookstore\Author', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    public function withNoName()
    {
        return $this
            ->filterBy('FirstName', null, Criteria::ISNOTNULL)
            ->where($this->getModelAliasOrName() . '.LastName IS NOT NULL');
    }
}
