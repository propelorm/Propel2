<?php

/*
 *  $Id: Table.php,v 1.15 2005/03/16 03:57:54 hlellelid Exp $
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
 
require_once 'propel/engine/database/model/XMLElement.php';
include_once 'propel/engine/EngineException.php';
include_once 'propel/engine/database/model/IDMethod.php';
include_once 'propel/engine/database/model/NameFactory.php';
include_once 'propel/engine/database/model/Column.php';
include_once 'propel/engine/database/model/Unique.php';
include_once 'propel/engine/database/model/ForeignKey.php';
include_once 'propel/engine/database/model/IdMethodParameter.php';
include_once 'propel/engine/database/model/Validator.php';

/**
 * Data about a table used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @version $Revision: 1.15 $
 * @package propel.engine.database.model
 */
class Table extends XMLElement implements IDMethod {

    /** enables debug output */
    const DEBUG = false;

    //private attributes;
    private $columnList;
    private $validatorList;
    private $foreignKeys;
    private $indices;
    private $unices;
    private $idMethodParameters;
    private $name;
    private $description;
    private $phpName;
    private $idMethod;
    private $phpNamingMethod;
    private $tableParent;
    private $referrers = array();
    private $foreignTableNames;
    private $containsForeignPK;
    private $inheritanceColumn;
    private $skipSql;
    private $readOnly;
    private $abstractValue;
    private $alias;
    private $enterface;
    private $pkg;
    private $baseClass;
    private $basePeer;
    private $columnsByName;
    private $columnsByPhpName;
    private $needsTransactionInPostgres;//maybe this can be retrieved from vendorSpecificInfo?
    private $heavyIndexing;
    private $forReferenceOnly;
    private $isTree;
    private $vendorSpecificInfo = array();

    /**
     * Constructs a table object with a name
     *
     * @param string $name table name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
        $this->columnList = array();
        $this->validatorList = array();
        $this->foreignKeys = array();
        $this->indices = array();
        $this->unices = array();
        $this->columnsByName = array();
        $this->columnsByPhpName = array();
        $this->vendorSpecificInfo = array();
    }

   /**
     * Sets up the Rule object based on the attributes that were passed to loadFromXML().
	 * @see parent::loadFromXML()
     */
    public function setupObject()
    {
        $this->name = $this->getAttribute("name");
        $this->phpName = $this->getAttribute("phpName");
        $this->idMethod = $this->getAttribute("idMethod", $this->getDatabase()->getDefaultIdMethod());

        // retrieves the method for converting from specified name to a PHP name.
        $this->phpNamingMethod = $this->getAttribute("phpNamingMethod", $this->getDatabase()->getDefaultPhpNamingMethod());
                
        $this->skipSql = $this->booleanValue($this->getAttribute("skipSql"));
        $this->readOnly = $this->booleanValue($this->getAttribute("readOnly"));
        
        $this->pkg = $this->getAttribute("package");
        $this->abstractValue = $this->booleanValue($this->getAttribute("abstract"));
        $this->baseClass = $this->getAttribute("baseClass");
        $this->basePeer = $this->getAttribute("basePeer");
        $this->alias = $this->getAttribute("alias");
		
        $this->heavyIndexing = ( $this->booleanValue($this->getAttribute("heavyIndexing"))
                || ("false" !== $this->getAttribute("heavyIndexing")
                		&& $this->getDatabase()->isHeavyIndexing() ) );
        $this->description = $this->getAttribute("description");
        $this->enterface = $this->getAttribute("interface"); // sic ('interface' is reserved word)
        $this->isTree = $this->booleanValue($this->getAttribute("isTree"));
    }

    /**
     * <p>A hook for the SAX XML parser to call when this table has
     * been fully loaded from the XML, and all nested elements have
     * been processed.</p>
     *
     * <p>Performs heavy indexing and naming of elements which weren't
     * provided with a name.</p>
     */
    public function doFinalInitialization()
    {
        // Heavy indexing must wait until after all columns composing
        // a table's primary key have been parsed.
        if ($this->heavyIndexing) {
            $this->doHeavyIndexing();
        }

        // Name any indices which are missing a name using the
        // appropriate algorithm.
        $this->doNaming();

        // if idMethod is "native" and in fact there are no autoIncrement
        // columns in the table, then change it to "none"
        if ($this->getIdMethod() === IDMethod::NATIVE) {
            $anyAutoInc = false;
            foreach($this->getColumns() as $col) {
                if ($col->isAutoIncrement()) {
                    $anyAutoInc = true;
                    break;
                }
            }
            if (!$anyAutoInc) {
                $this->setIdMethod(IDMethod::NO_ID_METHOD);
            }
        }
    }

