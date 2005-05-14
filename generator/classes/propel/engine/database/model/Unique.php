<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

include_once 'propel/engine/database/model/Index.php';

/**
 * Information about unique columns of a table.  This class assumes
 * that in the underlying RDBMS, unique constraints and unique indices
 * are roughly equivalent.  For example, adding a unique constraint to
 * a column also creates an index on that column (this is known to be
 * true for MySQL and Oracle).
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @version $Revision$
 * @package propel.engine.database.model
 */
class Unique extends Index {

		private $number;

    /**
     * Default constructor.
     */
    public function __construct(Table $table, $indexColumns = array())
    {
      // when uniques' names are generated, all uniques are already in place
      // this is why a number has to be generated to be able to generate
      // different names for multiple uniques in one table if no name is given
      $this->setNumber(count($table->getUnices()) + 1);
    }

    /**
     * Returns <code>true</code>.
     */
    public function isUnique()
    {
        return true;
    }

    /**
     * Sets a number for this unique that is used to identify the unique
     */
    public function setNumber($number)
    {
      $this->number = $number;
    }

    /**
     * Retrieves the identification number for this unique
     */
    public function getNumber()
    {
      return $this->number;
    }

    /**
     * String representation of the index. This is an xml representation.
     */
    public function toString()
    {
        $result = " <unique name=\"" . $this->getName() . "\">\n";        
        $columns = $this->getColumns();
        for ($i=0, $size=count($columns); $i < $size; $i++) {
            $result .= "  <unique-column name=\""
                . $columns[$i]
                . "\"/>\n";
        }
        $result .= " </unique>\n";
        return $result;
    }
}
