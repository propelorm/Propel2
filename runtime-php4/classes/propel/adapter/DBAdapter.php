<?php
/*
 * $Id: DBAdapter.php,v 1.6 2004/11/29 14:08:08 micha Exp $
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

include_once 'creole/Connection.php';

/**
 * <code>DBAdapter</code> defines the interface for a Propel database
 * adapter.  Support for new databases is added by subclassing
 * <code>DBAdapter</code> and implementing its abstract interface, and by
 * registering the new database adapter and corresponding Creole
 * driver in the private adapters array in this class.
 *
 * <p>The Propel database adapters exist to present a uniform
 * interface to database access across all available databases.  Once
 * the necessary adapters have been written and configured,
 * transparent swapping of databases is theoretically supported with
 * <i>zero code changes</i> and minimal configuration file
 * modifications.
 *
 * <p>This class also replaces Torque's DBFactory class.  Use the <code>DBAdapter::factory($creoleDriver)</code>
 * method to get the correct Propel DBAdapter adapter, given a Creole driver.
 *
 * @author Kaspars Jaudzems <kasparsj@navigators.lv> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Michael Aichler <aichler@mediacluster.de> (Propel)
 * @author Jon S. Stevens <jon@latchkey.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version $Revision: 1.6 $
 */
class DBAdapter
{
  /**
  * Creole driver to Propel adapter map.
  * @var array
  */
  var $adapters = array
    (
      ''       => 'DBNone',
      'mysql'  => 'DBMySQL',
      'mssql'  => 'DBMSSQL',
      'pgsql'  => 'DBPostgres',
      'sqlite' => 'DBSQLite',
    );

  /**
  * Creates a new instance of the database adapter associated
  * with the specified Creole driver.
  *
  * @param string $driver The name of the Creole driver to
  * create a new adapter instance for or a shorter form adapter key.
  * @return DBAdapter An instance of a Turbine database adapter.
  * @throws Exception if the Creole driver could not be instantiated.
  */
  function & factory($driver)
  {
    $self =& DBAdapter::getInstance();
    $adapterClass = null;

    if (isset($self->adapters["$driver"])) {
        $adapterClass =& $self->adapters["$driver"];
    }

    if ($adapterClass !== null) {
      require_once 'propel/adapter/' . $adapterClass . '.php';
      $a = new $adapterClass();
      return $a;
    }
    else {
      return new Exception(PROPEL_ERROR_NOT_FOUND, "Unknown Creole driver: " . $driver . ": Check your configuration file");
    }
  }

  /**
  * This method is used to ignore case.
  *
  * @param in The string to transform to upper case.
  * @return string The upper case string.
  */
  function toUpperCase($in)
  {
    trigger_error(
      "DBAdapter::toUpperCase(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * Returns the character used to indicate the beginning and end of
  * a piece of text used in a SQL statement (generally a single
  * quote).
  *
  * @return string The text delimeter.
  */
  function getStringDelimiter()
  {
    return '\'';
  }

  /**
  * Locks the specified table.
  *
  * @param Connection $con The Creole connection to use.
  * @param string $table The name of the table to lock.
  * @return void
  * @throws SQLException No Statement could be created or executed.
  */
  function lockTable(/*Connection*/ &$con, $table)
  {
    trigger_error(
      "DBAdapter::toUpperCase(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * Unlocks the specified table.
  *
  * @param Connection $con The Creole connection to use.
  * @param string $table The name of the table to unlock.
  * @return void
  * @throws SQLException No Statement could be created or executed.
  */
  function unlockTable(/*Connection*/ &$con, $table)
  {
    trigger_error(
      "DBAdapter::toUpperCase(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * This method is used to ignore case.
  *
  * @param string $in The string whose case to ignore.
  * @return string The string in a case that can be ignored.
  */
  function ignoreCase($in)
  {
    trigger_error(
      "DBAdapter::toUpperCase(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * This method is used to ignore case in an ORDER BY clause.
  * Usually it is the same as ignoreCase, but some databases
  * (Interbase for example) does not use the same SQL in ORDER BY
  * and other clauses.
  *
  * @param string $in The string whose case to ignore.
  * @return string The string in a case that can be ignored.
  */
  function ignoreCaseInOrderBy($in)
  {
      return $this->ignoreCase($in);
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
    trigger_error(
      "DBAdapter::concatString(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
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
    trigger_error(
      "DBAdapter::subString(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * Returns SQL which calculates the length (in chars) of a string.
  *
  * @param string String to calculate length of.
  * @return string 
  */
  function strLength($s) 
  {
    trigger_error(
      "DBAdapter::strLength(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  function & getInstance()
  {
    static $instance;

    if ($instance === null)
      $instance = new DBAdapter();

    return $instance;
  }

}
