<?php
/*
 * $Id: DBSybase.php,v 1.2 2005/02/13 12:23:52 micha Exp $
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
 * This is used to connect to a Sybase database using Sybase's
 * Creole driver.
 *
 * <B>NOTE:</B><I>Currently JConnect does not implement the required
 * methods for ResultSetMetaData, and therefore the village API's may
 * not function.  For connection pooling, everything works.</I>
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Michael Aichler <aichler@mediacluster.de> (Propel)
 * @author Jeff Brekke <ekkerbj@netscape.net> (Torque)
 * @version $Revision: 1.2 $
 * @package propel.adapter
 */
class DBSybase extends DBAdapter
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
    return "($s1 + $s2)";
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
    return "LEN($s)";
  }

  /**
  * Locks the specified table.
  *
  * @param Connection $con The Creole connection to use.
  * @param string $table The name of the table to lock.
  * @throws SQLException No Statement could be created or executed.
  */
  function lockTable(&$con, $table)
  {
    Propel::typeHint($con, 'Connection', 'DBSybase', 'lockTable');

    $statement =& $con->createStatement();
    $sql = "SELECT next_id FROM " . $table . " FOR UPDATE";

    return $statement->executeQuery($sql);
  }

  /**
  * Unlocks the specified table.
  *
  * @param Connection $con The Creole connection to use.
  * @param string $table The name of the table to unlock.
  * @throws SQLException No Statement could be created or executed.
  */
  function unlockTable(&$con, $table)
  {
    // Tables in Sybase are unlocked when a commit is issued.  The
    // user may have issued a commit but do it here to be sure.
    Propel::typeHint($con, 'Connection', 'DBSybase', 'unlockTable');
    return $con->commit();
  }

}