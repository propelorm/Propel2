<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Util;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\VendorInfo;
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
    /** enables debug output */
    const DEBUG = false;

    /** @var Schema  */
    private $schema;

    /** @var Database */
    private $currDB;

    /** @var Table */
    private $currTable;

    /** @var Column */
    private $currColumn;

    /** @var ForeignKey */
    private $currFK;

    /** @var Index */
    private $currIndex;

    /** @var Unique */
    private $currUnique;

    /** @var Behavior */
    private $currBehavior;

    /** @var VendorInfo */
    private $currVendorObject;

    private $isForReferenceOnly;
    private $currentPackage;
    private $currentXmlFile;
    private $defaultPackage;
    private $encoding;

    /**
     * two-dimensional array,
     * first dimension is for schemas(key is the path to the schema file),
     * second is for tags within the schema
     */
    private $schemasTagsStack = array();

    /**
     * Creates a new instance for the specified database type.
     *
     * @param PlatformInterface $defaultPlatform The default database platform for the application.
     * @param string            $defaultPackage  the default PHP package used for the om
     * @param string            $encoding        The database encoding.
     */
    public function __construct(PlatformInterface $defaultPlatform = null, $defaultPackage = null, $encoding = 'iso-8859-1')
    {
        $this->schema = new Schema($defaultPlatform);
        $this->defaultPackage = $defaultPackage;
        $this->firstPass = true;
        $this->encoding = $encoding;
    }

    /**
     * Set the Schema generator configuration
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        $this->schema->setGeneratorConfig($generatorConfig);
    }

    /**
     * Parses a XML input file and returns a newly created and
     * populated Schema structure.
     *
     * @param  string $xmlFile The input file to parse.
     * @return Schema populated by <code>xmlFile</code>.
     */
    public function parseFile($xmlFile)
    {
        // we don't want infinite recursion
        if ($this->isAlreadyParsed($xmlFile)) {
            return;
        }

        return $this->parseString(file_get_contents($xmlFile), $xmlFile);
    }

    /**
     * Parses a XML input string and returns a newly created and
     * populated Schema structure.
     *
     * @param  string $xmlString The input string to parse.
     * @param  string $xmlFile   The input file name.
     * @return Schema
     */
    public function parseString($xmlString, $xmlFile = null)
    {
        // we don't want infinite recursion
        if ($this->isAlreadyParsed($xmlFile)) {
            return;
        }
        // store current schema file path
        $this->schemasTagsStack[$xmlFile] = array();
        $this->currentXmlFile = $xmlFile;

        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, 'startElement', 'endElement');
        if (!xml_parse($parser, $xmlString)) {
            throw new SchemaException(sprintf('XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser))
            );
        }
        xml_parser_free($parser);

        array_pop($this->schemasTagsStack);

        return $this->schema;
    }

    public function startElement($parser, $name, $attributes)
    {
        $parentTag = $this->peekCurrentSchemaTag();
        if (false === $parentTag) {
            switch ($name) {
                case 'database':
                    if ($this->isExternalSchema()) {
                        $this->currentPackage = isset($attributes['package']) ? $attributes['package'] : null;
                        if (null === $this->currentPackage) {
                            $this->currentPackage = $this->defaultPackage;
                        }
                    } else {
                        $this->currDB = $this->schema->addDatabase($attributes);
                    }
                    break;

                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ('database' === $parentTag) {
            switch ($name) {
                case 'external-schema':
                    $xmlFile = isset($attributes['filename']) ? $attributes['filename'] : null;

                    // 'referenceOnly' attribute is valid in the main schema XML file only,
                    // and it's ignored in the nested external-schemas
                    if (!$this->isExternalSchema()) {
                        $isForRefOnly = isset($attributes['referenceOnly']) ? $attributes['referenceOnly'] : null;
                        $this->isForReferenceOnly = (null !== $isForRefOnly ? ('true' === strtolower($isForRefOnly)) : true); // defaults to TRUE
                    }

                    if ('/' !== $xmlFile{0}) {
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

        } elseif ('table' === $parentTag) {
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

        } elseif ('column' === $parentTag) {

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

        } elseif ('foreign-key' === $parentTag) {

            switch ($name) {
                case 'reference':
                    $this->currFK->addReference($attributes);
                    break;

                case 'vendor':
                    $this->currVendorObject = $this->currUnique->addVendorInfo($attributes);
                    break;

                default:
                    $this->_throwInvalidTagException($parser, $name);
            }

        } elseif ('index' === $parentTag) {

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

        } elseif ('unique' === $parentTag) {

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
        } elseif ($parentTag == 'behavior') {

            switch ($name) {
                case 'parameter':
                    $this->currBehavior->addParameter($attributes);
                    break;

                default:
                    $this->_throwInvalidTagException($parser, $name);
            }
        } elseif ('vendor' === $parentTag) {

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

    protected function _throwInvalidTagException($parser, $tag_name)
    {
        $location = '';
        if (null !== $this->currentXmlFile) {
            $location .= sprintf('file %s,', $this->currentXmlFile);
        }

        $location .= sprintf('line %d', xml_get_current_line_number($parser));
        if ($col = xml_get_current_column_number($parser)) {
            $location .= sprintf(', column %d', $col);
        }

        throw new SchemaException(sprintf('Unexpected tag <%s> in %s', $tag_name, $location));
    }

    public function endElement($parser, $name)
    {
        if ('index' === $name) {
            $this->currTable->addIndex($this->currIndex);
        } else if ('unique' === $name) {
            $this->currTable->addUnique($this->currUnique);
        }

        if (self::DEBUG) {
            print('endElement(' . $name . ") called\n");
        }

        $this->popCurrentSchemaTag();
    }

    protected function peekCurrentSchemaTag()
    {
        $keys = array_keys($this->schemasTagsStack);

        return end($this->schemasTagsStack[end($keys)]);
    }

    protected function popCurrentSchemaTag()
    {
        $keys = array_keys($this->schemasTagsStack);
        array_pop($this->schemasTagsStack[end($keys)]);
    }

    protected function pushCurrentSchemaTag($tag)
    {
        $keys = array_keys($this->schemasTagsStack);
        $this->schemasTagsStack[end($keys)][] = $tag;
    }

    protected function isExternalSchema()
    {
        return count($this->schemasTagsStack) > 1;
    }

    protected function isAlreadyParsed($filePath)
    {
        return isset($this->schemasTagsStack[$filePath]);
    }
}
