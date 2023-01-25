<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use InvalidArgumentException;
use Propel\Common\Util\PathTrait;
use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Exception\LogicException;
use ReflectionObject;

/**
 * Information about behaviors of a table.
 *
 * @author François Zaninotto
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Behavior extends MappingModel
{
    use PathTrait;

    /**
     * The table object on which the behavior is applied.
     *
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * The database object.
     *
     * @var \Propel\Generator\Model\Database
     */
    protected $database;

    /**
     * The behavior id.
     *
     * @var string
     */
    protected $id;

    /**
     * The behavior name.
     *
     * @var string
     */
    protected $name;

    /**
     * A collection of parameters.
     *
     * @var array<string, mixed>
     */
    protected $parameters = [];

    /**
     * Whether the table has been
     * modified by the behavior.
     *
     * @var bool
     */
    protected $isTableModified = false;

    /**
     * The absolute path to the directory
     * that contains the behavior's templates
     * files.
     *
     * @var string
     */
    protected $dirname;

    /**
     * A collection of additional builders.
     *
     * @var array
     */
    protected $additionalBuilders = [];

    /**
     * The order in which the behavior must
     * be applied.
     *
     * @var int
     */
    protected $tableModificationOrder = 50;

    /**
     * Sets the name of the Behavior
     *
     * @param string $name the name of the behavior
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;

        if ($this->id === null) {
            $this->id = $name;
        }
    }

    /**
     * Sets the id of the Behavior
     *
     * @param string $id The id of the behavior
     *
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Indicates whether the behavior can be applied several times on the same
     * table or not.
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return false;
    }

    /**
     * Returns the id of the Behavior
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the name of the Behavior
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the table this behavior is applied to
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    /**
     * Returns the table this behavior is applied to
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getTable(): ?Table
    {
        return $this->table;
    }

    /**
     * Returns the table this behavior is applied to
     *
     * @throws \Propel\Generator\Exception\LogicException
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getTableOrFail(): Table
    {
        $table = $this->getTable();

        if ($table === null) {
            throw new LogicException('Table is not defined.');
        }

        return $table;
    }

    /**
     * Sets the database this behavior is applied to
     *
     * @param \Propel\Generator\Model\Database $database
     *
     * @return void
     */
    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    /**
     * Returns the table this behavior is applied to if behavior is applied to
     * a database element.
     *
     * @return \Propel\Generator\Model\Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Adds a single parameter.
     *
     * Expects an associative array looking like
     * [ 'name' => 'foo', 'value' => bar ]
     *
     * @param array $parameter
     *
     * @return void
     */
    public function addParameter(array $parameter): void
    {
        $parameter = array_change_key_case($parameter, CASE_LOWER);
        $this->parameters[(string)$parameter['name']] = $parameter['value'];
    }

    /**
     * Overrides the behavior parameters.
     *
     * Expects an associative array looking like [ 'foo' => 'bar' ].
     *
     * @param array $parameters
     *
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns the associative array of parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns a single parameter by its name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters[$name];
    }

    /**
     * Checks if a param has the given value
     *
     * @param string $paramName
     * @param mixed $value
     *
     * @return bool
     */
    public function parameterHasValue(string $paramName, $value): bool
    {
        return $this->parameters[$paramName] === $value;
    }

    /**
     * Defines when this behavior must execute its modifyTable() method
     * relative to other behaviors. The bigger the value is, the later the
     * behavior is executed.
     *
     * Default is 50.
     *
     * @param int $tableModificationOrder
     *
     * @return void
     */
    public function setTableModificationOrder(int $tableModificationOrder): void
    {
        $this->tableModificationOrder = $tableModificationOrder;
    }

    /**
     * Returns when this behavior must execute its modifyTable() method relative
     * to other behaviors. The bigger the value is, the later the behavior is
     * executed.
     *
     * Default is 50.
     *
     * @return int
     */
    public function getTableModificationOrder(): int
    {
        return $this->tableModificationOrder;
    }

    /**
     * This method is automatically called on database behaviors when the
     * database model is finished.
     *
     * Propagates the behavior to the tables of the database and override this
     * method to have a database behavior do something special.
     *
     * @return void
     */
    public function modifyDatabase(): void
    {
        foreach ($this->getTables() as $table) {
            if ($table->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            $behavior = clone $this;
            $table->addBehavior($behavior);
        }
    }

    /**
     * Returns the list of all tables in the same database.
     *
     * @return array<\Propel\Generator\Model\Table> A collection of Table instance
     */
    protected function getTables(): array
    {
        return $this->database->getTables();
    }

    /**
     * This method is automatically called on table behaviors when the database
     * model is finished. Override this method to add columns to the current
     * table.
     *
     * @return void
     */
    public function modifyTable(): void
    {
    }

    /**
     * Sets whether the table has been modified.
     *
     * @param bool $modified
     *
     * @return void
     */
    public function setTableModified(bool $modified): void
    {
        $this->isTableModified = $modified;
    }

    /**
     * Returns whether the table has been modified.
     *
     * @return bool
     */
    public function isTableModified(): bool
    {
        return $this->isTableModified;
    }

    /**
     * Use Propel simple templating system to render a PHP file using variables
     * passed as arguments. The template file name is relative to the behavior's
     * directory name.
     *
     * @param string $filename
     * @param array $vars
     * @param string|null $templatePath
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function renderTemplate(string $filename, array $vars = [], ?string $templatePath = null): string
    {
        if ($templatePath === null) {
            $templatePath = $this->getTemplatePath($this->getDirname());
        }

        $filePath = $templatePath . $filename;
        if (!file_exists($filePath)) {
            // try with '.php' at the end
            $filePath = $filePath . '.php';
            if (!file_exists($filePath)) {
                throw new InvalidArgumentException(sprintf(
                    'Template `%s` not found in `%s` directory',
                    $filename,
                    $templatePath,
                ));
            }
        }
        $template = new PropelTemplate();
        $template->setTemplateFile($filePath);
        $vars = array_merge($vars, ['behavior' => $this]);

        return $template->render($vars);
    }

    /**
     * Returns the current absolute directory name of this behavior. It also
     * works for descendants.
     *
     * @return string
     */
    protected function getDirname(): string
    {
        if ($this->dirname === null) {
            $behaviorReflectionObject = new ReflectionObject($this);
            $behaviorFileName = (string)$behaviorReflectionObject->getFileName();
            $this->dirname = dirname($behaviorFileName);
        }

        return $this->dirname;
    }

    /**
     * Returns a column object using a name stored in the behavior parameters.
     * Useful for table behaviors.
     *
     * @param string $name
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getColumnForParameter(string $name): ?Column
    {
        return $this->table->getColumn($this->getParameter($name));
    }

    /**
     * @throws \Propel\Generator\Exception\LogicException
     *
     * @return void
     */
    protected function setupObject(): void
    {
        $this->setName($this->getAttribute('name'));
        $id = $this->getAttribute('id');

        if (!$this->allowMultiple() && $id) {
            throw new LogicException(sprintf('Defining an ID (%s) on a behavior which does not allow multiple instances makes no sense', $id));
        }

        $this->id = $this->getAttribute('id', $this->name);
    }

    /**
     * Returns the table modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function getTableMapBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns whether this behavior has additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        return (bool)$this->additionalBuilders;
    }

    /**
     * Returns the list of additional builder objects.
     *
     * @return array
     */
    public function getAdditionalBuilders(): array
    {
        return $this->additionalBuilders;
    }
}
