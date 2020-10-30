<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\ModelCriteria;

class TestableModelCriteria extends ModelCriteria
{
    public $joins = [];

    public function replaceNames(&$sql)
    {
        return parent::replaceNames($sql);
    }
}
