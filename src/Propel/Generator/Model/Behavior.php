<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Exception\LogicException;

/**
 * Information about behaviors of a entity.
 *
 * @author François Zaninotto
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Behavior extends MappingModel
{
    /**
     * The entity object on which the behavior is applied.
     *
     * @var Entity
     */
    protected $entity;

    /**
     * The database object.
     *
     * @var Database
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
     * @var array
     */
    protected $parameters = [ ];

    /**
     * Whether or not the entity has been
     * modified by the behavior.
     *
     * @var boolean
     */
    protected $isEntityModified = false;

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
    protected $entityModificationOrder = 50;

    /**
     * Sets the name of the Behavior
     *
     * @param string $name the name of the behavior
     */
    public function setName($name)
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
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Indicates whether the behavior can be applied several times on the same
     * entity or not.
     *
     * @return bool
     */
    public function allowMultiple()
    {
        return false;
    }

    /**
     * Returns the id of the Behavior
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
     * Sets the entity this behavior is applied to
     *
     * @param Entity $entity
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Returns the entity this behavior is applied to
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
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
     * Returns the entity this behavior is applied to if behavior is applied to
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
     * [ 'name' => 'foo', 'value' => bar ]
     *
     * @param array $parameter
     */
    public function addParameter(array $parameter)
    {
        $parameter = array_change_key_case($parameter, CASE_LOWER);
        $this->parameters[$parameter['name']] = $parameter['value'];
    }

    /**
     * Overrides the behavior parameters.
     *
     * Expects an associative array looking like [ 'foo' => 'bar' ].
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
     * @return string
     */
    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    /**
     * Sets a single parameter by its name.
     *
     * @param  string $name
     * @param  string $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Defines when this behavior must execute its modifyEntity() method
     * relative to other behaviors. The bigger the value is, the later the
     * behavior is executed.
     *
     * Default is 50.
     *
     * @param integer $entityModificationOrder
     */
    public function setEntityModificationOrder($entityModificationOrder)
    {
        $this->entityModificationOrder = (int) $entityModificationOrder;
    }

    /**
     * Returns when this behavior must execute its modifyEntity() method relative
     * to other behaviors. The bigger the value is, the later the behavior is
     * executed.
     *
     * Default is 50.
     *
     * @return integer
     */
    public function getEntityModificationOrder()
    {
        return $this->entityModificationOrder;
    }

    /**
     * This method is automatically called on database behaviors when the
     * database model is finished.
     *
     * Propagates the behavior to the entities of the database and override this
     * method to have a database behavior do something special.
     */
    public function modifyDatabase()
    {
        foreach ($this->getEntities() as $entity) {
            if ($entity->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            $behavior = clone $this;
            $entity->addBehavior($behavior);
        }
    }

    /**
     * Returns the list of all entities in the same database.
     *
     * @return Entity[] A collection of Entity instance
     */
    protected function getEntities()
    {
        return $this->database->getEntities();
    }

    /**
     * This method is automatically called on entity behaviors when the database
     * model is finished. It also override it to add columns to the current
     * entity.
     */
    public function modifyEntity()
    {

    }

    /**
     * Sets whether or not the entity has been modified.
     *
     * @param boolean $modified
     */
    public function setEntityModified($modified)
    {
        $this->isEntityModified = $modified;
    }

    /**
     * Returns whether or not the entity has been modified.
     *
     * @return boolean
     */
    public function isEntityModified()
    {
        return $this->isEntityModified;
    }
//
//    /**
//     * Use Propel simple templating system to render a PHP file using variables
//     * passed as arguments. The template file name is relative to the behavior's
//     * directory name.
//     *
//     * @param  string $filename
//     * @param  array  $vars
//     * @param  string $templateDir
//     * @return string
//     */
//    public function renderTemplate($filename, $vars = [], $templateDir = '/templates/')
//    {
//        $filePath = $this->getDirname() . $templateDir . $filename;
//        if (!file_exists($filePath)) {
//            // try with '.php' at the end
//            $filePath = $filePath . '.php';
//            if (!file_exists($filePath)) {
//                throw new \InvalidArgumentException(sprintf('Template "%s" not found in "%s" directory',
//                    $filename,
//                    $this->getDirname() . $templateDir
//                ));
//            }
//        }
//        $template = new PropelTemplate();
//        $template->setTemplateFile($filePath);
//        $vars = array_merge($vars, [ 'behavior' => $this ]);
//
//        return $template->render($vars);
//    }

//    /**
//     * Returns the current absolute directory name of this behavior. It also
//     * works for descendants.
//     *
//     * @return string
//     */
//    protected function getDirname()
//    {
//        if (null === $this->dirname) {
//            $r = new \ReflectionObject($this);
//            $this->dirname = dirname($r->getFileName());
//        }
//
//        return $this->dirname;
//    }

    /**
     * Returns a column object using a name stored in the behavior parameters.
     * Useful for entity behaviors.
     *
     * @param  string $name
     * @return Field
     */
    public function getFieldForParameter($name)
    {
        return $this->entity->getField($this->getParameter($name));
    }

    protected function setupObject()
    {
        $this->setName($this->getAttribute('name'));

        if (!$this->allowMultiple() && $id = $this->getAttribute('id')) {
            throw new LogicException(sprintf('Defining an ID (%s) on a behavior which does not allow multiple instances makes no sense', $id));
        }

        $this->id = $this->getAttribute('id', $this->name);
    }

    /**
     * Hook to change ObjectBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param ObjectBuilder $builder
     */
    public function objectBuilderModification(ObjectBuilder $builder)
    {

    }

    /**
     * Hook to change QueryBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param QueryBuilder $builder
     */
    public function queryBuilderModification(QueryBuilder $builder)
    {

    }

    /**
     * Hook to change RepositoryBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param RepositoryBuilder $builder
     */
    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {

    }

    /**
     * Hook to change EntityMapBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param EntityMapBuilder $builder
     */
    public function entityMapBuilderModification(EntityMapBuilder $builder)
    {

    }

    /**
     * Hook to change ActiveRecordTraitBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param ActiveRecordTraitBuilder $builder
     */
    public function activeRecordTraitBuilderModification(ActiveRecordTraitBuilder $builder)
    {

    }

    /**
     * Returns the entity modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getEntityModifier()
    {
        return $this;
    }

    /**
     * Returns the object builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
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
     * @return $this|Behavior
     */
    public function getQueryBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the entity map builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getEntityMapBuilderModifier()
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function getActiveRecordTraitBuilderModifier()
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

    public function preSave(RepositoryBuilder $builder)
    {
    }

    public function preInsert(RepositoryBuilder $builder)
    {
    }

    public function preUpdate(RepositoryBuilder $builder)
    {
    }

    public function postSave(RepositoryBuilder $builder)
    {
    }

    public function postInsert(RepositoryBuilder $builder)
    {
    }

    public function postUpdate(RepositoryBuilder $builder)
    {
    }

}
