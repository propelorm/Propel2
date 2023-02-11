<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Manager;

use Closure;
use DOMDocument;
use Exception;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;
use RuntimeException;
use XSLTProcessor;

/**
 * An abstract base Propel manager to perform work related to the XML schema
 * file.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@zenplex.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
abstract class AbstractManager
{
    /**
     * Data models that we collect. One from each XML schema file.
     *
     * @var list<\Propel\Generator\Model\Schema>
     */
    protected $dataModels = [];

    /**
     * @var array<\Propel\Generator\Model\Database>
     */
    protected $databases;

    /**
     * Map of data model name to database name.
     * Should probably stick to the convention
     * of them being the same but I know right now
     * in a lot of cases they won't be.
     *
     * @var array
     */
    protected $dataModelDbMap;

    /**
     * DB encoding to use for SchemaReader object
     *
     * @var string
     */
    protected $dbEncoding = 'UTF-8';

    /**
     * Whether to perform validation (XSD) on the schema.xml file(s).
     *
     * @var bool
     */
    protected $validate = false;

    /**
     * The XSD schema file to use for validation.
     *
     * @var mixed
     */
    protected $xsd;

    /**
     * XSL file to use to normalize (or otherwise transform) schema before validation.
     *
     * @deprecated Not in use and not working due to missing class.
     *
     * @var mixed
     */
    protected $xsl;

    /**
     * Gets list of all used xml schemas
     *
     * @var array
     */
    protected $schemas = [];

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var \Closure|null
     */
    private $loggerClosure;

    /**
     * Have datamodels been initialized?
     *
     * @var bool
     */
    private $dataModelsLoaded = false;

    /**
     * An initialized GeneratorConfig object.
     *
     * @var \Propel\Generator\Config\GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * Returns the list of schemas.
     *
     * @return array
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Sets the schemas list.
     *
     * @param array $schemas
     *
     * @return void
     */
    public function setSchemas(array $schemas): void
    {
        $this->schemas = $schemas;
    }

    /**
     * Sets the working directory path.
     *
     * @param string $workingDirectory
     *
     * @return void
     */
    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * Returns the working directory path.
     *
     * @return string|null
     */
    public function getWorkingDirectory(): ?string
    {
        return $this->workingDirectory;
    }

    /**
     * Returns the data models that have been
     * processed.
     *
     * @return array<\Propel\Generator\Model\Schema>
     */
    public function getDataModels(): array
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModels;
    }

    /**
     * Returns the data model to database name map.
     *
     * @return array
     */
    public function getDataModelDbMap(): array
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModelDbMap;
    }

    /**
     * @return array<\Propel\Generator\Model\Database>
     */
    public function getDatabases(): array
    {
        if ($this->databases === null) {
            $databases = [];
            foreach ($this->getDataModels() as $dataModel) {
                foreach ($dataModel->getDatabases() as $database) {
                    if (!isset($databases[$database->getName()])) {
                        $databases[$database->getName()] = $database;
                    } else {
                        $tables = $database->getTables();
                        // Merge tables from different schema.xml to the same database
                        foreach ($tables as $table) {
                            if (!$databases[$database->getName()]->hasTable($table->getName(), true)) {
                                $databases[$database->getName()]->addTable($table);
                            }
                        }
                    }
                }
            }
            $this->databases = $databases;
        }

        return $this->databases;
    }

    /**
     * @param string $name
     *
     * @return \Propel\Generator\Model\Database|null
     */
    public function getDatabase(string $name): ?Database
    {
        $dbs = $this->getDatabases();

        return $dbs[$name] ?? null;
    }

    /**
     * Sets whether to perform validation on the datamodel schema.xml file(s).
     *
     * @param bool $validate
     *
     * @return void
     */
    public function setValidate(bool $validate): void
    {
        $this->validate = $validate;
    }

    /**
     * Sets the XSD schema to use for validation of any datamodel schema.xml
     * file(s).
     *
     * @param string $xsd
     *
     * @return void
     */
    public function setXsd(string $xsd): void
    {
        $this->xsd = $xsd;
    }

    /**
     * Sets the normalization XSLT to use to transform datamodel schema.xml
     * file(s) before validation and parsing.
     *
     * @param mixed $xsl
     *
     * @return void
     */
    public function setXsl($xsl): void
    {
        $this->xsl = $xsl;
    }

    /**
     * Sets the current target database encoding.
     *
     * @param string $encoding Target database encoding
     *
     * @return void
     */
    public function setDbEncoding(string $encoding): void
    {
        $this->dbEncoding = $encoding;
    }

    /**
     * Sets a logger closure.
     *
     * @param \Closure $logger
     *
     * @return void
     */
    public function setLoggerClosure(Closure $logger): void
    {
        $this->loggerClosure = $logger;
    }

    /**
     * Returns all matching XML schema files and loads them into data models for
     * class.
     *
     * @throws \Propel\Generator\Exception\EngineException
     * @throws \RuntimeException
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return void
     */
    protected function loadDataModels(): void
    {
        $schemas = [];
        $totalNbTables = 0;
        $dataModelFiles = $this->getSchemas();
        $defaultPlatform = $this->getGeneratorConfig()->getConfiguredPlatform();

        // Make a transaction for each file
        foreach ($dataModelFiles as $schema) {
            $dmFilename = $schema->getPathName();
            $this->log('Processing: ' . $schema->getFileName());

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->load($dmFilename);

            $this->includeExternalSchemas($dom, $schema->getPath());

            // normalize (or transform) the XML document using XSLT
            if ($this->getGeneratorConfig()->get()['generator']['schema']['transform'] && $this->xsl) {
                $this->log('Transforming ' . $dmFilename . ' using stylesheet ' . $this->xsl->getPath());

                if (!class_exists('\XSLTProcessor')) {
                    $this->log('Could not perform XLST transformation. Make sure PHP has been compiled/configured to support XSLT.');
                } else {
                    // normalize the document using normalizer stylesheet
                    $xslDom = new DOMDocument('1.0', 'UTF-8');
                    $xslDom->load($this->xsl->getAbsolutePath());
                    $xsl = new XSLTProcessor();
                    $xsl->importStyleSheet($xslDom);
                    $dom = $xsl->transformToDoc($dom);

                    if ($dom === false) {
                        throw new RuntimeException('XSLTProcessor transformation to a DOMDocument failed.');
                    }
                }
            }

            // validate the XML document using XSD schema
            if ($this->validate && $this->xsd) {
                $this->log('  Validating XML using schema ' . $this->xsd->getPath());

                if (!$dom->schemaValidate($this->xsd->getAbsolutePath())) {
                    throw new EngineException(sprintf("XML schema file (%s) does not validate. See warnings above for reasons validation failed (make sure error_reporting is set to show E_WARNING if you don't see any).", $dmFilename), $this->getLocation());
                }
            }

            $xmlParser = new SchemaReader($defaultPlatform, $this->dbEncoding);
            $xmlParser->setGeneratorConfig($this->getGeneratorConfig());
            $schema = $xmlParser->parseString((string)$dom->saveXML(), $dmFilename);
            $nbTables = $schema->getDatabase(null, false)->countTables();
            $totalNbTables += $nbTables;

            $this->log(sprintf('  %d tables processed successfully', $nbTables));

            $schema->setName($dmFilename);
            $schemas[] = $schema;
        }

        $this->log(sprintf('%d tables found in %d schema files.', $totalNbTables, count($dataModelFiles)));

        if (!$schemas) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }

        foreach ($schemas as $schema) {
            // map schema filename with database name
            $this->dataModelDbMap[$schema->getName()] = $schema->getDatabase(null, false)->getName();
        }

        if (count($schemas) > 1 && $this->getGeneratorConfig()->get()['generator']['packageObjectModel']) {
            $schema = $this->joinDataModels($schemas);
            $this->dataModels = [$schema];
        } else {
            $this->dataModels = $schemas;
        }

        foreach ($this->dataModels as &$schema) {
            $schema->doFinalInitialization();
        }

        $this->dataModelsLoaded = true;
    }

    /**
     * Replaces all external-schema nodes with the content of XML schema that node refers to
     *
     * Recurses to include any external schema referenced from in an included XML (and deeper)
     * Note: this function very much assumes at least a reasonable XML schema, maybe it'll proof
     * users don't have those and adding some more informative exceptions would be better
     *
     * @param \DOMDocument $dom
     * @param string $srcDir
     *
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return int number of included external schemas
     */
    protected function includeExternalSchemas(DOMDocument $dom, string $srcDir): int
    {
        $databaseNode = $dom->getElementsByTagName('database')->item(0);
        $externalSchemaNodes = $dom->getElementsByTagName('external-schema');

        $nbIncludedSchemas = 0;
        while ($externalSchema = $externalSchemaNodes->item(0)) {
            $filePath = $externalSchema->getAttribute('filename');
            $referenceOnly = $externalSchema->getAttribute('referenceOnly');
            $this->log('Processing external schema: ' . $filePath);

            $externalSchema->parentNode->removeChild($externalSchema);

            $externalSchemaPath = realpath($srcDir . DIRECTORY_SEPARATOR . $filePath);
            if ($externalSchemaPath === false) {
                $externalSchemaPath = realpath($filePath);
            }
            if ($externalSchemaPath === false || !is_readable($externalSchemaPath)) {
                throw new BuildException("Cannot read external schema at '$filePath'");
            }

            $externalSchemaDom = new DOMDocument('1.0', 'UTF-8');
            $externalSchemaDom->load($externalSchemaPath);

            $this->includeExternalSchemas($externalSchemaDom, dirname($externalSchemaPath));
            foreach ($externalSchemaDom->getElementsByTagName('table') as $tableNode) {
                if ($referenceOnly === 'true') {
                    $tableNode->setAttribute('skipSql', 'true');
                }
                $databaseNode->appendChild($dom->importNode($tableNode, true));
            }

            $nbIncludedSchemas++;
        }

        return $nbIncludedSchemas;
    }

    /**
     * Joins the datamodels collected from schema.xml files into one big datamodel.
     * We need to join the datamodels in this case to allow for foreign keys
     * that point to tables in different packages.
     *
     * @param array $schemas
     *
     * @return \Propel\Generator\Model\Schema
     */
    protected function joinDataModels(array $schemas): Schema
    {
        $mainSchema = array_shift($schemas);
        $mainSchema->joinSchemas($schemas);

        return $mainSchema;
    }

    /**
     * Returns the GeneratorConfig object for this manager or creates it
     * on-demand.
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface
     */
    protected function getGeneratorConfig(): GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Sets the GeneratorConfigInterface implementation.
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig): void
    {
        $this->generatorConfig = $generatorConfig;
    }

    /**
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return void
     */
    protected function validate(): void
    {
        if ($this->validate) {
            if (!$this->xsd) {
                throw new BuildException("'validate' set to TRUE, but no XSD specified (use 'xsd' attribute).");
            }
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function log(string $message): void
    {
        if ($this->loggerClosure !== null) {
            $closure = $this->loggerClosure;
            $closure($message);
        }
    }

    /**
     * Returns an array of properties as key/value pairs from an input file.
     *
     * @param string $file
     *
     * @throws \Exception
     *
     * @return array<string>
     */
    protected function getProperties(string $file): array
    {
        $properties = [];

        $lines = @file($file);
        if ($lines === false) {
            throw new Exception(sprintf('Unable to parse contents of "%s".', $file));
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if (!$line || in_array($line[0], ['#', ';'], true)) {
                continue;
            }

            $length = strpos($line, '=') ?: null;
            $properties[trim(substr($line, 0, $length))] = trim(substr($line, $length + 1));
        }

        return $properties;
    }
}
