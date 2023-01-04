<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Map\RelationMap;

/**
 * Specialized Criterion used for EXISTS with simpler constructor than ExistsQueryCriterion for BC
 */
class ExistsCriterion extends ExistsQueryCriterion
{
 /**
  * @phpstan-param \Propel\Runtime\ActiveQuery\Criterion\ExistsCriterion::TYPE_*|null $typeOfExists
  *
  * @param \Propel\Runtime\ActiveQuery\ModelCriteria|\Propel\Runtime\ActiveQuery\Criteria $outerQuery
  * @param \Propel\Runtime\ActiveQuery\ModelCriteria $existsQuery
  * @param string|null $typeOfExists Either ExistsCriterion::TYPE_EXISTS or ExistsCriterion::TYPE_NOT_EXISTS
  * @param \Propel\Runtime\Map\RelationMap|null $relationMap where outer query is on the left side
  */
    public function __construct(
        $outerQuery,
        ModelCriteria $existsQuery,
        ?string $typeOfExists = null,
        ?RelationMap $relationMap = null
    ) {
        parent::__construct($outerQuery, null, $typeOfExists, $existsQuery);

        if ($relationMap && $outerQuery instanceof ModelCriteria) {
            $this->initForRelation($outerQuery, $relationMap);
        }
    }
}
