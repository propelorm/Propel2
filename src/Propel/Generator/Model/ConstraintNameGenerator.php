<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;

/**
 * A <code>NameGeneratorInterface</code> implementation for table-specific
 * constraints.  Conforms to the maximum column name length for the
 * type of database in use.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class ConstraintNameGenerator implements NameGeneratorInterface
{
    /**
     * Conditional compilation flag.
     */
    const DEBUG = false;

    /**
     * First element of <code>inputs</code> should be of type {@link Database}, second
     * should be a table name, third is the type identifier (spared if
     * trimming is necessary due to database type length constraints),
     * and the fourth is a <code>Integer</code> indicating the number
     * of this constraint.
     *
     * @see NameGenerator
     * @param  array           $inputs An array of input parameters
     * @return string
     * @throws EngineException
     */
    public function generateName($inputs)
    {
        $db            = $inputs[0];
        $name          = $inputs[1];
        $namePostfix   = $inputs[2];
        $constraintNbr = (string) $inputs[3];

        // Calculate maximum RDBMS-specific column character limit.
        try {
            $maxColumnNameLength = (int) $db->getMaxColumnNameLength();
            $maxBodyLength = ($maxColumnNameLength - strlen($namePostfix) - strlen($constraintNbr) - 2);
        } catch (EngineException $e) {
            throw $e;
        }

        // Do any necessary trimming.
        if ($maxBodyLength !== -1 && strlen($name) > $maxBodyLength) {
            $name = substr($name, 0, $maxBodyLength);
        }

        $name .= self::STD_SEPARATOR_CHAR . $namePostfix
            . self::STD_SEPARATOR_CHAR . $constraintNbr;

        return $name;
    }
}