    /**
     * <p>Adds extra indices for multi-part primary key columns.</p>
     *
     * <p>For databases like MySQL, values in a where clause much
     * match key part order from the left to right.  So, in the key
     * definition <code>PRIMARY KEY (FOO_ID, BAR_ID)</code>,
     * <code>FOO_ID</code> <i>must</i> be the first element used in
     * the <code>where</code> clause of the SQL query used against
     * this table for the primary key index to be used.  This feature
     * could cause problems under MySQL with heavily indexed tables,
     * as MySQL currently only supports 16 indices per table (i.e. it
     * might cause too many indices to be created).</p>
     *
     * <p>See <a href="http://www.mysql.com/doc/E/X/EXPLAIN.html">the
     * manual</a> for a better description of why heavy indexing is
     * useful for quickly searchable database tables.</p>
     */
    private function doHeavyIndexing()
    {
        if (self::DEBUG) {
            print("doHeavyIndex() called on table " . $this->name."\n");
        }

        $pk = $this->getPrimaryKey();
        $size = count($pk);

        try {
            // We start at an offset of 1 because the entire column
            // list is generally implicitly indexed by the fact that
            // it's a primary key.
            for ($i=1; $i < $size; $i++) {
                $this->addIndex(new Index($this, array_slice($pk, $i, $size)));
            }
        } catch (EngineException $e) {
            print $e->getMessage() . "\n";
            print $e->getTraceAsString();
        }
    }

    /**
     * Names composing objects which haven't yet been named.  This
     * currently consists of foreign-key and index entities.
     */
    private function doNaming() {

        // Assure names are unique across all databases.
        try {
            for ($i=0, $size = count($this->foreignKeys); $i < $size; $i++) {
                $fk = $this->foreignKeys[$i];
                $name = $fk->getName();
                if (empty($name)) {
                    $name = $this->acquireConstraintName("FK", $i + 1);
                    $fk->setName($name);
                }
            }

            for ($i = 0, $size = count($this->indices); $i < $size; $i++) {
                $index = $this->indices[$i];
                $name = $index->getName();
                if (empty($name)) {
                    $name = $this->acquireConstraintName("I", $i + 1);
                    $index->setName($name);
                }
            }

            // NOTE: Most RDBMSes can apparently name unique column
            // constraints/indices themselves (using MySQL and Oracle
            // as test cases), so we'll assume that we needn't add an
            // entry to the system name list for these.
        } catch (EngineException $nameAlreadyInUse) {
            print $nameAlreadyInUse->getMessage() . "\n";
            print $nameAlreadyInUse->getTraceAsString();
        }
    }

    /**
     * Macro to a constraint name.
     *
     * @param nameType constraint type
     * @param nbr unique number for this constraint type
     * @return unique name for constraint
     * @throws EngineException
     */
    private function acquireConstraintName($nameType, $nbr)
    {
        $inputs = array();
        $inputs[] = $this->getDatabase();
        $inputs[] = $this->getName();
        $inputs[] = $nameType;
        $inputs[] = $nbr;
        return NameFactory::generateName(NameFactory::CONSTRAINT_GENERATOR, $inputs);
    }

    /**
     * Gets the value of base class for classes produced from this table.
     *
     * @return The base class for classes produced from this table.
     */
    public function getBaseClass()
    {
        if ($this->isAlias() && $this->baseClass === null) {
            return $this->alias;
        } elseif ($this->baseClass === null) {
            return $this->getDatabase()->getBaseClass();
        } else {
            return $this->baseClass;
        }
    }

    /**
     * Set the value of baseClass.
     * @param v  Value to assign to baseClass.
     */
    public function setBaseClass($v)
    {
        $this->baseClass = $v;
    }

    /**
     * Get the value of basePeer.
     * @return value of basePeer.
     */
    public function getBasePeer()
    {
        if ($this->isAlias() && $this->basePeer === null) {
            return $this->alias . "Peer";
        } elseif ($this->basePeer === null) {
            return $this->getDatabase()->getBasePeer();
        } else {
            return $this->basePeer;
        }
    }

