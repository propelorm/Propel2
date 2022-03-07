<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * Information about unique columns of a table. This class assumes
 * that in the underlying RDBMS, unique constraints and unique indices
 * are roughly equivalent. For example, adding a unique constraint to
 * a column also creates an index on that column (this is known to be
 * true for MySQL and Oracle).
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Unique extends Index
{
    /**
     * Returns whether this index is unique.
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        return true;
    }
}
