<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Builder\Util;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\PlatformInterface;

/**
 * A class that is used to parse an input xml schema file and creates a Schema
 * PHP object.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class SchemaReader
{
    public const DEBUG = false;

    /**
     * @var \Propel\Generator\Model\Schema
     */
    private $schema;

    /**
     * @var \Propel\Generator\Model\Database
     */
    private $currDB;

    /**
     * @var \Propel\Generator\Model\Table
     */
    private $currTable;

    /**
     * @var \Propel\Generator\Model\Column
     */
    private $currColumn;

    /**
     * @var \Propel\Generator\Model\ForeignKey
     */
    private $currFK;

    /**
     * @var \Propel\Generator\Model\Index
     */
    private $currIndex;

    /**
     * @var \Propel\Generator\Model\Unique
     */
    private $currUnique;

    /**
     * @var \Propel\Generator\Model\Behavior
     */
    private $currBehavior;

    /**
     * @var \Propel\Generator\Model\VendorInfo
     */
    private $currVendorObject;

    /**
     * @var bool
     */
    private $isForReferenceOnly = false;

    /**
     * @var string|null
     */
    private $currentPackage;

    /**
     * @var string|null
     */
    private $currentXmlFile;

    /**
     * @var string|null
     */
    private $defaultPackage;

    /**
     * @deprecated Unused.
     *
     * @var string
     */
    private $encoding;

    /**
     * Two-dimensional array,
     * first dimension is for schemas(key is the path to the schema file),
     * second is for tags within the schema.
     *
     * @var array
     */
    private $schemasTagsStack = [];

    /**
     * Creates a new instance for the specified database type.
     *
     * @param \Propel\Generator\Platform\PlatformInterface|null $defaultPlatform The default database platform for the application.
     * @param string|null $defaultPackage the default PHP package used for the om
     * @param string $encoding The database encoding.
     */
    public function __construct(?PlatformInterface $defaultPlatform = null, $defaultPackage = null, $encoding = 'iso-8859-1')
    {
        $this->schema = new Schema($defaultPlatform);
        $this->defaultPackage = $defaultPackage;
        $this->encoding = $encoding;
    }

    /**
     * Set the Schema generator configuration
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        $this->schema->setGeneratorConfig($generatorConfig);
    }

    /**
     * Parses a XML input file and returns a newly created and
     * populated Schema structure.
     *
     * @param string $xmlFile The input file to parse.
     *
     * @return \Propel\Generator\Model\Schema|null
     */
    public function parseFile($xmlFile)
    {
        // we don't want infinite recursion
        if ($this->isAlreadyParsed($xmlFile)) {
            return null;
        }

        return $this->parseString(file_get_contents($xmlFile), $xmlFile);
    }

    /**
     * Parses a XML input string and returns a newly created and
     * populated Schema structure.
     *
     * @param string $xmlString The input string to parse.
     * @param string|null $xmlFile The input file name.
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return \Propel\Generator\Model\Schema|null
     */
    public function parseString($xmlString, $xmlFile = null)
    {
        // we don't want infinite recursion
        if ($this->isAlreadyParsed($xmlFile)) {
            return null;
        }

        // store current schema file path
        $this->schemasTagsStack[$xmlFile] = [];
        $this->currentXmlFile = $xmlFile;

        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, 'startElement', 'endElement');
        if (!xml_parse($parser, $xmlString)) {
            throw new SchemaException(sprintf(
                'XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser)
            ));
        }
        xml_parser_free($parser);

        array_pop($this->schemasTagsStack);

        return $this->schema;
    }

    /**
     * @param resource $parser
     * @param string $name
     * @param array $attributes
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    public function startElement($parser, $name, $attributes)
    {
        $parentTag = $this->peekCurrentSchemaTag();
        if ($parentTag === false) {
            switch ($name) {
                case 'database':
                    if ($this->isExternalSchema()) {
                        $this->currentPackage = isset($attributes['package']) ? $attributes['package'] : null;
                        if ($this->currentPackage === null) {
                            $this->currentPackage = $this->defaultPackage;
                        }
                    } else {
                        $this->currDB = $this->schema->addDatabase($attributes);
                    }

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'database') {
            switch ($name) {
                case 'external-schema':
                    $xmlFile = isset($attributes['filename']) ? $attributes['filename'] : null;

                    // 'referenceOnly' attribute is valid in the main schema XML file only,
                    // and it's ignored in the nested external-schemas
                    if (!$this->isExternalSchema()) {
                        $isForRefOnly = isset($attributes['referenceOnly']) ? $attributes['referenceOnly'] : null;
                        $this->isForReferenceOnly = ($isForRefOnly !== null ? (strtolower($isForRefOnly) === 'true') : true); // defaults to TRUE
                    }

                    if ($xmlFile[0] !== '/') {
                        $xmlFile = realpath(dirname($this->currentXmlFile) . DIRECTORY_SEPARATOR . $xmlFile);
                        if (!file_exists($xmlFile)) {
                            throw new SchemaException(sprintf('Unknown include external "%s"', $xmlFile));
                        }
                    }

                    $this->parseFile($xmlFile);

                    break;
                case 'domain':
                    $this->currDB->addDomain($attributes);

                    break;
                case 'table':
                    if (
                        !isset($attributes['schema'])
                        && $this->currDB->getSchema() && $this->currDB->getPlatform()->supportsSchemas()
                        && strpos($attributes['name'], $this->currDB->getPlatform()->getSchemaDelimiter()) === false
                    ) {
                        $attributes['schema'] = $this->currDB->getSchema();
                    }

                    $this->currTable = $this->currDB->addTable($attributes);
                    if ($this->isExternalSchema()) {
                        $this->currTable->setForReferenceOnly($this->isForReferenceOnly);
                        $this->currTable->setPackage($this->currentPackage);
                    }

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currDB->addVendorInfo($attributes);

                    break;
                case 'behavior':
                    $this->currBehavior = $this->currDB->addBehavior($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'table') {
            switch ($name) {
                case 'column':
                    $this->currColumn = $this->currTable->addColumn($attributes);

                    break;
                case 'foreign-key':
                    $this->currFK = $this->currTable->addForeignKey($attributes);

                    break;
                case 'index':
                    $this->currIndex = new Index();
                    $this->currIndex->setTable($this->currTable);
                    $this->currIndex->loadMapping($attributes);

                    break;
                case 'unique':
                    $this->currUnique = new Unique();
                    $this->currUnique->setTable($this->currTable);
                    $this->currUnique->loadMapping($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currTable->addVendorInfo($attributes);

                    break;
                case 'id-method-parameter':
                    $this->currTable->addIdMethodParameter($attributes);

                    break;
                case 'behavior':
                    $this->currBehavior = $this->currTable->addBehavior($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'column') {
            switch ($name) {
                case 'inheritance':
                    $this->currColumn->addInheritance($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currColumn->addVendorInfo($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'foreign-key') {
            switch ($name) {
                case 'reference':
                    $this->currFK->addReference($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currFK->addVendorInfo($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'index') {
            switch ($name) {
                case 'index-column':
                    $this->currIndex->addColumn($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currIndex->addVendorInfo($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'unique') {
            switch ($name) {
                case 'unique-column':
                    $this->currUnique->addColumn($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currUnique->addVendorInfo($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'behavior') {
            switch ($name) {
                case 'parameter':
                    $this->currBehavior->addParameter($attributes);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ($parentTag === 'vendor') {
            switch ($name) {
                case 'parameter':
                    $this->currVendorObject->setParameter($attributes['name'], $attributes['value']);

                    break;
                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } else {
            // it must be an invalid tag
            $this->_throwInvalidTagException($parser, $name);
        }

        $this->pushCurrentSchemaTag($name);
    }

    /**
     * @param resource $parser
     * @param string $tag_name
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    protected function _throwInvalidTagException($parser, $tag_name)
    {
        $location = '';
        if ($this->currentXmlFile !== null) {
            $location .= sprintf('file %s,', $this->currentXmlFile);
        }

        $location .= sprintf('line %d', xml_get_current_line_number($parser));
        if ($col = xml_get_current_column_number($parser)) {
            $location .= sprintf(', column %d', $col);
        }

        throw new SchemaException(sprintf('Unexpected tag <%s> in %s', $tag_name, $location));
    }

    /**
     * @param resource $parser
     * @param string $name
     *
     * @return void
     */
    public function endElement($parser, $name)
    {
        if ($name === 'index') {
            $this->currTable->addIndex($this->currIndex);
        } elseif ($name === 'unique') {
            $this->currTable->addUnique($this->currUnique);
        }

        if (static::DEBUG) {
            print('endElement(' . $name . ") called\n");
        }

        $this->popCurrentSchemaTag();
    }

    /**
     * @return string|false
     */
    protected function peekCurrentSchemaTag()
    {
        $keys = array_keys($this->schemasTagsStack);

        return end($this->schemasTagsStack[end($keys)]);
    }

    /**
     * @return string|false
     */
    protected function popCurrentSchemaTag()
    {
        $keys = array_keys($this->schemasTagsStack);

        return array_pop($this->schemasTagsStack[end($keys)]);
    }

    /**
     * @param string $tag
     *
     * @return void
     */
    protected function pushCurrentSchemaTag($tag)
    {
        $keys = array_keys($this->schemasTagsStack);
        $this->schemasTagsStack[end($keys)][] = $tag;
    }

    /**
     * @return bool
     */
    protected function isExternalSchema()
    {
        return count($this->schemasTagsStack) > 1;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    protected function isAlreadyParsed($filePath)
    {
        return isset($this->schemasTagsStack[$filePath]);
    }
}