    /**
     * Set the value of basePeer.
     * @param v  Value to assign to basePeer.
     */
    public function setBasePeer($v)
    {
        $this->basePeer = $v;
    }

    /**
     * A utility function to create a new column from attrib and add it to this
     * table.
     *
     * @param $coldata xml attributes or Column class for the column to add
     * @return the added column
     */
    public function addColumn($data)
    {
        if ($data instanceof Column) {
            $col = $data; // alias
            $col->setTable($this);
            if ($col->isInheritance()) {
                $this->inheritanceColumn = $col;
            }
            $this->columnList[] = $col;
            $this->columnsByName[$col->getName()] = $col;
            $this->columnsByPhpName[$col->getPhpName()] = $col;
            $col->setPosition(count($this->columnList));
            $this->needsTransactionInPostgres |= $col->requiresTransactionInPostgres();
            return $col;
        } else {
            $col = new Column();
            $col->setTable($this);
            $col->loadFromXML($data);
            return $this->addColumn($col); // call self w/ different param
        }
    }

   /**
    * Add a validator to this table.
    *
    * Supports two signatures:
    * - addValidator(Validator $validator)
    * - addValidator(array $attribs)
    *
    * @param mixed $data Validator object or XML attribs (array) from <validator /> element.
    * @return Validator The added Validator.
    * @throws EngineException
    */
   public function addValidator($data)
   {
     if ($data instanceof Validator)
     {
      $validator = $data;
      $col = $this->getColumn($validator->getColumnName());
      if($col == null) {
        throw new EngineException("Failed adding validator to table '" . $this->getName() .
          "': column '" . $validator->getColumnName() . "' does not exist !");
      }
      $validator->setColumn($col);
	  $validator->setTable($this);
      $this->validatorList[] = $validator;
      return $validator;
     }
     else
     {
      $validator = new Validator();
	  $validator->setTable($this);
      $validator->loadFromXML($data);
      return $this->addValidator($validator);
     }
   }

    /**
     * A utility function to create a new foreign key
     * from attrib and add it to this table.
     */
    public function addForeignKey($fkdata)
    {
        if ($fkdata instanceof ForeignKey) {
            $fk = $fkdata;
            $fk->setTable($this);
            $this->foreignKeys[] = $fk;

            if ($this->foreignTableNames === null) {
                $this->foreignTableNames = array();
            }
            if (!in_array($fk->getForeignTableName(), $this->foreignTableNames)) {
                $this->foreignTableNames[] = $fk->getForeignTableName();
            }
            return $fk;
        } else {
            $fk = new ForeignKey();
            $fk->loadFromXML($fkdata);
            return $this->addForeignKey($fk);
        }
    }

    /**
     * Gets the column that subclasses of the class representing this
     * table can be produced from.
     * @return string
     */
    public function getChildrenColumn()
    {
        return $this->inheritanceColumn;
    }

    /**
     * Get the subclasses that can be created from this table.
     * @return array string[] Class names
     */
    public function getChildrenNames()
    {
        if ($this->inheritanceColumn === null
                || !$this->inheritanceColumn->isEnumeratedClasses()) {
            return null;
        }
        $children = $this->inheritanceColumn->getChildren();
        $names = array();
        for ($i = 0, $size=count($children); $i < $size; $i++) {
            $names[] = get_class($children[$i]);
        }
        return $names;
    }

    /**
     * Adds the foreign key from another table that refers to this table.
     */
    public function addReferrer(ForeignKey $fk)
    {
        if ($this->referrers === null) {
            $this->referrers = array();
        }
        $this->referrers[] = $fk;
    }

    /**
     * Get list of references to this table.
     */
    public function getReferrers()
    {
        return $this->referrers;
    }

    /**
     * Set whether this table contains a foreign PK
     */
    public function setContainsForeignPK($b)
    {
        $this->containsForeignPK = (boolean) $b;
    }

    /**
     * Determine if this table contains a foreign PK
     */
    public function getContainsForeignPK()
    {
        return $this->containsForeignPK;
    }

    /**
     * A list of tables referenced by foreign keys in this table
     */
    public function getForeignTableNames()
    {
        if ($this->foreignTableNames === null) {
            $this->foreignTableNames = array();
        }
        return $this->foreignTableNames;
    }

