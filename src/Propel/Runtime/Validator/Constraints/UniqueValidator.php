<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Validator\Constraints;

use Propel\Runtime\Map\TableMap;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param \Symfony\Component\Validator\Constraint $constraint
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null) {
            return;
        }

        $className = $this->context->getClassName();
        $tableMap = $className::TABLE_MAP;
        $queryClass = $className . 'Query';
        $filter = sprintf('filterBy%s', $tableMap::translateFieldName($this->context->getPropertyName(), TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME));
        $matches = $queryClass::create()->$filter($value);

        $columnName = sprintf('%s.%s', $tableMap::TABLE_NAME, $this->context->getPropertyName());

        $object = $this->context->getObject();
        if ($object->isNew() && $matches->count() > 0) {
            $this->context->addViolation($constraint->message);
        } elseif ($object->isModified() && $matches->count() > (in_array($columnName, $object->getModifiedColumns()) ? 0 : 1)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
