<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Builder\Util\PropelTemplate;

/**
 * Information about behaviors of a table.
 *
 * @author     François Zaninotto
 */
class Behavior extends XmlElement
{

    protected $table;
    protected $database;
    protected $name;
    protected $parameters = array();
    protected $isTableModified = false;
    protected $dirname;
    protected $additionalBuilders = array();
    protected $tableModificationOrder = 50;

    /**
     * Sets the name of the Behavior
     *
     * @param string $name the name of the behavior
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the Behavior
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the table this behavior is applied to
     *
     * @param Table $table the table this behavior is applied to
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns the table this behavior is applied to
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets the database this behavior is applied to
     *
     * @param Database $database the database this behavior is applied to
     */
    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Returns the table this behavior is applied to if behavior is applied to <database> element.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Add a parameter
     * Expects an associative array looking like array('name' => 'foo', 'value' => bar)
     *
     * @param     array associative array with name and value keys
     */
    public function addParameter($attribute)
    {
        $attribute = array_change_key_case($attribute, CASE_LOWER);
        $this->parameters[$attribute['name']] = $attribute['value'];
    }

    /**
     * Overrides the behavior parameters
     * Expects an associative array looking like array('foo' => 'bar')
     *
     * @param     array associative array
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Get the associative array of parameters
     * @return    array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    /**
     * Define when this behavior must execute its modifyTable() relative to other behaviors.
     * The bigger the value, the later the behavior is executed. Default is 50.
     *
     * @param $tableModificationOrder integer
     */
    public function setTableModificationOrder($tableModificationOrder)
    {
        $this->tableModificationOrder = $tableModificationOrder;
    }

    /**
     * Get when this behavior must execute its modifyTable() relative to other behaviors.
     * The bigger the value, the later the behavior is executed. Default is 50.
     *
     * @return integer
     */
    public function getTableModificationOrder()
    {
        return $this->tableModificationOrder;
    }

    /**
     * This method is automatically called on database behaviors when the database model is finished
     * Propagate the behavior to the tables of the database
     * Override this method to have a database behavior do something special
     */
    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            if ($table->hasBehavior($this->getName())) {
                // don't add the same behavior twice
                continue;
            }
            $b = clone $this;
            $table->addBehavior($b);
        }
    }

    /**
     * This method is automatically called on table behaviors when the database model is finished
     * Override it to add columns to the current table
     */
    public function modifyTable()
    {
    }

    public function setTableModified($bool)
    {
        $this->isTableModified = $bool;
    }

    public function isTableModified()
    {
        return $this->isTableModified;
    }

    /**
     * Use Propel's simple templating system to render a PHP file
     * using variables passed as arguments.
     *
     * @param string $filename    The template file name, relative to the behavior's dirname
     * @param array  $vars        An associative array of argumens to be rendered
     * @param string $templateDir The name of the template subdirectory
     *
     * @return string The rendered template
     */
    public function renderTemplate($filename, $vars = array(), $templateDir = '/templates/')
    {
        $filePath = $this->getDirname() . $templateDir . $filename;
        if (!file_exists($filePath)) {
            // try with '.php' at the end
            $filePath = $filePath . '.php';
            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException(sprintf('Template "%s" not found in "%s" directory',
                    $filename,
                    $this->getDirname() . $templateDir
                ));
            }
        }
        $template = new PropelTemplate();
        $template->setTemplateFile($filePath);
        $vars = array_merge($vars, array('behavior' => $this));

        return $template->render($vars);
    }

    /**
     * Returns the current dirname of this behavior (also works for descendants)
     *
     * @return string The absolute directory name
     */
    protected function getDirname()
    {
        if (null === $this->dirname) {
            $r = new \ReflectionObject($this);
            $this->dirname = dirname($r->getFileName());
        }

        return $this->dirname;
    }

    /**
     * Retrieve a column object using a name stored in the behavior parameters
     * Useful for table behaviors
     *
     * @param     string    $param Name of the parameter storing the column name
     * @return    ColumnMap The column of the table supporting the behavior
     */
    public function getColumnForParameter($param)
    {
        return $this->getTable()->getColumn($this->getParameter($param));
    }

    /**
     * Sets up the Behavior object based on the attributes that were passed to loadFromXML().
     * @see       parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->name = $this->getAttribute("name");
    }

    /**
     * @see       parent::appendXml(DOMNode)
     */
    public function appendXml(DOMNode $node)
    {
        $doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

        $bNode = $node->appendChild($doc->createElement('behavior'));
        $bNode->setAttribute('name', $this->getName());

        foreach ($this->parameters as $name => $value) {
            $parameterNode = $bNode->appendChild($doc->createElement('parameter'));
            $parameterNode->setAttribute('name', $name);
            $parameterNode->setAttribute('value', $value);
        }
    }

    public function getTableModifier()
    {
        return $this;
    }

    public function getObjectBuilderModifier()
    {
        return $this;
    }

    public function getQueryBuilderModifier()
    {
        return $this;
    }

    public function getPeerBuilderModifier()
    {
        return $this;
    }

    public function getTableMapBuilderModifier()
    {
        return $this;
    }

    public function hasAdditionalBuilders()
    {
        return !empty($this->additionalBuilders);
    }

    public function getAdditionalBuilders()
    {
        return $this->additionalBuilders;
    }
}