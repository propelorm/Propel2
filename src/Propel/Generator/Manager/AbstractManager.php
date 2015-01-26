<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;

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
     */
    protected $dataModels = array();

    /**
     * @var Database[]
     */
    protected $databases;

    /**
     * Map of data model name to database name.
     * Should probably stick to the convention
     * of them being the same but I know right now
     * in a lot of cases they won't be.
     */
    protected $dataModelDbMap;

    /**
     * DB encoding to use for SchemaReader object
     */
    protected $dbEncoding = 'UTF-8';

    /**
     * Whether to perform validation (XSD) on the schema.xml file(s).
     * @var boolean
     */
    protected $validate;

    /**
     * The XSD schema file to use for validation.
     * @var string
     */
    protected $xsd;

    /**
     * XSL file to use to normalize (or otherwise transform) schema before validation.
     * @var string
     */
    protected $xsl;

    /**
     * Gets list of all used xml schemas
     *
     * @var array
     */
    protected $schemas = array();

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var \Closure
     */
    private $loggerClosure = null;

    /**
     * Have datamodels been initialized?
     * @var boolean
     */
    private $dataModelsLoaded = false;

    /**
     * An initialized GeneratorConfig object.
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * Returns the list of schemas.
     *
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * Sets the schemas list.
     *
     * @param array
     */
    public function setSchemas($schemas)
    {
        $this->schemas = $schemas;
    }

    /**
     * Sets the working directory path.
     *
     * @param string $workingDirectory
     */
    public function setWorkingDirectory($workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * Returns the working directory path.
     *
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }


    /**
     * Returns the data models that have been
     * processed.
     *
     * @return Schema[]
     */
    public function getDataModels()
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
    public function getDataModelDbMap()
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModelDbMap;
    }

    /**
     * @return Database[]
     */
    public function getDatabases()
    {
        if (null === $this->databases) {
            $databases = array();
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
     * @param  string $name
     * @return Database|null
     */
    public function getDatabase($name)
    {
        $dbs = $this->getDatabases();
        return @$dbs[$name];
    }

    /**
     * Sets whether to perform validation on the datamodel schema.xml file(s).
     *
     * @param boolean $validate
     */
    public function setValidate($validate)
    {
        $this->validate = (boolean) $validate;
    }

    /**
     * Sets the XSD schema to use for validation of any datamodel schema.xml
     * file(s).
     *
     * @param string $xsd
     */
    public function setXsd($xsd)
    {
        $this->xsd = $xsd;
    }

    /**
     * Sets the normalization XSLT to use to transform datamodel schema.xml
     * file(s) before validation and parsing.
     *
     * @param string $xsl
     */
    public function setXsl($xsl)
    {
        $this->xsl = $xsl;
    }

    /**
     * Sets the current target database encoding.
     *
     * @param string $encoding Target database encoding
     */
    public function setDbEncoding($encoding)
    {
        $this->dbEncoding = $encoding;
    }

    /**
     * Sets a logger closure.
     *
     * @param \Closure $logger
     */
    public function setLoggerClosure(\Closure $logger)
    {
        $this->loggerClosure = $logger;
    }

    /**
     * Returns all matching XML schema files and loads them into data models for
     * class.
     */
    protected function loadDataModels()
    {
        $schemas = array();
        $totalNbTables   = 0;
        $dataModelFiles  = $this->getSchemas();
        $defaultPlatform = $this->getGeneratorConfig()->getConfiguredPlatform();

        // Make a transaction for each file
        foreach ($dataModelFiles as $schema) {
            $dmFilename = $schema->getPathName();
            $this->log('Processing: ' . $schema->getFileName());

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->load($dmFilename);

            $this->includeExternalSchemas($dom, $schema->getPath());

            // normalize (or transform) the XML document using XSLT
            if ($this->getGeneratorConfig()->get()['generator']['schema']['transform'] && $this->xsl) {
                $this->log('Transforming ' . $dmFilename . ' using stylesheet ' . $this->xsl->getPath());

                if (!class_exists('\XSLTProcessor')) {
                    $this->log('Could not perform XLST transformation. Make sure PHP has been compiled/configured to support XSLT.');
                } else {
                    // normalize the document using normalizer stylesheet
                    $xslDom = new \DOMDocument('1.0', 'UTF-8');
                    $xslDom->load($this->xsl->getAbsolutePath());
                    $xsl = new \XsltProcessor();
                    $xsl->importStyleSheet($xslDom);
                    $dom = $xsl->transformToDoc($dom);
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
            $schema = $xmlParser->parseString($dom->saveXML(), $dmFilename);
            $nbTables = $schema->getDatabase(null, false)->countTables();
            $totalNbTables += $nbTables;

            $this->log(sprintf('  %d tables processed successfully', $nbTables));

            $schema->setName($dmFilename);
            $schemas[] = $schema;
        }

        $this->log(sprintf('%d tables found in %d schema files.', $totalNbTables, count($dataModelFiles)));

        if (empty($schemas)) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }

        foreach ($schemas as $schema) {
            // map schema filename with database name
            $this->dataModelDbMap[$schema->getName()] = $schema->getDatabase(null, false)->getName();
        }

        if (count($schemas) > 1 && $this->getGeneratorConfig()->get()['generator']['packageObjectModel']) {
            $schema = $this->joinDataModels($schemas);
            $this->dataModels = array($schema);
        } else {
            $this->dataModels = $schemas;
        }

        foreach ($this->dataModels as &$schema) {
            $schema->doFinalInitialization();
        }

        $this->dataModelsLoaded = true;
    }

    /**
     * Replaces all external-schema nodes with the content of xml schema that node refers to
     *
     * Recurses to include any external schema referenced from in an included xml (and deeper)
     * Note: this function very much assumes at least a reasonable XML schema, maybe it'll proof
     * users don't have those and adding some more informative exceptions would be better
     *
     * @param \DOMDocument $dom
     * @param string       $srcDir
     */
    protected function includeExternalSchemas(\DOMDocument $dom, $srcDir)
    {
        $databaseNode = $dom->getElementsByTagName('database')->item(0);
        $externalSchemaNodes = $dom->getElementsByTagName('external-schema');

        $nbIncludedSchemas = 0;
        while ($externalSchema = $externalSchemaNodes->item(0)) {
            $include = $externalSchema->getAttribute('filename');
            $referenceOnly = $externalSchema->getAttribute('referenceOnly');
            $this->log('Processing external schema: ' . $include);

            $externalSchema->parentNode->removeChild($externalSchema);

            if (!is_readable($include)) {
                throw new BuildException("External schema '$include' does not exist");
            }

            $externalSchemaDom = new \DOMDocument('1.0', 'UTF-8');
            $externalSchemaDom->load(realpath($include));

            // The external schema may have external schemas of its own ; recurs
            $this->includeExternalSchemas($externalSchemaDom, $srcDir);
            foreach ($externalSchemaDom->getElementsByTagName('table') as $tableNode) {
                if ($referenceOnly) {
                    $tableNode->setAttribute("skipSql", "true");
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
     * @param  array  $schemas
     * @return Schema
     */
    protected function joinDataModels(array $schemas)
    {
        $mainSchema = array_shift($schemas);
        $mainSchema->joinSchemas($schemas);

        return $mainSchema;
    }

    /**
     * Returns the GeneratorConfig object for this manager or creates it
     * on-demand.
     *
     * @return GeneratorConfigInterface
     */
    protected function getGeneratorConfig()
    {
        return $this->generatorConfig;
    }

    /**
     * Sets the GeneratorConfigInterface implementation.
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        $this->generatorConfig = $generatorConfig;
    }

    protected function validate()
    {
        if ($this->validate) {
            if (!$this->xsd) {
                throw new BuildException("'validate' set to TRUE, but no XSD specified (use 'xsd' attribute).");
            }
        }
    }

    protected function log($message)
    {
        if (null !== $this->loggerClosure) {
            $closure = $this->loggerClosure;
            $closure($message);
        }
    }

    /**
     * Returns an array of properties as key/value pairs from an input file.
     *
     * @param  string $file
     * @return array
     */
    protected function getProperties($file)
    {
        $properties = array();

        if (false === $lines = @file($file)) {
            throw new \Exception(sprintf('Unable to parse contents of "%s".', $file));
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || in_array($line[0], array('#', ';'))) {
                continue;
            }

            $pos = strpos($line, '=');
            $properties[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
        }

        return $properties;
    }
}
