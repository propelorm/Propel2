<?php
/*
 *  $Id: PeerInfo.php,v 1.6 2004/06/01 04:26:23 hlellelid Exp $
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

/**
 * Peer Helper Class
 *
 * Handle Dynamic Peer Access. Trying to solve the problems associated
 * with looking at constants, calling methods on static Peer Objects
 *
 * @author   David Giffin <david@giffin.org>
 * @copyright Copyright (c) 2000-2003 David Giffin : LGPL - See LICENCE
 * @package  propel.util
 */
class PeerInfo
{
    /** Propel Object Peers */
    private static $peers = array();    

    /** Reflection Objects of the Propel Peers */
    private static $reflections = array();    

    /** Table Maps of the Propel Peers */
    private static $maps = array();    


    /**
     * Add a Peer to the list of Peers
     * 
     * @param string $peer The Propel Peer to add
     */
     private static function addPeer($peer)
     {


        $peers = array_keys(self::$peers);

        if (!in_array($peer, $peers)) {

            self::$peers[$peer]       = self::loadPeer($peer);
            self::$reflections[$peer] = new reflectionClass($peer);
            self::$maps[$peer]        = null;

        }
    }  


    /**     
     * Get a constant from the Peer Reflector
     * 
     * @param  String The name of the constant
     * @return String The Constant String
     */
        public static function getPeerConstant($peer, $name)
        {
        self::addPeer($peer);
                return self::$reflections[$peer]->getConstant($name);
        }


    /**
     * Get a Peer from the Peer List
     *
     * @param string $peer The Propel Peer to add
     */
        public static function getPeer($peer) {
        self::addPeer($peer);
                return self::$peers[$peer];
        }    


    /**
     * Load a Peer
     *
     * You may wat to override this method if your Peers
     * are not in the include_path.
     *
     * @param string $peerName the name of the Peer
     */
    public static function loadPeer($peerName)
    {
        $peerFile = $peerName . ".php";
        require_once($peerFile);
        $peerObject = new $peerName();
        return $peerObject;
    }


    /**
     * Get a Column Constant from a Peer
     *
     * @param string The PhpName or DB_NAME for the constant
     * @return string the Column Constant
     */
    public static function getColumnConstant($peer, $name)
    {
        self::addPeer($peer);
        $map = self::getPeer($peer)->getPhpNameMap();
        foreach ($map as $phpName => $dbName) {
            if ($phpName == $name) {
                return self::getPeerConstant($peer, $dbName);                
            } else if ($dbName == $name) {
                return self::getPeerConstant($peer, $dbName);
            }
        }
        return null;
    }


    /**
     * Get the Primary Key for this Peer
     *
     * @param string $peer   The name of the Peer
     * @return string The name of the Primary Key
     */
    public static function getPrimaryKey($peer)
    {
        self::addPeer($peer);
        $tableMap = self::getTableMap($peer);
        $columns = $tableMap->getColumns();
        foreach ($columns as $columnName => $column) {
            if ($column->isPrimaryKey()) {
                return $columnName;
            }
        }
        return null;
    }


    /**
     * Get the Table Map for a Peer
     *
     * @param string $peer   The name of the Peer
     * @return TableMap The table map for this Peer
     */
    public static function getTableMap($peer)
    {        
        self::addPeer($peer);
        if (!self::$maps[$peer]) {
            $tableName = self::getTableName($peer);        
            $dbMap     = self::getPeer($peer)->getMapBuilder()->getDatabaseMap();
            self::$maps[$peer] = $dbMap->getTable($tableName);
        }
        return self::$maps[$peer];
    }


    public static function getTableName($peer)
    {
        self::addPeer($peer);
        return self::getPeerConstant($peer, "TABLE_NAME");
    }


    /**
     * Call a Method from the Static Peer Class
     *
     * @param string $peer   The name of the Peer
     * @param string $method The name of the method to call
     * @param array  $params The parameters to pass to the method
     * @return mixed What ever the method returns
     */
        public static function callMethod($peer, $method, $params = null)
        {
                if ($params !== null) {
                        return call_user_func_array(array($peer, $method), $params);
                }  
                return call_user_func(array($peer, $method));
        }

}

?>
