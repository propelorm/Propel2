<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Propel\Runtime\Util\BasePeer;

class UniqueValidator extends ConstraintValidator
{
    public function isValid($value, Constraint $constraint)
    {
        $object = $this->context->getRoot();
        $className = get_class($object);
        $peer = $object->getPeer();
        $colName = $this->context->getCurrentProperty();
        $colName = $peer->translateFieldName($colName, BasePeer::TYPE_STUDLYPHPNAME, BasePeer::TYPE_PHPNAME);
        $query = call_user_func($className.'Query::create');
        $filter = 'filterBy'.$colName;
        $ret = $query->$filter($value)->count();
        if ($ret >0) {
            $this->setMessage($constraint->message);

            return true;
        } else {
            return false;
        }
    }
}
