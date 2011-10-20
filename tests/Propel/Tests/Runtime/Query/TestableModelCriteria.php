<?php

namespace Propel\Tests\Runtime\Query;

use Propel\Runtime\Query\ModelCriteria;

class TestableModelCriteria extends ModelCriteria
{
    public $joins = array();

    public function replaceNames(&$clause)
    {
        return parent::replaceNames($clause);
    }

}
