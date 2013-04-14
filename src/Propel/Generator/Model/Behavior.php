<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Builder\Util\PropelTemplate;

/**
 * Information about behaviors of a table.
 *
 * @author François Zaninotto
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class Behavior extends MappingModel
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
     * @param Table $table
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
     * @param Database $database
     */
    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Returns the table this behavior is applied to if behavior is applied to
     * a database element.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Adds a single parameter.
     *
     * Expects an associative array looking like
     * array('name' => 'foo', 'value' => bar)
     *
     * @param array $parameters
     */
    public function addParameter(array $parameter)
    {
        $parameter = array_change_key_case($parameter, CASE_LOWER);
        $this->parameters[$parameter['name']] = $parameter['value'];
    }

    /**
     * Overrides the behavior parameters.
     *
     * Expects an associative array looking like array('foo' => 'bar').
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns the associative array of parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns a single parameter by its name.
     *
     * @param  string $name
     * @return array
     */
    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    /**
     * Defines when this behavior must execute its modifyTable() method
     * relative to other behaviors. The bigger the value is, the later the
     * behavior is executed.
     *
     * Default is 50.
     *
     * @param integer $tableModificationOrder
     */
    public function setTableModificationOrder($tableModificationOrder)
    {
        $this->tableModificationOrder = (int) $tableModificationOrder;
    }

    /**
     * Returns when this behavior must execute its modifyTable() method relative
     * to other behaviors. The bigger the value is, the later the behavior is
     * executed.
     *
     * Default is 50.
     *
     * @return integer
     */
    public function getTableModificationOrder()
    {
        return $this->tableModificationOrder;
    }

    /**
     * This method is automatically called on database behaviors when the
     * database model is finished.
     *
     * Propagates the behavior to the tables of the database and override this
     * method to have a database behavior do something special.
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
     * This method is automatically called on table behaviors when the database
     * model is finished. It also override it to add columns to the current
     * table.
     */
    public function modifyTable()
    {

    }

    /**
     * Sets whether or not the table has been modified.
     *
     * @param boolean $modified
     */
    public function setTableModified($modified)
    {
        $this->isTableModified = $modified;
    }

    /**
     * Returns whether or not the table has been modified.
     *
     * @return boolean
     */
    public function isTableModified()
    {
        return $this->isTableModified;
    }

    /**
     * Use Propel simple templating system to render a PHP file using variables
     * passed as arguments. The template file name is relative to the behavior's
     * directory name.
     *
     * @param  string $filename
     * @param  array  $vars
     * @param  string $templateDir
     * @return string
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
     * Returns the current absolute directory name of this behavior. It also
     * works for descendants.
     *
     * @return string
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
     * Returns a column object using a name stored in the behavior parameters.
     * Useful for table behaviors.
     *
     * @param  string $name
     * @return Column
     */
    public function getColumnForParameter($name)
    {
        return $this->getTable()->getColumn($this->getParameter($name));
    }

    protected function setupObject()
    {
        $this->name = $this->getAttribute("name");
    }

    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $bNode = $node->appendChild($doc->createElement('behavior'));
        $bNode->setAttribute('name', $this->getName());

        foreach ($this->parameters as $name => $value) {
            $parameterNode = $bNode->appendChild($doc->createElement('parameter'));
            $parameterNode->setAttribute('name', $name);
            $parameterNode->setAttribute('value', $value);
        }
    }

    /**
     * Returns the table modifier object.
     *
     * The current object is returned by default.
     *
     * @return Behavior
     */
    public function getTableModifier()
    {
        return $this;
    }

    /**
     * Returns the object builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return Behavior
     */
    public function getObjectBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the query builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return Behavior
     */
    public function getQueryBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the table map builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return Behavior
     */
    public function getTableMapBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns whether or not this behavior has additional builders.
     *
     * @return boolean
     */
    public function hasAdditionalBuilders()
    {
        return !empty($this->additionalBuilders);
    }

    /**
     * Returns the list of additional builder objects.
     *
     * @return array
     */
    public function getAdditionalBuilders()
    {
        return $this->additionalBuilders;
    }
}
