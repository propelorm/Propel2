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
 * Node classes, Nested Set classes, etc.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.om
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
	 * @return     string The resulting PHP sourcecode.
	 */
	public function build()
	{
		$this->validateModel();

		$script = "<" . "?php\n"; // intentional concatenation
		if ($this->isAddIncludes()) {
			$this->addIncludes($script);
		}
		$this->addClassOpen($script);
		$this->addClassBody($script);
		$this->addClassClose($script);
		return $script;
	}

	/**
	 * Validates the current table to make sure that it won't
	 * result in generated code that will not parse.
	 *
	 * This method may emit warnings for code which may cause problems
	 * and will throw exceptions for errors that will definitely cause
	 * problems.
	 */
	protected function validateModel()
	{
		// Validation is currently only implemented in the subclasses.
	}

	/**
	 * Creates a $obj = new Book(); code snippet. Can be used by frameworks, for instance, to
	 * extend this behavior, e.g. initialize the object after creating the instance or so.
	 *
	 * @return     string Some code
	 */
	public function buildObjectInstanceCreationCode($objName, $clsName)
	{
		return "$objName = new $clsName();";
	}

	/**
	 * Returns the qualified (prefixed) classname that is being built by the current class.
	 * This method must be implemented by child classes.
	 * @return     string
	 */
	abstract public function getUnprefixedClassname();

	/**
	 * Returns the prefixed clasname that is being built by the current class.
	 * @return     string
	 * @see        DataModelBuilder#prefixClassname()
	 */
	public function getClassname()
	{
		return $this->prefixClassname($this->getUnprefixedClassname());
	}
	/**
	 * Gets the dot-path representation of current class being built.
	 * @return     string
	 */
	public function getClasspath()
	{
		if ($this->getPackage()) {
			$path = $this->getPackage() . '.' . $this->getClassname();
		} else {
			$path = $this->getClassname();
		}
		return $path;
	}

	/**
	 * Gets the full path to the file for the current class.
	 * @return     string
	 */
	public function getClassFilePath()
	{
		return ClassTools::getFilePath($this->getPackage(), $this->getClassname());
	}

	/**
	 * Gets package name for this table.
	 * This is overridden by child classes that have different packages.
	 * @return     string
	 */
	public function getPackage()
	{
		$pkg = ($this->getTable()->getPackage() ? $this->getTable()->getPackage() : $this->getDatabase()->getPackage());
		if (!$pkg) {
			$pkg = $this->getBuildProperty('targetPackage');
		}
		return $pkg;
	}

	/**
	 * Returns filesystem path for current package.
	 * @return     string
	 */
	public function getPackagePath()
	{
		return strtr($this->getPackage(), '.', '/');
	}

	/**
	 * Shortcut method to return the [stub] peer classname for current table.
	 * This is the classname that is used whenever object or peer classes want
	 * to invoke methods of the peer classes.
	 * @return     string (e.g. 'MyPeer')
	 * @see        StubPeerBuilder::getClassname()
	 */
	public function getPeerClassname() {
		return $this->getStubPeerBuilder()->getClassname();
	}

	/**
	 * Returns the object classname for current table.
	 * This is the classname that is used whenever object or peer classes want
	 * to invoke methods of the object classes.
	 * @return     string (e.g. 'My')
	 * @see        StubPeerBuilder::getClassname()
	 */
	public function getObjectClassname() {
		return $this->getStubObjectBuilder()->getClassname();
	}

	/**
	 * Get the column constant name (e.g. PeerName::COLUMN_NAME).
	 *
	 * @param      Column $col The column we need a name for.
	 * @param      string $classname The Peer classname to use.
	 *
	 * @return     string If $classname is provided, then will return $classname::COLUMN_NAME; if not, then the peername is looked up for current table to yield $currTablePeer::COLUMN_NAME.
	 */
	public function getColumnConstant($col, $classname = null)
	{
		if ($col === null) {
			$e = new Exception("No col specified.");
			print $e;
			throw $e;
		}
		if ($classname === null) {
			return $col->getConstantName();
		}
		// was it overridden in schema.xml ?
		if ($col->getPeerName()) {
			$const = strtoupper($col->getPeerName());
		} else {
			$const = strtoupper($col->getName());
		}
		return $classname.'::'.$const;
	}

	/**
	 * Gets the basePeer path if specified for table/db.
	 * If not, will return 'propel.util.BasePeer'
	 * @return     string
	 */
	public function getBasePeer(Table $table) {
		$class = $table->getBasePeer();
		if ($class === null) {
			$class = "propel.util.BasePeer";
		}
		return $class;
	}

	/**
	 * Convenience method to get the foreign Table object for an fkey.
	 * @return     Table
	 */
	protected function getForeignTable(ForeignKey $fk)
	{
		return $this->getTable()->getDatabase()->getTable($fk->getForeignTableName());
	}

	/**
	 * Gets the PHP method name affix to be used for fkeys for the current table (not referrers to this table).
	 *
	 * The difference between this method and the getRefFKPhpNameAffix() method is that in this method the
	 * classname in the affix is the foreign table classname.
	 *
	 * @param      ForeignKey $fk The local FK that we need a name for.
	 * @param      boolean $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
	 * @return     string
	 */
	public function getFKPhpNameAffix(ForeignKey $fk, $plural = false)
	{
		if ($fk->getPhpName()) {
			if ($plural) {
				return $this->getPluralizer()->getPluralForm($fk->getPhpName());
			} else {
				return $fk->getPhpName();
			}
		} else {
			$className = $this->getForeignTable($fk)->getPhpName();
			if ($plural) {
				$className = $this->getPluralizer()->getPluralForm($className);
			}
			return $className . $this->getRelatedBySuffix($fk, true);
		}
	}

	/**
	 * Gets the PHP method name affix to be used for referencing foreign key methods and variable names (e.g. set????(), $coll???).
	 *
	 * The difference between this method and the getFKPhpNameAffix() method is that in this method the
	 * classname in the affix is the classname of the local fkey table.
	 *
	 * @param      ForeignKey $fk The referrer FK that we need a name for.
	 * @param      boolean $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
	 * @return     string
	 */
	public function getRefFKPhpNameAffix(ForeignKey $fk, $plural = false)
	{
		if ($fk->getRefPhpName()) {
			if ($plural) {
				return $this->getPluralizer()->getPluralForm($fk->getRefPhpName());
			} else {
				return $fk->getRefPhpName();
			}
		} else {
			$className = $fk->getTable()->getPhpName();
			if ($plural) {
				$className = $this->getPluralizer()->getPluralForm($className);
			}
			return $className . $this->getRelatedBySuffix($fk);
		}
	}

	/**
	 * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
	 *
	 * The related by suffix is based on the local columns of the foreign key.  If there is more than
	 * one column in a table that points to the same foreign table, then a 'RelatedByLocalColName' suffix
	 * will be appended.
	 *
	 * @return     string
	 */
	protected function getRelatedBySuffix(ForeignKey $fk, $columnCheck = false)
	{
		$relCol = "";
		foreach ($fk->getLocalColumns() as $columnName) {
			$column = $fk->getTable()->getColumn($columnName);
			if (!$column) {
				throw new Exception("Could not fetch column: $columnName in table " . $fk->getTable()->getName());
			}

			if ( count($column->getTable()->getForeignKeysReferencingTable($fk->getForeignTableName())) > 1
			|| $fk->getForeignTableName() == $fk->getTable()->getName()) {
				// if there are seeral foreign keys that point to the same table
				// then we need to generate methods like getAuthorRelatedByColName()
				// instead of just getAuthor().  Currently we are doing the same
				// for self-referential foreign keys, to avoid confusion.
				$relCol .= $column->getPhpName();
			}
		}

		#var_dump($fk->getForeignTableName() . ' - ' .$fk->getTableName() . ' - ' . $this->getTable()->getName());

		#$fk->getForeignTableName() != $this->getTable()->getName() &&
		// @todo comment on it
		if ($columnCheck && !$relCol && $fk->getTable()->getColumn($fk->getForeignTableName())) {
			foreach ($fk->getLocalColumns() as $columnName) {
				$column = $fk->getTable()->getColumn($columnName);
				$relCol .= $column->getPhpName();
			}
		}


		if ($relCol != "") {
			$relCol = "RelatedBy" . $relCol;
		}

		return $relCol;
	}
	
	/**
	 * Whether to add the include statements.
	 * This is based on the build property propel.addIncludes
	 */
	protected function isAddIncludes()
	{
		return $this->getBuildProperty('addIncludes');
	}
	
	/**
   * Checks whether any registered behavior on that table has a modifier for a hook
   * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
   * @param string $modifier The name of the modifier object providing the method in the behavior
   * @return boolean
   */
  public function hasBehaviorModifier($hookName, $modifier)
  {
    $modifierGetter = 'get' . $modifier;
    foreach ($this->getTable()->getBehaviors() as $behavior) {
      if(method_exists($behavior->$modifierGetter(), $hookName)) { 
        return true;
      }
      return false;
    }
  }

  /**
   * Checks whether any registered behavior on that table has a modifier for a hook
   * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
   * @param string $modifier The name of the modifier object providing the method in the behavior
	 * @param string &$script The script will be modified in this method.
   */
  public function applyBehaviorModifier($hookName, $modifier, &$script, $tab = "		")
  {
    $modifierGetter = 'get' . $modifier;
    foreach ($this->getTable()->getBehaviors() as $behavior) {
      $modifier = $behavior->$modifierGetter();
      if(method_exists($modifier, $hookName)) {
        if (strpos($hookName, 'Filter') !== false) {
          // filter hook: the script string will be modified by the behavior
          $modifier->$hookName($script);
        } else {
          // regular hook: the behavior returns a string to append to the script string
          $script .= "\n" . $tab . '// ' . $behavior->getName() . " behavior\n";
          $script .= preg_replace('/^/m', $tab, $modifier->$hookName());           
         }
      }
    }
  }
}
