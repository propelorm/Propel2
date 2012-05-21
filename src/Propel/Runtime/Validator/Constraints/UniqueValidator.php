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
        if (null === $value) {
            return false;
        }

        $object     = $this->context->getRoot();
        $peer       = $object->getPeer();
        $className  = get_class($object);
        $queryClass = $className . 'Query';
        $filter     = sprintf('filterBy%s', $peer->translateFieldName($this->context->getCurrentProperty(), BasePeer::TYPE_STUDLYPHPNAME, BasePeer::TYPE_PHPNAME));

        if (0 < $queryClass::create()->$filter($value)->count()) {
            $this->setMessage($constraint->message);

            return true;
        }

        return false;
    }
}