    /**
     * Return true if the column requires a transaction in Postgres
     */
    public function requiresTransactionInPostgres()
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * A utility function to create a new id method parameter
     * from attrib or object and add it to this table.
     */
    public function addIdMethodParameter($impdata)
    {
        if ($impdata instanceof IdMethodParameter) {
            $imp = $impdata;
            $imp->setTable($this);
            if ($this->idMethodParameters === null) {
                $this->idMethodParameters = array();
            }
            $this->idMethodParameters[] = $imp;
            return $imp;
        } else {
            $imp = new IdMethodParameter();
            $imp->loadFromXML($impdata);
            return $this->addIdMethodParameter($imp); // call self w/ diff param
        }
    }

    /**
     * Adds a new index to the index list and set the
     * parent table of the column to the current table
     */
    public function addIndex($idxdata)
    {
        if ($idxdata instanceof Index) {
            $index = $idxdata;
            $index->setTable($this);
            $index->getName(); // we call this method so that the name is created now if it doesn't already exist.
            $this->indices[] = $index;
            return $index;
        } else {
            $index = new Index($this);
            $index->loadFromXML($idxdata);
            return $this->addIndex($index);
        }
    }

    /**
     * Adds a new Unique to the Unique list and set the
     * parent table of the column to the current table
     */
    public function addUnique($unqdata)
    {
        if ($unqdata instanceof Unique) {
            $unique = $unqdata;
            $unique->setTable($this);
            $this->unices[] = $unique;
            return $unique;
        } else {
            $unique = new Unique();
            $unique->loadFromXML($unqdata);
            return $this->addUnique($unique);
        }
    }

    /**
     * Get the name of the Table
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the Table
     */
    public function setName($newName)
    {
        $this->name = $newName;
    }

    /**
     * Get the description for the Table
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description for the Table
     *
     * @param newDescription description for the Table
     */
    public function setDescription($newDescription)
    {
        $this->description = $newDescription;
    }

    /**
     * Get name to use in PHP sources
     * @return string
     */
    public function getPhpName()
    {
        if ($this->phpName === null) {
            $inputs = array();
            $inputs[] = $this->name;
            $inputs[] = $this->phpNamingMethod;
            try {
                $this->phpName = NameFactory::generateName(NameFactory::PHP_GENERATOR, $inputs);
            } catch (EngineException $e) {
                print $e->getMessage() . "\n";
                print $e->getTraceAsString();
            }
        }
        return $this->phpName;
    }

    /**
     * Set name to use in PHP sources
     * @param string $phpName
     */
    public function setPhpName($phpName)
    {
        $this->phpName = $phpName;
    }

    /**
     * Get the method for generating pk's
     * [HL] changing behavior so that Database default
     *        method is returned if no method has been specified
     *        for the table.
     * @return string
     */
    public function getIdMethod()
    {
        if ($this->idMethod === null) {
            return IDMethod::NO_ID_METHOD;
        } else {
            return $this->idMethod;
        }
    }

    /**
     * Set the method for generating pk's
     */
    public function setIdMethod($idMethod)
    {
        $this->idMethod = $idMethod;
    }

    /**
     * Skip generating sql for this table (in the event it should
     * not be created from scratch).
     * @return boolean Value of skipSql.
     */
    public function isSkipSql()
    {
        return ($this->skipSql || $this->isAlias() || $this->isForReferenceOnly());
    }

    /**
     * Is table read-only, in which case only accessors (and relationship setters)
     * will be created.
     * @return boolan Value of readOnly.
     */
    public function isReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Set whether this table should have its creation sql generated.
     * @param boolean $v Value to assign to skipSql.
     */
    public function setSkipSql($v)
    {
        $this->skipSql = $v;
    }

    /**
     * PhpName of om object this entry references.
     * @return value of external.
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Is this table specified in the schema or is there just
     * a foreign key reference to it.
     * @return value of external.
     */
    public function isAlias()
    {
        return ($this->alias !== null);
    }

    /**
     * Set whether this table specified in the schema or is there just
     * a foreign key reference to it.
     * @param v  Value to assign to alias.
     */
    public function setAlias($v)
    {
        $this->alias = $v;
    }


    /**
     * Interface which objects for this table will implement
     * @return value of interface.
     */
    public function getInterface()
    {
        return $this->enterface;
    }

