<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Tests\TestCase;

/**
 * This class provides methods for mocking Table, Database and Platform objects.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
abstract class ModelTestCase extends TestCase
{
    /**
     * Returns a dummy Behavior object.
     *
     * @param string $name The behavior name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Behavior
     */
    protected function getBehaviorMock($name, array $options = [])
    {
        $defaults = [
            'additional_builders' => [],
            'is_table_modified' => false,
            'modification_order' => 0,
        ];

        $options = array_merge($defaults, $options);

        $behavior = $this
            ->getMockBuilder('Propel\Generator\Model\Behavior')
            ->disableOriginalConstructor()
            ->getMock();

        $behavior
            ->expects($this->any())
            ->method('setTable');

        $behavior
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $behavior
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($name));

        $behavior
            ->expects($this->any())
            ->method('getAdditionalBuilders')
            ->will($this->returnValue($options['additional_builders']));

        $behavior
            ->expects($this->any())
            ->method('hasAdditionalBuilders')
            ->will($this->returnValue(count($options['additional_builders']) > 0));

        $behavior
            ->expects($this->any())
            ->method('isTableModified')
            ->will($this->returnValue($options['is_table_modified']));

        $behavior
            ->expects($this->any())
            ->method('getTableModificationOrder')
            ->will($this->returnValue($options['modification_order']));

        return $behavior;
    }

    /**
     * Returns a dummy ForeignKey object.
     *
     * @param string|null $name The foreign key name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\ForeignKey
     */
    protected function getForeignKeyMock($name = null, array $options = [])
    {
        $defaults = [
            'foreign_table_name' => '',
            'table' => null,
            'other_fks' => [],
            'local_columns' => [],
        ];

        $options = array_merge($defaults, $options);

        $fk = $this
            ->getMockBuilder('Propel\Generator\Model\ForeignKey')
            ->disableOriginalConstructor()
            ->getMock();

        $fk
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $fk
            ->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue($options['table']));

        $fk
            ->expects($this->any())
            ->method('getForeignTableName')
            ->will($this->returnValue($options['foreign_table_name']));

        $fk
            ->expects($this->any())
            ->method('getLocalColumns')
            ->will($this->returnValue($options['local_columns']));

        $fk
            ->expects($this->any())
            ->method('getOtherFks')
            ->will($this->returnValue($options['other_fks']));

        return $fk;
    }

    /**
     * Returns a dummy Index object.
     *
     * @param string|null $name The index name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Index
     */
    protected function getIndexMock($name = null, array $options = [])
    {
        $defaults = [
            'foreign_table_name' => '',
        ];

        $options = array_merge($defaults, $options);

        $index = $this
            ->getMockBuilder('Propel\Generator\Model\Index')
            ->disableOriginalConstructor()
            ->getMock();
        $index
            ->expects($this->once())
            ->method('setTable');
        $index
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $index;
    }

    /**
     * Returns a dummy Unique object.
     *
     * @param string|null $name The unique index name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Unique
     */
    protected function getUniqueIndexMock($name = null, array $options = [])
    {
        $unique = $this
            ->getMockBuilder('Propel\Generator\Model\Unique')
            ->disableOriginalConstructor()
            ->getMock();
        $unique
            ->expects($this->once())
            ->method('setTable');
        $unique
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $unique;
    }

    /**
     * Returns a dummy Schema object.
     *
     * @param string|null $name The schema name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Schema
     */
    protected function getSchemaMock($name = null, array $options = [])
    {
        $defaults = [
            'generator_config' => null,
        ];

        $options = array_merge($defaults, $options);

        $schema = $this
            ->getMockBuilder('Propel\Generator\Model\Schema')
            ->disableOriginalConstructor()
            ->getMock();
        $schema
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $schema
            ->expects($this->any())
            ->method('getGeneratorConfig')
            ->will($this->returnValue($options['generator_config']));

        return $schema;
    }

    /**
     * Returns a dummy Platform object.
     *
     * @param bool $supportsSchemas Whether the platform supports schemas
     * @param array $options An array of options
     * @param string $schemaDelimiter
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    protected function getPlatformMock($supportsSchemas = true, array $options = [], $schemaDelimiter = '.')
    {
        $defaults = [
            'max_column_name_length' => null,
        ];

        $options = array_merge($defaults, $options);

        $platform = $this
            ->getMockBuilder('Propel\Generator\Platform\DefaultPlatform')
            ->disableOriginalConstructor()
            ->getMock();

        $platform
            ->expects($this->any())
            ->method('supportsSchemas')
            ->will($this->returnValue($supportsSchemas));

        $platform
            ->expects($this->any())
            ->method('getSchemaDelimiter')
            ->will($this->returnValue($schemaDelimiter));

        $platform
            ->expects($this->any())
            ->method('getMaxColumnNameLength')
            ->will($this->returnValue($options['max_column_name_length']));

        return $platform;
    }

    /**
     * Returns a dummy Domain object.
     *
     * @param string|null $name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Domain
     */
    protected function getDomainMock($name = null, array $options = [])
    {
        $defaults = [];

        $options = array_merge($defaults, $options);

        $domain = $this
            ->getMockBuilder('Propel\Generator\Model\Domain')
            ->disableOriginalConstructor()
            ->getMock();

        $domain
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $domain;
    }

    /**
     * Returns a dummy Table object.
     *
     * @param string $name The table name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Table
     */
    protected function getTableMock($name, array $options = [])
    {
        $defaults = [
            'php_name' => str_replace(' ', '', ucwords(str_replace('_', ' ', $name))),
            'namespace' => null,
            'database' => null,
            'platform' => null,
            'common_name' => $name,
            'behaviors' => [],
            'indices' => [],
            'unices' => [],
        ];

        $options = array_merge($defaults, $options);

        $table = $this
            ->getMockBuilder('Propel\Generator\Model\Table')
            ->disableOriginalConstructor()
            ->getMock();

        $table
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $table
            ->expects($this->any())
            ->method('getCommonName')
            ->will($this->returnValue($options['common_name']));

        $table
            ->expects($this->any())
            ->method('getPhpName')
            ->will($this->returnValue($options['php_name']));

        $table
            ->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue($options['platform']));

        $table
            ->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($options['namespace']));

        $table
            ->expects($this->any())
            ->method('getBehaviors')
            ->will($this->returnValue($options['behaviors']));

        $table
            ->expects($this->any())
            ->method('getIndices')
            ->will($this->returnValue($options['indices']));

        $table
            ->expects($this->any())
            ->method('getUnices')
            ->will($this->returnValue($options['unices']));

        $table
            ->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($options['database']));

        return $table;
    }

    /**
     * Returns a dummy Database object.
     *
     * @param string $name The database name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Database
     */
    protected function getDatabaseMock($name, array $options = [])
    {
        $defaults = [
            'platform' => null,
        ];

        $options = array_merge($defaults, $options);

        $database = $this
            ->getMockBuilder('Propel\Generator\Model\Database')
            ->disableOriginalConstructor()
            ->getMock();
        $database
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $database
            ->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue($options['platform']));

        return $database;
    }

    /**
     * Returns a dummy Column object.
     *
     * @param string $name The column name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Column
     */
    protected function getColumnMock($name, array $options = [])
    {
        $defaults = [
            'size' => null,
        ];

        $options = array_merge($defaults, $options);

        $column = $this
            ->getMockBuilder('Propel\Generator\Model\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $column
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $column
            ->expects($this->any())
            ->method('getSize')
            ->will($this->returnValue($options['size']));

        return $column;
    }
}
