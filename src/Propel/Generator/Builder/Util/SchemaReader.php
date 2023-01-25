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
    /**
     * @var bool
     */
    public const DEBUG = false;

    /**
     * @var \Propel\Generator\Model\Schema
     */
    private $schema;

    /**
     * @var \XMLParser|resource
     */
    private $parser;

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
     * @var array|null
     */
    private $currParameterListCollector;

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
     */
    public function __construct(?PlatformInterface $defaultPlatform = null, ?string $defaultPackage = null)
    {
        $this->schema = new Schema($defaultPlatform);
        $this->defaultPackage = $defaultPackage;
    }

    /**
     * Set the Schema generator configuration
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig): void
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
    public function parseFile(string $xmlFile): ?Schema
    {
        // we don't want infinite recursion
        if ($this->isAlreadyParsed($xmlFile)) {
            return null;
        }

        return $this->parseString((string)file_get_contents($xmlFile), $xmlFile);
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
    public function parseString(string $xmlString, ?string $xmlFile = null): ?Schema
    {
        // we don't want infinite recursion
        if ($xmlFile && $this->isAlreadyParsed($xmlFile)) {
            return null;
        }

        // store current schema file path
        $this->schemasTagsStack[$xmlFile] = [];
        $this->currentXmlFile = $xmlFile;

        $parserStash = $this->parser;

        $this->parser = xml_parser_create();
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, [$this, 'startElement'], [$this, 'endElement']);
        if (!xml_parse($this->parser, $xmlString)) {
            throw new SchemaException(
                sprintf(
                    'XML error: %s at line %d',
                    xml_error_string(xml_get_error_code($this->parser)),
                    xml_get_current_line_number($this->parser),
                ),
            );
        }
        xml_parser_free($this->parser);
        $this->parser = $parserStash;

        array_pop($this->schemasTagsStack);

        return $this->schema;
    }

    /**
     * @param resource $parser
     * @param string $tagName
     * @param array $attributes
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    public function startElement($parser, string $tagName, array $attributes): void
    {
        $parentTag = $this->peekCurrentSchemaTag();
        if ($parentTag === false) {
            switch ($tagName) {
                case 'database':
                    if ($this->isExternalSchema()) {
                        $this->currentPackage = $attributes['package'] ?? null;
                        if ($this->currentPackage === null) {
                            $this->currentPackage = $this->defaultPackage;
                        }
                    } else {
                        $this->currDB = $this->schema->addDatabase($attributes);
                    }

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'database') {
            switch ($tagName) {
                case 'external-schema':
                    $xmlFile = $attributes['filename'] ?? null;

                    // 'referenceOnly' attribute is valid in the main schema XML file only,
                    // and it's ignored in the nested external-schemas
                    if (!$this->isExternalSchema()) {
                        $isForRefOnly = $attributes['referenceOnly'] ?? null;
                        $this->isForReferenceOnly = ($isForRefOnly !== null ? (strtolower($isForRefOnly) === 'true') : true); // defaults to TRUE
                    }

                    if ($xmlFile[0] !== '/') {
                        $xmlFile = (string)realpath(dirname($this->currentXmlFile) . DIRECTORY_SEPARATOR . $xmlFile);
                        if (!file_exists($xmlFile)) {
                            throw new SchemaException(sprintf('Unknown include external `%s`', $xmlFile));
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
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'table') {
            switch ($tagName) {
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
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'column') {
            switch ($tagName) {
                case 'inheritance':
                    $this->currColumn->addInheritance($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currColumn->addVendorInfo($attributes);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'foreign-key') {
            switch ($tagName) {
                case 'reference':
                    $this->currFK->addReference($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currFK->addVendorInfo($attributes);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'index') {
            switch ($tagName) {
                case 'index-column':
                    $this->currIndex->addColumn($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currIndex->addVendorInfo($attributes);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'unique') {
            switch ($tagName) {
                case 'unique-column':
                    $this->currUnique->addColumn($attributes);

                    break;
                case 'vendor':
                    $this->currVendorObject = $this->currUnique->addVendorInfo($attributes);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'behavior') {
            switch ($tagName) {
                case 'parameter':
                    $this->currBehavior->addParameter($attributes);

                    break;
                case 'parameter-list':
                    $this->initParameterListCollector($attributes);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'parameter-list') {
            switch ($tagName) {
                case 'parameter-list-item':
                    $this->addItemToParameterListCollector();

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'parameter-list-item') {
            switch ($tagName) {
                case 'parameter':
                    $this->addAttributeToParameterListItem($attributes);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } elseif ($parentTag === 'vendor') {
            switch ($tagName) {
                case 'parameter':
                    $this->currVendorObject->setParameter($attributes['name'], $attributes['value']);

                    break;
                default:
                    $this->throwInvalidTagException($tagName);
            }
        } else {
            // it must be an invalid tag
            $this->throwInvalidTagException($tagName);
        }

        $this->pushCurrentSchemaTag($tagName);
    }

    /**
     * @param string $tagName
     *
     * @return void
     */
    protected function throwInvalidTagException(string $tagName): void
    {
        $this->throwSchemaExceptionWithLocation('Unexpected tag <%s>', $tagName);
    }

    /**
     * @param string $format
     * @param mixed $args sprintf arguments
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    private function throwSchemaExceptionWithLocation(string $format, ...$args): void
    {
        $format .= ' in %s';
        $args[] = $this->getLocationDescription();
        $message = vsprintf($format, $args);

        throw new SchemaException($message);
    }

    /**
     * Builds a human-readable description of the current location in the parser, i.e. "file schema.xml line 42, column 43"
     *
     * @return string
     */
    private function getLocationDescription(): string
    {
        $location = ($this->currentXmlFile !== null) ? sprintf('file %s,', $this->currentXmlFile) : '';

        /**
         * @phpstan-ignore-next-line PHPStan is expecting XMLParser only, while resource is valid too.
         */
        $currentLineNumber = xml_get_current_line_number($this->parser);
        if ($currentLineNumber) {
            $location .= sprintf('line %d', $currentLineNumber);
        }

        /**
         * @phpstan-ignore-next-line PHPStan is expecting XMLParser only, while resource is valid too.
         */
        $currentColumnNumber = xml_get_current_column_number($this->parser);
        if ($currentColumnNumber) {
            $location .= sprintf(', column %d', $currentColumnNumber);
        }

        return $location;
    }

    /**
     * @param resource $parser
     * @param string $tagName
     *
     * @return void
     */
    public function endElement($parser, string $tagName): void
    {
        if ($tagName === 'index') {
            $this->currTable->addIndex($this->currIndex);
        } elseif ($tagName === 'unique') {
            $this->currTable->addUnique($this->currUnique);
        }

        if (static::DEBUG) {
            print('endElement(' . $tagName . ") called\n");
        }

        $this->popCurrentSchemaTag();

        if ($tagName === 'parameter-list') {
            $this->finalizeParameterList();
        }
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
    protected function pushCurrentSchemaTag(string $tag): void
    {
        $keys = array_keys($this->schemasTagsStack);
        $this->schemasTagsStack[end($keys)][] = $tag;
    }

    /**
     * @return bool
     */
    protected function isExternalSchema(): bool
    {
        return count($this->schemasTagsStack) > 1;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    protected function isAlreadyParsed(string $filePath): bool
    {
        return isset($this->schemasTagsStack[$filePath]);
    }

    /**
     * @param array $attributes attributes of parameter-list tag
     *
     * @return void
     */
    private function initParameterListCollector(array $attributes): void
    {
        $parameterName = $this->getExpectedValue($attributes, 'name');

        $this->currParameterListCollector = [
            'name' => $parameterName,
            'value' => [],
        ];
    }

    /**
     * Add a new item to the parameter list.
     *
     * @return void
     */
    private function addItemToParameterListCollector(): void
    {
        $this->currParameterListCollector['value'][] = [];
    }

    /**
     * Add a parameter to the last added item in the parameter list.
     *
     * @param array $attributes
     *
     * @return void
     */
    private function addAttributeToParameterListItem(array $attributes): void
    {
        $name = $this->getExpectedValue($attributes, 'name');
        $value = $this->getExpectedValue($attributes, 'value');
        $items = &$this->currParameterListCollector['value'];
        end($items);
        $currentItem = &$items[key($items)];
        $currentItem[$name] = $value;
    }

    /**
     * Feeds the current parameter list to its parent and clears the collector.
     *
     * @return void
     */
    private function finalizeParameterList(): void
    {
        $parentTag = $this->peekCurrentSchemaTag();
        if ($parentTag === 'behavior') {
            $this->currBehavior->addParameter($this->currParameterListCollector);
        } else {
            $this->throwSchemaExceptionWithLocation('Cannot add parameter list to tag <%s>', $parentTag);
        }

        $this->currParameterListCollector = null;
    }

    /**
     * Checks if the given array contains the given key with a non-empty value.
     *
     * @param array $attributes
     * @param string $key
     *
     * @return string the non-empty value
     */
    private function getExpectedValue(array $attributes, string $key): string
    {
        if (empty($attributes[$key])) {
            $this->throwSchemaExceptionWithLocation('Parameter misses expected attribute "%s"', $key);
        }

        return $attributes[$key];
    }
}
