<?php

/*
 *  $Id: DBOracle.php,v 1.8 2004/09/01 14:25:28 dlawson_mi Exp $
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
 * Oracle adapter.
 * 
 * @author David Giffin <david@giffin.org> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jon S. Stevens <jon@clearink.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Bill Schneider <bschneider@vecna.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version $Revision: 1.8 $
 * @package propel.adapter
 */
class DBOracle extends DBAdapter {

    /**
     * This method is used to ignore case.
     *
     * @param string $in The string to transform to upper case.
     * @return string The upper case string.
     */
    public function toUpperCase($in)
    {
        return "UPPER(" . $in . ")";
    }

    /**
     * This method is used to ignore case.
     *
     * @param string $in The string whose case to ignore.
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCase($in)
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
    public function concatString($s1, $s2)
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
    public function subString($s, $pos, $len)
    {
        return "SUBSTR($s, $pos, $len)";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param string String to calculate length of.
     * @return string 
     */
    public function strLength($s)
    {
        return "LENGTH($s)";
    }
     
    /**
     * Locks the specified table.
     *
     * @param Connection $con The Creole connection to use.
     * @param string $table The name of the table to lock.
     * @throws SQLException No Statement could be created or executed.
     */
    public function lockTable(Connection $con, $table)
    {
        $statement = $con->createStatement();
        $statement->executeQuery("SELECT next_id FROM " . $table ." FOR UPDATE");
    }

    /**
     * Unlocks the specified table.
     *
     * @param Connection $con The Creole connection to use.
     * @param string $table The name of the table to unlock.
     * @throws SQLException - No Statement could be created or executed.
     */
    public function unlockTable(Connection $con, $table)
    {
        // Tables in Oracle are unlocked when a commit is issued.  The
        // user may have issued a commit but do it here to be sure.
        $con->commit();
    }    
}