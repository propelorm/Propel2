<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Builder\Util\XmlToAppData;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\AppData;
use Propel\Generator\Model\Database;

use \DomDocument;
use \Exception;

/**
 * An abstract base Propel manager to perform work related to the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Jason van Zyl <jvanzyl@zenplex.com> (Torque)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
abstract class AbstractManager
{
    /**
     * Data models that we collect. One from each XML schema file.
     */
    protected $dataModels = array();

    /**
     * Map of data model name to database name.
     * Should probably stick to the convention
     * of them being the same but I know right now
     * in a lot of cases they won't be.
     */
    protected $dataModelDbMap;

    /**
     * DB encoding to use for XmlToAppData object
     */
    protected $dbEncoding = 'iso-8859-1';

    /**
     * Whether to perform validation (XSD) on the schema.xml file(s).
     * @var        boolean
     */
    protected $validate;

    /**
     * The XSD schema file to use for validation.
     * @var        string
     */
    protected $xsd;

    /**
     * XSL file to use to normalize (or otherwise transform) schema before validation.
     * @var        string
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
     * @var \closure
     */
    private $loggerClosure = null;

    /**
     * Have datamodels been initialized?
     * @var        boolean
     */
    private $dataModelsLoaded = false;

    /**
     * An initialized GeneratorConfig object.
     *
     * @var     \Propel\Generator\Config\GeneratorConfig
     */
    private $generatorConfig;

    /**
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * @param  array
     */
    public function setSchemas($schemas)
    {
        $this->schemas = $schemas;
    }

    /**
     * @param string $workingDirectory
     */
    public function setWorkingDirectory($workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }


    /**
     * Return the data models that have been
     * processed.
     *
     * @return     List data models
     */
    public function getDataModels()
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModels;
    }

    /**
     * Return the data model to database name map.
     *
     * @return     Hashtable data model name to database name map.
     */
    public function getDataModelDbMap()
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModelDbMap;
    }

    /**
     * Set whether to perform validation on the datamodel schema.xml file(s).
     *
     * @param      boolean $validate
     */
    public function setValidate($validate)
    {
        $this->validatealidate = (boolean) $validate;
    }

    /**
     * Set the XSD schema to use for validation of any datamodel schema.xml file(s).
     *
     * @param      string $xsd
     */
    public function setXsd($xsd)
    {
        $this->xsd = $xsd;
    }

    /**
     * Set the normalization XSLT to use to transform datamodel schema.xml file(s) before validation and parsing.
     *
     * @param      string $xsl
     */
    public function setXsl($xsl)
    {
        $this->xsl = $xsl;
    }

    /**
     * Set the current target database encoding.
     *
     * @param      string $encoding Target database encoding
     */
    public function setDbEncoding($encoding)
    {
        $this->dbEncoding = $encoding;
    }

    /**
     * @var \closure $logger	A logger closure
     */
    public function setLoggerClosure(\closure $logger)
    {
        $this->loggerClosure = $logger;
    }

    /**
     * Gets all matching XML schema files and loads them into data models for class.
     * @return     void
     */
    protected function loadDataModels()
    {
        $ads = array();
        $totalNbTables   = 0;
        $dataModelFiles  = $this->getSchemas();
        $defaultPlatform = $this->getGeneratorConfig()->getConfiguredPlatform();

        // Make a transaction for each file
        foreach ($dataModelFiles as $schema) {
            $dmFilename = $schema->getPathName();
            $this->log('Processing: ' . $schema->getFileName());

            $dom = new DomDocument('1.0', 'UTF-8');
            $dom->load($dmFilename);

            $this->includeExternalSchemas($dom, $srcDir);

            // normalize (or transform) the XML document using XSLT
            if ($this->getGeneratorConfig()->getBuildProperty('schemaTransform') && $this->xsl) {
                $this->log('Transforming ' . $dmFilename . ' using stylesheet ' . $this->xsl->getPath());

                if (!class_exists('\XSLTProcessor')) {
                    $this->log('Could not perform XLST transformation. Make sure PHP has been compiled/configured to support XSLT.');
                } else {
                    // normalize the document using normalizer stylesheet
                    $xslDom = new DomDocument('1.0', 'UTF-8');
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
                    throw new EngineException(<<<EOT
XML schema file ($xmlFile->getPath()) does not validate.
See warnings above for reasons validation failed (make sure error_reporting
is set to show E_WARNING if you don't see any)
EOT
                    , $this->getLocation());
                }
            }

            $xmlParser = new XmlToAppData($defaultPlatform, $this->dbEncoding);
            $xmlParser->setGeneratorConfig($this->getGeneratorConfig());
            $ad = $xmlParser->parseString($dom->saveXML(), $dmFilename);
            $nbTables = $ad->getDatabase(null, false)->countTables();
            $totalNbTables += $nbTables;

            $this->log(sprintf('  %d tables processed successfully', $nbTables));

            $ad->setName($dmFilename);
            $ads[] = $ad;
        }

        $this->log(sprintf('%d tables found in %d schema files.', $totalNbTables, count($dataModelFiles)));

        if (empty($ads)) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }

        foreach ($ads as $ad) {
            // map schema filename with database name
            $this->dataModelDbMap[$ad->getName()] = $ad->getDatabase(null, false)->getName();
        }

        if (count($ads) > 1 && $this->getGeneratorConfig()->getBuildProperty('packageObjectModel')) {
            $ad = $this->joinDataModels($ads);
            $this->dataModels = array($ad);
        } else {
            $this->dataModels = $ads;
        }

        foreach ($this->dataModels as &$ad) {
            $ad->doFinalInitialization();
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
     * @param      \DomDocument $dom
     * @param      string $srcDir
     */
    protected function includeExternalSchemas(DomDocument $dom, $srcDir)
    {
        $databaseNode = $dom->getElementsByTagName('database')->item(0);
        $externalSchemaNodes = $dom->getElementsByTagName('external-schema');

        $nbIncludedSchemas = 0;
        while ($externalSchema = $externalSchemaNodes->item(0)) {
            $include = $externalSchema->getAttribute('filename');

            $this->log('Processing external schema: ' . $include);

            $externalSchema->parentNode->removeChild($externalSchema);

            $externalSchemaDom = new DomDocument('1.0', 'UTF-8');
            $externalSchemaDom->load(realpath($externalSchemaFile));

            // The external schema may have external schemas of its own ; recurse
            $this->includeExternalSchemas($externalSchemaDom, $srcDir);
            foreach ($externalSchemaDom->getElementsByTagName('table') as $tableNode) {
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
     * @param      array[\Propel\Generator\Model\AppData] $ads The datamodels to join
     * @return     \Propel\Generator\Model\AppData        The single datamodel with all other datamodels joined in
     */
    protected function joinDataModels($ads)
    {
        $mainAppData = array_shift($ads);
        $mainAppData->joinAppDatas($ads);

        return $mainAppData;
    }

    /**
     * Gets the GeneratorConfig object for this manager or creates it on-demand.
     *
     * @return     \Propel\Generator\Config\GeneratorConfigInterface
     */
    protected function getGeneratorConfig()
    {
        return $this->generatorConfig;
    }

    /**
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
        } else {
            var_dump($message);
        }
    }

    /**
     * Returns an array of properties as key/value pairs from an input file.
     *
     * @param string $file  A file properties.
     * @return array        An array of properties as key/value pairs.
     */
    protected function getProperties($file)
    {
        $properties = array();

        if (false === $lines = @file($file)) {
            throw new Exception(sprintf('Unable to parse contents of "%s".', $file));
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' == $line || in_array($line[0], array('#', ';'))) {
                continue;
            }

            $pos = strpos($line, '=');
            $properties[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
        }

        return $properties;
    }
}
