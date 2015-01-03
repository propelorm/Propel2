<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Validator\Constraints;

use Propel\Runtime\Map\EntityMap;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        $className  = $this->context->getClassName();
        $entityMap   = $className::TABLE_MAP;
        $queryClass = $className . 'Query';
        $filter     = sprintf('filterBy%s', $entityMap::translateFieldName($this->context->getPropertyName(), EntityMap::TYPE_COLNAME, EntityMap::TYPE_PHPNAME));

        if (0 < $queryClass::create()->$filter($value)->count()) {
            $this->context->addViolation($constraint->message);
        }
    }
}
