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

require_once 'propel/engine/builder/DataModelBuilder.php';

/**
 * Baseclass for OM-building classes.
 * 
 * OM-building classes are those that build a PHP (or other) class to service
 * a single table.  This includes Peer classes, Entity classes, Map classes, 
 * Node classes, etc.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.om
 */
abstract class OMBuilder extends DataModelBuilder {	

	/**
	 * Builds the PHP source for current class and returns it as a string.
	 * 
	 * This is the main entry point and defines a basic structure that classes should follow. 
	 * In most cases this method will not need to be overridden by subclasses.  This method 
	 * does assume that the output language is PHP code, so it will need to be overridden if 
	 * this is not the case.
	 * 
	 * @return string The resulting PHP sourcecode.
	 */
	public function build()
	{
		$script = "<" . "?php\n"; // intentional concatenation		
		$this->addIncludes($script);
		$this->addClassOpen($script);
		$this->addClassBody($script);	
		$this->addClassClose($script);
		return $script;
	}

	/**
	 * Gets package name for this table.
	 * @return string
	 */
	protected function getPackage()
	{
		$pkg = $this->getDatabase()->getPackage();
		if (!$pkg) {
		    $pkg = $this->getBuildProperty('targetPackage');
		}
		return $pkg;
	}
	
	protected function getMapPackage()
	{
		return $this->getPackage() . ".map";
	}
	
	protected function getBasePackage()
	{
		return $this->getPackage() . ".om";
	}
	
	/**
	 * Returns the peer classname for current table.
	 * @return string (e.g. 'MyPeer')
	 */
	public function getPeerClassname($phpName = null) {
		if ($phpName !== null) {
			$e = new Exception("getPeerClassname() called with param.");
			print $e;
			throw $e;
		}
		return $this->getTable()->getPhpName() . 'Peer';
	}
	
	/**
	 * Returns the object classname for current table.
	 * @return string (e.g. 'MyPeer')
	 */
	public function getObjectClassname() {
		return $this->getTable()->getPhpName();
	}
	
	/** 
	 * Get the column constant name (e.g. PeerName::COLUMN_NAME).
     * 
     * @param Column $col The column we need a name for.
     * @param string $phpName The PHP Name of the peer class. The 'Peer' is appended automatically.
     * 
     * @return string If $phpName is provided, then will return {$phpName}Peer::COLUMN_NAME; if not, then uses current table COLUMN_NAME.
     */
    public function getColumnConstant(Column $col, $phpName = null)
	{
		$classname = $this->getPeerClassname($phpName);
		
        // was it overridden in schema.xml ?
        if ($col->getPeerName()) {
            $const = strtoupper($col->getPeerName());
        } else {
            $const = strtoupper($col->getName());
        }
		return $classname.'::'.$const;
    }
	
	/**
     * Gets just classname, given a dot-path to class.
     * @param string $qualifiedName
     * @return string
     */
    public function classname($qualifiedName)
    {
        $pos = strrpos($qualifiedName, '.');
        if ($pos === false) { 
            return $qualifiedName;  // there is no '.' in the qualifed name
        } else {
            return substr($qualifiedName, $pos + 1); // start just after '.'
        }
    }
	
	/**
     * Gets the basePeer path if specified for table/db.  
     * If not, will return 'propel.util.BasePeer'
     * @return string
     */
    public function getBasePeer(Table $table) {
        $class = $table->getBasePeer();
        if ($class === null) {
            $class = "propel.util.BasePeer";
        }
        return $class;
    }	
		
}