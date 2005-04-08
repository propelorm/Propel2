<?php
/*
 *  $Id: DBMySQL.php,v 1.3 2004/10/30 17:48:49 micha Exp $
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

require_once 'propel/adapter/DBAdapter.php';

/**
 * This is used in order to connect to a MySQL database.
 *
 * @author Kaspars Jaudzems <kasparsj@navigators.lv> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jon S. Stevens <jon@clearink.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version $Revision: 1.3 $
 */
class DBMySQL extends DBAdapter
{
  /**
  * This method is used to ignore case.
  *
  * @param in The string to transform to upper case.
  * @return The upper case string.
  */
  function toUpperCase($in)
  {
    return "UPPER(" . $in . ")";
  }

  /**
  * This method is used to ignore case.
  *
  * @param in The string whose case to ignore.
  * @return The string in a case that can be ignored.
  */
  function ignoreCase($in)
  {
    return "UPPER(" . $in . ")";
  }
  
  /**
  * Returns SQL which concatenates the second string to the first.
  *
  * @param string String to concatenate.
  * @param string String to append.
  * @return string 
  */
  function concatString($s1, $s2)
  {
    return "CONCAT($s1, $s2)";
  }

  /**
  * Returns SQL which extracts a substring.
  *
  * @param string String to extract from.
  * @param int Offset to start from.
  * @param int Number of characters to extract.
  * @return string 
  */
  function subString($s, $pos, $len)
  {
    return "SUBSTRING($s, $pos, $len)";
  }

  /**
  * Returns SQL which calculates the length (in chars) of a string.
  *
  * @param string String to calculate length of.
  * @return string 
  */
  function strLength($s)
  {
      return "CHAR_LENGTH($s)";
  }

  /**
  * Locks the specified table.
  *
  * @param Connection $con The Creole connection to use.
  * @param string $table The name of the table to lock.
  * @return number of affected rows on success,
  * SQLException if no Statement could be created or executed.
  */
  function lockTable(/*Connection*/ &$con, $table)
  {
    $statement =& $con->createStatement();
    $sql = "LOCK TABLE " . $table . " WRITE";
    return $statement->executeUpdate($sql);
  }

  /**
  * Unlocks the specified table.
  *
  * @param Connection $con The Creole connection to use.
  * @param string $table The name of the table to unlock.
  * @throws SQLException No Statement could be created or
  * executed.
  */
  function unlockTable(/*Connection*/ &$con, $table)
  {
    $statement = $con->createStatement();
    return $statement->executeUpdate("UNLOCK TABLES");
  }

}
