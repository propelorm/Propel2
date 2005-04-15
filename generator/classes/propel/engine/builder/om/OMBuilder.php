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

require_once 'propel/engine/builder/om/DataModelBuilder.php';

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
    
    /**
     * Gets the baseClass path if specified for table/db.  
     * If not, will return 'propel.om.BaseObject'
     * @return string
     */
    public function getBaseClass(Table $table) {
        $class = $table->getBaseClass();
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
    public function getInterface(Table $table) {
        $interface = $table->getInterface();
        if ($interface === null) {
            $interface = "propel.om.Persistent";
        }
        return $interface;
    }

	
		
}