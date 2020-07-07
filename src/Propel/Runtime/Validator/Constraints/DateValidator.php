<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Validator\Constraints;

use Symfony\Component\Validator\Constraints\DateValidator as SymfonyDateValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Validates Dates
 * Provides FC on \DateTimeInterface values which were removed from the DateValidator in Symfony 5.
 * Propel expectations on DateTime validation are detaching starting with Symfony 5.
 */
class DateValidator extends SymfonyDateValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {

        if ($value instanceof \DateTimeInterface) {
            return;
        }

        parent::validate($value, $constraint);
    }
}
