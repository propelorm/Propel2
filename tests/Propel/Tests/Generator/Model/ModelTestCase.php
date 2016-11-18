<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Tests\TestCase;

/**
 * This class provides methods for mocking Entity, Database and Platform objects.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
abstract class ModelTestCase extends TestCase
{
    /**
     * Returns a dummy Behavior object.
     *
     * @param  string   $name    The behavior name
     * @param  array    $options An array of options
     * @return Behavior
     */
    protected function getBehaviorMock($name, array $options = array())
    {
        $defaults = array(
            'additional_builders' => array(),
            'is_entity_modified'   => false,
            'modification_order'  => 0,
        );

        $options = array_merge($defaults, $options);

        $behavior = $this
            ->getMockBuilder('Propel\Generator\Model\Behavior')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $behavior
            ->expects($this->any())
            ->method('setEntity')
        ;

        $behavior
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        $behavior
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($name))
        ;

        $behavior
            ->expects($this->any())
            ->method('getAdditionalBuilders')
            ->will($this->returnValue($options['additional_builders']))
        ;

        $behavior
            ->expects($this->any())
            ->method('hasAdditionalBuilders')
            ->will($this->returnValue(count($options['additional_builders']) > 0))
        ;

        $behavior
            ->expects($this->any())
            ->method('isEntityModified')
            ->will($this->returnValue($options['is_entity_modified']))
        ;

        $behavior
            ->expects($this->any())
            ->method('getEntityModificationOrder')
            ->will($this->returnValue($options['modification_order']))
        ;

        return $behavior;
    }

    /**
     * Returns a dummy Relation object.
     *
     * @param  string     $name    The foreign key name
     * @param  array      $options An array of options
     * @return Relation
     */
    protected function getRelationMock($name = null, array $options = array())
    {
        $defaults = array(
            'target' => '',
            'entity' => null,
            'other_fks' => array(),
            'local_fields' => array(),
        );

        $options = array_merge($defaults, $options);

        $fk = $this
            ->getMockBuilder('Propel\Generator\Model\Relation')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $fk
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        $fk
            ->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($options['entity']))
        ;

        $fk
            ->expects($this->any())
            ->method('getForeignEntityName')
            ->will($this->returnValue($options['target']))
        ;

        $fk
            ->expects($this->any())
            ->method('getLocalFields')
            ->will($this->returnValue($options['local_fields']))
        ;

        $fk
            ->expects($this->any())
            ->method('getOtherFks')
            ->will($this->returnValue($options['other_fks']))
        ;

        return $fk;
    }

    /**
     * Returns a dummy Index object.
     *
     * @param  string $name    The index name
     * @param  array  $options An array of options
     * @return Index
     */
    protected function getIndexMock($name = null, array $options = array())
    {
        $defaults = array(
        );

        $options = array_merge($defaults, $options);

        $index = $this
            ->getMockBuilder('Propel\Generator\Model\Index')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $index
            ->expects($this->once())
            ->method('setEntity')
        ;
        $index
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        return $index;
    }

    /**
     * Returns a dummy Unique object.
     *
     * @param  string $name    The unique index name
     * @param  array  $options An array of options
     * @return Unique
     */
    protected function getUniqueIndexMock($name = null, array $options = array())
    {
        $unique = $this
            ->getMockBuilder('Propel\Generator\Model\Unique')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $unique
            ->expects($this->once())
            ->method('setEntity')
        ;
        $unique
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        return $unique;
    }

    /**
     * Returns a dummy Schema object.
     *
     * @param  string $name    The schema name
     * @param  array  $options An array of options
     * @return Schema
     */
    protected function getSchemaMock($name = null, array $options = array())
    {
        $defaults = array(
            'generator_config' => null,
        );

        $options = array_merge($defaults, $options);

        $schema = $this
            ->getMockBuilder('Propel\Generator\Model\Schema')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $schema
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;
        $schema
            ->expects($this->any())
            ->method('getGeneratorConfig')
            ->will($this->returnValue($options['generator_config']))
        ;

        return $schema;
    }

    /**
     * Returns a dummy Platform object.
     *
     * @param  boolean           $supportsSchemas Whether or not the platform supports schemas
     * @param  array             $options         An array of options
     * @param  string            $schemaDelimiter
     * @return PlatformInterface
     */
    protected function getPlatformMock($supportsSchemas = true, array $options = array(), $schemaDelimiter = '.')
    {
        $defaults = array(
            'max_field_name_length' => null,
        );

        $options = array_merge($defaults, $options);

        $platform = $this
            ->getMockBuilder('Propel\Generator\Platform\SqlDefaultPlatform')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $platform
            ->expects($this->any())
            ->method('supportsSchemas')
            ->will($this->returnValue($supportsSchemas))
        ;

        $platform
            ->expects($this->any())
            ->method('getSchemaDelimiter')
            ->will($this->returnValue($schemaDelimiter))
        ;

        $platform
            ->expects($this->any())
            ->method('getMaxFieldNameLength')
            ->will($this->returnValue($options['max_field_name_length']))
        ;

        return $platform;
    }

    /**
     * Returns a dummy Domain object.
     *
     * @param  string $name
     * @param  array  $options An array of options
     * @return Domain
     */
    protected function getDomainMock($name = null, array $options = array())
    {
        $defaults = array();

        $options = array_merge($defaults, $options);

        $domain = $this
            ->getMockBuilder('Propel\Generator\Model\Domain')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $domain
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        return $domain;
    }

    /**
     * Returns a dummy Entity object.
     *
     * @param  string $name    The entity name
     * @param  array  $options An array of options
     * @return Entity
     */
    protected function getEntityMock($name, array $options = array())
    {
        $defaults = array(
            'name'    => $name,
            'tableName' => $name,
            'namespace'   => null,
            'database'    => null,
            'platform'    => null,
            'behaviors'   => array(),
            'indices'     => array(),
            'unices'      => array(),
        );

        $options = array_merge($defaults, $options);

        $entity = $this
            ->getMockBuilder('Propel\Generator\Model\Entity')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $entity
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        $entity
            ->expects($this->any())
            ->method('getFullClassName')
            ->will($this->returnValue($name))
        ;

        $entity
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($options['tableName']))
        ;

        $entity
            ->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue($options['platform']))
        ;

        $entity
            ->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($options['namespace']))
        ;

        $entity
            ->expects($this->any())
            ->method('getBehaviors')
            ->will($this->returnValue($options['behaviors']))
        ;

        $entity
            ->expects($this->any())
            ->method('getIndices')
            ->will($this->returnValue($options['indices']))
        ;

        $entity
            ->expects($this->any())
            ->method('getUnices')
            ->will($this->returnValue($options['unices']))
        ;

        $entity
            ->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($options['database']))
        ;

        return $entity;
    }

    /**
     * Returns a dummy Database object.
     *
     * @param  string   $name    The database name
     * @param  array    $options An array of options
     * @return Database
     */
    protected function getDatabaseMock($name, array $options = array())
    {
        $defaults = array(
            'platform' => null,
        );

        $options = array_merge($defaults, $options);

        $database = $this
            ->getMockBuilder('Propel\Generator\Model\Database')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $database
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;
        $database
            ->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue($options['platform']))
        ;

        return $database;
    }

    /**
     * Returns a dummy Field object.
     *
     * @param  string $name    The field name
     * @param  array  $options An array of options
     * @return Field
     */
    protected function getFieldMock($name, array $options = array())
    {
        $defaults = array(
            'size' => null,
        );

        $options = array_merge($defaults, $options);

        $field = $this
            ->getMockBuilder('Propel\Generator\Model\Field')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $field
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name))
        ;

        $field
            ->expects($this->any())
            ->method('getSize')
            ->will($this->returnValue($options['size']))
        ;

        return $field;
    }
}
