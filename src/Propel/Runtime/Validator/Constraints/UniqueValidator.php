<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Validator\Constraints;

use Propel\Runtime\Map\TableMap;
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
        $tableMap   = $className::TABLE_MAP;
        $queryClass = $className . 'Query';
        $filter     = sprintf('filterBy%s', $tableMap::translateFieldName($this->context->getPropertyName(), TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME));
        $matches    = $queryClass::create()->$filter($value);

        $columnName = sprintf('%s.%s', $tableMap::TABLE_NAME, $this->context->getPropertyName());

        if ($this->context->getObject()->isNew() && $matches->count() > 0) {
            $this->context->addViolation($constraint->message);
        }

        else if ($this->context->getObject()->isModified() && $matches->count() > (in_array($columnName, $this->context->getObject()->getModifiedColumns()) ? 0 : 1)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