    /**
     * Interface which objects for this table will implement
     * @param v  Value to assign to interface.
     */
    public function setInterface($v)
    {
        $this->enterface = $v;
    }

    /**
     * When a table is abstract, it marks the business object class that is
     * generated as being abstract. If you have a table called "FOO", then the
     * Foo BO will be <code>public abstract class Foo</code>
     * This helps support class hierarchies
     *
     * @return value of abstractValue.
     */
    public function isAbstract()
    {
        return $this->abstractValue;
    }

    /**
     * When a table is abstract, it marks the business object
     * class that is generated as being abstract. If you have a
     * table called "FOO", then the Foo BO will be
     * <code>public abstract class Foo</code>
     * This helps support class hierarchies
     *
     * @param v  Value to assign to abstractValue.
     */
    public function setAbstract($v)
    {
        $this->abstractValue = (boolean) $v;
    }

    /**
     * Get the value of package.
     * @return value of package.
     */
    public function getPackage()
    {
        return $this->pkg;
    }

    /**
     * Set the value of package.
     * @param v  Value to assign to package.
     */
    public function setPackage($v)
    {
        $this->pkg = $v;
    }

    /**
     * Returns an Array containing all the columns in the table
     */
    public function getColumns()
    {
        return $this->columnList;
    }

    /**
     * Utility method to get the number of columns in this table
     */
    public function getNumColumns()
    {
        return count($this->columnList);
    }

