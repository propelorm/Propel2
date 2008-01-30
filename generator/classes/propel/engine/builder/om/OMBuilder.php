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
	 * Peer builder class for current table.
	 * @var        DataModelBuilder
	 */
	private $peerBuilder;

	/**
	 * Stub Peer builder class for current table.
	 * @var        DataModelBuilder
	 */
	private $stubPeerBuilder;

	/**
	 * Object builder class for current table.
	 * @var        DataModelBuilder
	 */
	private $objectBuilder;

	/**
	 * Stub Object builder class for current table.
	 * @var        DataModelBuilder
	 */
	private $stubObjectBuilder;

	/**
	 * MapBuilder builder class for current table.
	 * @var        DataModelBuilder
	 */
	private $mapBuilderBuilder;

	/**
	 * Stub Interface builder class for current table.
	 * @var        DataModelBuilder
	 */
	private $interfaceBuilder;

	/**
	 * Stub child object for current table.
	 * @var        DataModelBuilder
	 */
	private $multiExtendObjectBuilder;

	/**
	 * Node object builder for current table.
	 * @var        DataModelBuilder
	 */
	private $nodeBuilder;

	/**
	 * Node peer builder for current table.
	 * @var        DataModelBuilder
	 */
	private $nodePeerBuilder;

	/**
	 * Stub node object builder for current table.
	 * @var        DataModelBuilder
	 */
	private $stubNodeBuilder;

	/**
	 * Stub node peer builder for current table.
	 * @var        DataModelBuilder
	 */
	private $stubNodePeerBuilder;

	/**
	 * NestedSet object builder for current table.
	 * @var        DataModelBuilder
	 */
	private $nestedSetBuilder;

	/**
	 * NestedSet peer builder for current table.
	 * @var        DataModelBuilder
	 */
	private $nestedSetPeerBuilder;

	/**
	 * The Pluralizer class to use.
	 * @var        Pluralizer
	 */
	private $pluralizer;

	/**
	 * Returns new or existing Peer builder class for this table.
	 * @return     PeerBuilder
	 */
	public function getPeerBuilder()
	{
		if (!isset($this->peerBuilder)) {
			$this->peerBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'peer');
		}
		return $this->peerBuilder;
	}

	/**
	 * Returns new or existing Pluralizer class.
	 * @return     Pluralizer
	 */
	public function getPluralizer()
	{
		if (!isset($this->pluralizer)) {
			$this->pluralizer = $this->getGeneratorConfig()->getConfiguredPluralizer();
		}
		return $this->pluralizer;
	}

	/**
	 * Returns new or existing stub Peer builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getStubPeerBuilder()
	{
		if (!isset($this->stubPeerBuilder)) {
			$this->stubPeerBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'peerstub');
		}
		return $this->stubPeerBuilder;
	}

	/**
	 * Returns new or existing Object builder class for this table.
	 * @return     ObjectBuilder
	 */
	public function getObjectBuilder()
	{
		if (!isset($this->objectBuilder)) {
			$this->objectBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'object');
		}
		return $this->objectBuilder;
	}

	/**
	 * Returns new or existing stub Object builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getStubObjectBuilder()
	{
		if (!isset($this->stubObjectBuilder)) {
			$this->stubObjectBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'objectstub'); 
		}
		return $this->stubObjectBuilder;
	}

	/**
	 * Returns new or existing MapBuilder builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getMapBuilderBuilder()
	{
		if (!isset($this->mapBuilderBuilder)) {
			$this->mapBuilderBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'mapbuilder'); 
		}
		return $this->mapBuilderBuilder;
	}

	/**
	 * Returns new or existing stub Interface builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getInterfaceBuilder()
	{
		if (!isset($this->interfaceBuilder)) {
			$this->interfaceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'interface'); 
		}
		return $this->interfaceBuilder;
	}

	/**
	 * Returns new or existing stub child object builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getMultiExtendObjectBuilder()
	{
		if (!isset($this->multiExtendObjectBuilder)) {
			$this->multiExtendObjectBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'objectmultiextend'); 
		}
		return $this->multiExtendObjectBuilder;
	}

	/**
	 * Returns new or existing node Object builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getNodeBuilder()
	{
		if (!isset($this->nodeBuilder)) {
			$this->nodeBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'node');
		}
		return $this->nodeBuilder;
	}

	/**
	 * Returns new or existing node Peer builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getNodePeerBuilder()
	{
		if (!isset($this->nodePeerBuilder)) {
			$this->nodePeerBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'nodepeer');
		}
		return $this->nodePeerBuilder;
	}

	/**
	 * Returns new or existing stub node Object builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getStubNodeBuilder()
	{
		if (!isset($this->stubNodeBuilder)) {
			$this->stubNodeBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'nodestub');
		}
		return $this->stubNodeBuilder;
	}

	/**
	 * Returns new or existing stub node Peer builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getStubNodePeerBuilder()
	{
		if (!isset($this->stubNodePeerBuilder)) {
			$this->stubNodePeerBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'nodepeerstub');
		}
		return $this->stubNodePeerBuilder;
	}

	/**
	 * Returns new or existing nested set object builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getNestedSetBuilder()
	{
		if (!isset($this->nestedSetBuilder)) {
			$this->nestedSetBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'nestedset');
		}
		return $this->nestedSetBuilder;
	}

	/**
	 * Returns new or existing nested set Peer builder class for this table.
	 * @return     DataModelBuilder
	 */
	public function getNestedSetPeerBuilder()
	{
		if (!isset($this->nestedSetPeerBuilder)) {
			$this->nestedSetPeerBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'nestedsetpeer');
		}
		return $this->nestedSetPeerBuilder;
	}

	/**
	 * Convenience method to return a NEW Peer class builder instance.
	 * This is used very frequently from the peer and object builders to get
	 * a peer builder for a RELATED table.
	 * @param      Table $table
	 * @return     PeerBuilder
	 */
	public function getNewPeerBuilder(Table $table)
	{
		return $this->getGeneratorConfig()->getConfiguredBuilder($table, 'peer');
	}

	/**
	 * Convenience method to return a NEW Object class builder instance.
	 * This is used very frequently from the peer and object builders to get
	 * an object builder for a RELATED table.
	 * @param      Table $table
	 * @return     ObjectBuilder
	 */
	public function getNewObjectBuilder(Table $table)
	{
		return $this->getGeneratorConfig()->getConfiguredBuilder($table, 'object');
	}

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
		$this->addIncludes($script);
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
			$classname = $this->getPeerClassname();
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
	
}
