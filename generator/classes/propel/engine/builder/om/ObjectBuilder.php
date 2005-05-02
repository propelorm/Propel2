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

require_once 'propel/engine/builder/om/OMBuilder.php';

/**
 * Base class for Peer-building classes.
 * 
 * This class is designed so that it can be extended by a PHP4PeerBuilder in addition
 * to the "standard" PHP5PeerBuilder and PHP5ComplexOMPeerBuilder.  Hence, this class
 * should not have any actual template code in it -- simply basic logic & utility 
 * methods.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class ObjectBuilder extends OMBuilder {
	
	/**
	 * Constructs a new PeerBuilder subclass.
	 */
	public function __construct(Table $table) {
		parent::__construct($table);
	}
		
	/**
	 * This method adds the contents of the generated class to the script.
	 * 
	 * This method contains the high-level logic that determines which methods
	 * get generated.
	 * 
	 * Hint: Override this method in your subclass if you want to reorganize or
	 * drastically change the contents of the generated peer class.
	 * 
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addClassBody(&$script)
	{

		$table = $this->getTable();

		if (!$table->isAlias()) {
			$this->addAttributes($script);
		}
	}
	
	/**
	 * Adds the getter methods for the column values.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addColumnAccessorMethods(&$script)
	{
		$table = $this->getTable();
		
		foreach ($table->getColumns() as $col) {
			
			if ($col->getType() === PropelTypes::DATE || $col->getType() === PropelTypes::TIME || $col->getType() === PropelTypes::TIMESTAMP) { 
				$this->addTemporalAccessor($script, $col);
			} else {
				$this->addGenericAccessor($script, $col);
			}
			if ($col->isLazyLoad()) {
			    $this->addLazyLoader($script, $col);
			}
						
		}
	}
	
	protected function addMutatorMethods()
	{
	
		foreach ($table->getColumns() as $col) {
			
			if ($col->isLob()) {
				$this->addLobMutator($script, $col);
			} elseif ($col->getType() === PropelTypes::DATE || $col->getType() === PropelTypes::TIME || $col->getType() === PropelTypes::TIMESTAMP) {
				$this->addTemporalMutator($script, $col);
			} else {
				$this->addDefaultMutator($script, $col);
			}
						
		}
	
	
	}
	
	
	/**
     * Gets the baseClass path if specified for table/db.  
     * If not, will return 'propel.om.BaseObject'
     * @return string
     */
    public static function getBaseClass() {
        $class = $this->getTable()->getBaseClass();
        if ($class === null) {
            $class = "propel.om.BaseObject";
        }
        return $class;
    }
	
	/**
     * Gets the interface path if specified for table.
     * If not, will return 'propel.om.Persistent'.
     * @return string
     */
    public static function getInterface(Table $table) {
        $interface = $table->getInterface();
        if ($interface === null && !$table->isReadOnly()) {
            $interface = "propel.om.Persistent";
        }
        return $interface;
    }
	
}