    /**
     * Utility method to get the number of columns in this table
     */
    public function getNumLazyLoadColumns()
    {
        $count = 0;
        foreach($this->columnList as $col) {
            if ($col->isLazyLoad()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Returns an Array containing all the validators in the table
     */
    public function getValidators()
    {
      return $this->validatorList;
    }

    /**
     * Returns an Array containing all the FKs in the table
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Returns a Collection of parameters relevant for the chosen
     * id generation method.
     */
    public function getIdMethodParameters()
    {
        return $this->idMethodParameters;
    }

    /**
     * A name to use for creating a sequence if one is not specified.
     */
    public function getSequenceName()
    {
        $result = null;
        if ($this->getIdMethod() == self::NATIVE) {
            $idMethodParams = $this->getIdMethodParameters();
            if ($idMethodParams === null) {
                $result = $this->getName() . "_SEQ";
            } else {
                $result = $idMethodParams[0]->getValue();
            }
        }
        return $result;
    }

    /**
     * Returns an Array containing all the FKs in the table
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Returns an Array containing all the UKs in the table
     */
    public function getUnices()
    {
        return $this->unices;
    }

    /**
     * Returns a specified column.
     * @return Return a Column object or null if it does not exist.
     */
    public function getColumn($name)
    {
        return @$this->columnsByName[$name];
    }

    /**
     * Returns a specified column.
     * @return Return a Column object or null if it does not exist.
     */
    public function getColumnByPhpName($phpName)
    {
        return @$this->columnsByPhpName[$phpName];
    }

    /**
     * Return the first foreign key that includes col in it's list
     * of local columns.  Eg. Foreign key (a,b,c) refrences tbl(x,y,z)
     * will be returned of col is either a,b or c.
     * @param string $col
     * @return Return a Column object or null if it does not exist.
     */
    public function getForeignKey($col)
    {
        $firstFK = null;
        for($i=0,$size=count($this->foreignKeys); $i < $size; $i++) {
            $key = $this->foreignKeys[$i];
            if (in_array($col, $key->getLocalColumns())) {
                if ($firstFK === null) {
                    $firstFK = $key;
                } else {
                    throw new EngineException($col . " has ben declared as a foreign key multiple times.  This is not"
                                       . " being handled properly. (Try moving foreign key declarations to the foreign table.)");
                }
            }
        }
        return $firstFK;
    }

    /**
     * Returns true if the table contains a specified column
     * @param mixed $col Column or column name.
     */
    public function containsColumn($col)
    {
        if ($col instanceof Column) {
            return in_array($col, $this->columnList);
        } else {
            return ($this->getColumn($col) !== null);
        }
    }

    /**
     * Set the parent of the table
     *
     * @param parent the parant database
     */
    public function setDatabase($parent)
    {
        $this->tableParent = $parent;
    }

    /**
     * Get the parent of the table
     *
     * @return the parant database
     */
    public function getDatabase()
    {
        return $this->tableParent;
    }

    /**
     * Flag to determine if code/sql gets created for this table.
     * Table will be skipped, if return true.
     * @return value of forReferenceOnly.
     */
    public function isForReferenceOnly()
    {
        return $this->forReferenceOnly;
    }

    /**
     * Flag to determine if code/sql gets created for this table.
     * Table will be skipped, if set to true.
     * @param v  Value to assign to forReferenceOnly.
     */
    public function setForReferenceOnly($v)
    {
        $this->forReferenceOnly = (boolean) $v;
    }

   /**
     * Flag to determine if tree node class should be generated for this table.
     * @return valur of isTree
    */
   public function isTree()
   {
        return $this->isTree;
   }

    /**
     * Flag to determine if tree node class should be generated for this table.
     * @param v  Value to assign to isTree.
     */
    public function setIsTree($v)
    {
        $this->isTree = (boolean) $v;
    }

    /**
     * Sets vendor specific parameter
     */
    public function setVendorParameter($name, $value)
    {
        $this->vendorSpecificInfo[$name] = $value;
    }

    /**
     * Sets vendor specific information to a table.
     */
    public function setVendorSpecificInfo($info)
    {
        $this->vendorSpecificInfo = $info;
    }

    /**
     * Retrieves vendor specific information to an index.
     */
    public function getVendorSpecificInfo()
    {
        return $this->vendorSpecificInfo;
    }

    /**
     * Returns a XML representation of this table.
     *
     * @return XML representation of this table
     */
    public function toString() {

        $result = "<table name=\"" . $this->name . "\"";

        if ($this->phpName !== null) {
            $result .= " phpName=\""
                  . $this->phpName
                  . '"';
        }

        if ($this->idMethod !== null) {
            $result .= " idMethod=\""
                  . $this->idMethod
                  . '"';
        }

        if ($this->skipSql) {
            $result .= " skipSql=\""
                  . ($this->skipSql ? "true" : "false")
                  . '"';
        }

        if ($this->abstractValue) {
            $result .= " abstract=\""
                  . ($abstractValue ? "true" : "false")
                  . '"';
        }

        if ($this->baseClass !== null) {
            $result .= " baseClass=\""
                  . $this->baseClass
                  . '"';
        }

        if ($this->basePeer !== null) {
            $result .= " basePeer=\""
                  . $this->basePeer
                  . '"';
        }

        $result .= ">\n";

        if ($this->columnList !== null) {
            for($i=0,$_i=count($this->columnList); $i < $_i; $i++) {
                $result .= $this->columnList[$i]->toString();
            }
        }

        if ($this->foreignKeys !== null) {
            for($i=0,$_i=count($this->foreignKeys); $i < $_i; $i++) {
                $result .= $this->foreignKeys[$i]->toString();
            }
        }

        if ($this->idMethodParameters !== null) {
            for($i=0,$_i=count($this->idMethodParameters); $i < $_i; $i++) {
                $result .= $this->idMethodParameters[$i]->toString();
            }
        }

        $result .= "</table>\n";

        return $result;
    }

    /**
     * Returns the collection of Columns which make up the single primary
     * key for this table.
     *
     * @return array A list of the primary key parts.
     */
    public function getPrimaryKey()
    {
        $pk = array();
        for($i=0,$_i=count($this->columnList); $i < $_i; $i++) {
            $col = $this->columnList[$i];
            if ($col->isPrimaryKey()) {
                $pk[] = $col;
            }
        }
        return $pk;
    }

    /**
     * Determine whether this table has a primary key.
     *
     * @return Whether this table has any primary key parts.
     */
    public function hasPrimaryKey()
    {
        return (count($this->getPrimaryKey()) > 0);
    }

    /**
     * Returns all parts of the primary key, separated by commas.
     *
     * @return A CSV list of primary key parts.
     */
    public function printPrimaryKey()
    {
        return $this->printList($this->columnList);
    }

    /**
     * Returns the elements of the list, separated by commas.
     * @param array $list
     * @return A CSV list.
     */
    private function printList($list){
        $result = "";
        $comma = 0;
        for($i=0,$_i=count($list); $i < $_i; $i++) {
            $col = $list[$i];
            if ($col->isPrimaryKey()) {
                $result .= ($comma++ ? ',' : '') . $col->getName();
            }
        }
        return $result;
    }
}