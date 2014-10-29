<?php

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class ModelCriteriaForUseQuery extends ModelCriteria
{
    public function __construct($dbName = 'bookstore', $entityName = 'Propel\Tests\Bookstore\Author', $entityAlias = null)
    {
        parent::__construct($dbName, $entityName, $entityAlias);
    }

    public function withNoName()
    {
        return $this
            ->filterBy('FirstName', null, Criteria::ISNOTNULL)
            ->where($this->getModelAliasOrName() . '.LastName IS NOT NULL');
    }
}
