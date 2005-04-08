<?php
/*
 *  $Id: AppData.php,v 1.2 2004/07/28 16:26:30 hlellelid Exp $
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

include_once 'propel/engine/EngineException.php';
include_once 'propel/engine/database/model/Database.php';

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque) 
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version $Revision: 1.2 $
 * @package propel.engine.database.model
 */
class AppData {

    /**
     * The list of databases for this application.
     * @var array Database[]
     */
    private $dbList = array();
  
    /**
     * The type for our databases.
     * @var string
     */
    private $databaseType;

    /**
     * Name of the database. Only one database definition
     * is allowed in one XML descriptor.
     */
    private $name;

    /** 
     * Flag to ensure that initialization is performed only once. 
     * @var boolean
     */
    private $isInitialized = false;

    /**
     * Creates a new instance for the specified database type.
     *
     * @param databaseType The default type for any databases added to
     * this application model.
     */
    public function __construct($databaseType)
    {
        $this->databaseType = $databaseType;
    }
    
    /**
     * Set the name of the database.
     *
     * @param name of the database.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the database.
     *
     * @return String name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the short name of the database (without the '-schema' postfix).
     *
     * @return String name
     */
    public function getShortName()
    {
        return str_replace("-schema", "", $name);
    }

    /**
     * Return an array of all databases
     *
     * @return Array of Database objects
     */
    public function getDatabases()
    {
        $this->doFinalInitialization();
        return $this->dbList;
    }

    /**
     * Returns whether this application has multiple databases.
     *
     * @return boolean True if the application has multiple databases
     */
    public function hasMultipleDatabases()
    {
        return (count($this->dbList) > 1);
    }

    /**
     * Return the database with the specified name.
     *
     * @param name database name
     * @return A Database object.  If it does not exist it returns null
     */
    public function getDatabase($name = null)
    {
        $this->doFinalInitialization();
        
        if ($name === null) {
            return $this->dbList[0];
        }
        
        for($i=0,$size=count($this->dbList); $i < $size; $i++) {
            $db = $this->dbList[$i];
            if ($db->getName() === $name) {
                return $db;
            }
        }
        return null;
    }

    /**
     * Add a database to the list and sets the AppData property to this
     * AppData
     *
     * @param db the database to add
     */
    public function addDatabase($db)
    {
        if ($db instanceof Database) {
            $db->setAppData($this);
            if ($db->getName() === null) {
                $db->setName(Propel::getDefaultDB());
            }
            if ($db->getDatabaseType() === null) {
                $db->setDatabaseType($this->databaseType);
            }
            $this->dbList[] = $db;
            return $db;
        } else {
            // XML attributes array / hash
            $d = new Database();
            $d->loadFromXML($db);
            return $this->addDatabase($d); // calls self w/ different param type
        }
        
    }
    
    /**
     * 
     * @return void
     */
    private function doFinalInitialization()
    {
        if (!$this->isInitialized) {
            for($i=0, $size=count($this->dbList); $i < $size; $i++) {
                $this->dbList[$i]->doFinalInitialization();
            }
            $this->isInitialized = true;
        }
    }

    /**
     * Creats a string representation of this AppData.
     * The representation is given in xml format.
     *
     * @return string Representation in xml format
     */
    public function toString()
    {
        $result = "<app-data>\n";
        for ($i=0,$size=count($this->dbList); $i < $size; $i++) {       
            $result .= $this->dbList[$i]->toString();
        }
        $result .= "</app-data>";
        return $result;
    }
}
