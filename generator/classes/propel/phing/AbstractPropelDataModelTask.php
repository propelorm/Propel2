<?php

/*
 *  $Id: AbstractPropelDataModelTask.php,v 1.6 2005/03/29 11:28:39 pachanga Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

//include_once 'phing/tasks/ext/CapsuleTask.php';
require_once 'phing/Task.php';
include_once 'propel/engine/database/model/AppData.php';
include_once 'propel/engine/database/model/Database.php';
include_once 'propel/engine/database/transform/XmlToAppData.php';

/**
 * An abstract base Propel task to perform work related to the XML schema file.
 *
 * The subclasses invoke templates to do the actual writing of the resulting files.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@zenplex.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @package propel.phing
 */
abstract class AbstractPropelDataModelTask extends Task {

    /**
     * Fileset of XML schemas which represent our data models.
     * @var array Fileset[]
     */
    protected $schemaFilesets = array();

    /**
     * Data models that we collect. One from each XML schema file.
     */
    protected $dataModels = array();

    /**
     * Have datamodels been initialized?
     * @var boolean
     */
    private $dataModelsLoaded = false;

    /**
     * Map of data model name to database name.
     * Should probably stick to the convention
     * of them being the same but I know right now
     * in a lot of cases they won't be.
     */
    protected $dataModelDbMap;

    /**
     * Hashtable containing the names of all the databases
     * in our collection of schemas.
     */
    protected $databaseNames;

    /**
     * The target database(s) we are generating SQL
     * for. Right now we can only deal with a single
     * target, but we will support multiple targets
     * soon.
     */
    protected $targetDatabase;

    /**
     * DB encoding to use for XmlToAppData object
     */
    protected $dbEncoding = 'iso-8859-1';

    /**
     * Target PHP package to place the generated files in.
     */
    protected $targetPackage;

    /**
     * @var Mapper
     */
    protected $mapperElement;

    /**
     * Destination directory for results of template scripts.
     * @var File
     */
    protected $outputDirectory;

    /**
     * Path where Capsule looks for templates.
     * @var File
     */
    protected $templatePath;

    /**
     * Return the data models that have been
     * processed.
     *
     * @return List data models
     */
    public function getDataModels()
    {
        if (!$this->dataModelsLoaded) $this->loadDataModels();
        return $this->dataModels;
    }

    /**
     * Return the data model to database name map.
     *
     * @return Hashtable data model name to database name map.
     */
    public function getDataModelDbMap()
    {
        if (!$this->dataModelsLoaded) $this->loadDataModels();
        return $this->dataModelDbMap;
    }

    /**
     * Adds a set of xml schema files (nested fileset attribute).
     *
     * @param set a Set of xml schema files
     */
    public function addSchemaFileset(Fileset $set)
    {
        $this->schemaFilesets[] = $set;
    }

    /**
     * Get the current target database.
     *
     * @return String target database(s)
     */
    public function getTargetDatabase()
    {
        return $this->targetDatabase;
    }

    /**
     * Set the current target database. (e.g. mysql, oracle, ..)
     *
     * @param v target database(s)
     */
    public function setTargetDatabase($v)
    {
        $this->targetDatabase = $v;
    }

    /**
     * Get the current target package.
     *
     * @return string target PHP package.
     */
    public function getTargetPackage()
    {
        return $this->targetPackage;
    }

    /**
     * Set the current target package. This is where generated PHP classes will
     * live.
     *
     * @param string $v target PHP package.
     */
    public function setTargetPackage($v)
    {
        $this->targetPackage = $v;
    }

    /**
     * [REQUIRED] Set the path where Capsule will look
     * for templates using the file template
     * loader.
     * @return void
     * @throws Exception
     */
    public function setTemplatePath($templatePath) {
        $resolvedPath = "";
        $tok = strtok($templatePath, ",");
        while ( $tok ) {
            // resolve relative path from basedir and leave
            // absolute path untouched.
            $fullPath = $this->project->resolveFile($tok);
            $cpath = $fullPath->getCanonicalPath();
            if ($cpath === false) {
                $this->log("Template directory does not exist: " . $fullPath->getAbsolutePath());
            } else {
                $resolvedPath .= $cpath;
            }
            $tok = strtok(",");
            if ( $tok ) {
                $resolvedPath .= ",";
            }
        }
        $this->templatePath = $resolvedPath;
     }

    /**
     * Get the path where Velocity will look
     * for templates using the file template
     * loader.
     * @return string
     */
    public function getTemplatePath() {
        return $this->templatePath;
    }

    /**
     * [REQUIRED] Set the output directory. It will be
     * created if it doesn't exist.
     * @param File $outputDirectory
     * @return void
     * @throws Exception
     */
    public function setOutputDirectory(File $outputDirectory) {
        try {
            if (!$outputDirectory->exists()) {
                $this->log("Output directory does not exist, creating: " . $outputDirectory->getPath(),PROJECT_MSG_VERBOSE);
                if (!$outputDirectory->mkdirs()) {
                    throw new IOException("Unable to create Ouptut directory: " . $outputDirectory->getAbsolutePath());
                }
            }
            $this->outputDirectory = $outputDirectory->getCanonicalPath();
        } catch (IOException $ioe) {
            throw new BuildException($ioe);
        }
    }

    /**
     * Set the current target database encoding.
     *
     * @param v target database encoding
     */
    public function setDbEncoding($v)
    {
       $this->dbEncoding = $v;
    }

    /**
     * Get the output directory.
     * @return string
     */
    public function getOutputDirectory() {
        return $this->outputDirectory;
    }

    /**
     * Nested creator, creates one Mapper for this task.
     *
     * @return  Mapper  The created Mapper type object.
     * @throws  BuildException
     */
    public function createMapper() {
        if ($this->mapperElement !== null) {
            throw new BuildException("Cannot define more than one mapper.", $this->location);
        }
        $this->mapperElement = new Mapper($this->project);
        return $this->mapperElement;
    }

    /**
     * Maps the passed in name to a new filename & returns resolved File object.
     * @param string $from
     * @return File Resolved File object.
     * @throws BuilException    - if no Mapper element se
     *                          - if unable to map new filename.
     */
    protected function getMappedFile($from)
    {
        if(!$this->mapperElement) {
            throw new BuildException("This task requires you to use a <mapper/> element to describe how filename changes should be handled.");
        }

        $mapper = $this->mapperElement->getImplementation();
        $mapped = $mapper->main($from);
        if (!$mapped) {
            throw new BuildException("Cannot create new filename based on: " . $from);
        }
        // Mappers always return arrays since it's possible for some mappers to map to multiple names.
        $outFilename = array_shift($mapped);
        $outFile = new File($this->getOutputDirectory(), $outFilename);
        return $outFile;
    }

    /**
     * Gets all matching XML schema files and loads them into data models for class.
     * @return void
     */
    protected function loadDataModels()
    {

        // Get all matched files from schemaFilesets
        foreach($this->schemaFilesets as $fs) {
            $ds = $fs->getDirectoryScanner($this->project);
            $srcDir = $fs->getDir($this->project);

            $dataModelFiles = $ds->getIncludedFiles();

            // Make a transaction for each file
            foreach($dataModelFiles as $dmFilename) {
                $this->log("Processing: ".$dmFilename);
                $f = new File($srcDir, $dmFilename);
                $xmlParser = new XmlToAppData($this->getTargetDatabase(),
                                              $this->getTargetPackage(),
                                              $this->dbEncoding);
                $ad = $xmlParser->parseFile($f->__toString());
                $ad->setName($f->getName());
                $this->dataModels[] = $ad;
            }
        }

        $this->databaseNames = array();
        $this->dataModelDbMap = array();

        // Different datamodels may state the same database
        // names, we just want the unique names of databases.
        foreach($this->dataModels as $dm) {
            $database = $dm->getDatabase();
            $this->databaseNames[$database->getName()] = $database->getName(); // making list of *unique* dbnames.
            $this->dataModelDbMap[$dm->getName()] = $database->getName();
        }

        $this->dataModelsLoaded = true;

    }

    /**
     * Creates a new Capsule context with some basic properties set.
     * (Capsule is a simple PHP encapsulation system -- aka a php "template" class.)
     * @return Capsule
     */
    protected function createContext() {

        $context = new Capsule();

        // Make sure the output directory exists, if it doesn't
        // then create it.
        $outputDir = new File($this->outputDirectory);
        if (!$outputDir->exists()) {
            $this->log("Output directory does not exist, creating: " . $outputDir->getAbsolutePath());
            $outputDir->mkdirs();
        }

        // Place our set of data models into the context along
        // with the names of the databases as a convenience for now.
        $context->put("targetDatabase", $this->targetDatabase);
        $context->put("targetPackage", $this->targetPackage);
        $context->put("now", strftime("%c"));

        $this->log("Target database type: " . $this->targetDatabase);
        $this->log("Target package: " . $this->targetPackage);
        $this->log("Using template path: " . $this->templatePath);
        $this->log("Output directory: " . $this->outputDirectory);

        $context->setTemplatePath($this->templatePath);
        $context->setOutputDirectory($this->outputDirectory);

        $this->populateContextProperties($context);

        return $context;
    }

  /**
   * Fetches the propel.xxx properties from project, renaming the propel.xxx properties to just xxx.
   *
   * Also, renames any xxx.yyy properties to xxxYyy as PHP doesn't like the xxx.yyy syntax.
   *
   * @return array Assoc array of properties.
   */
  protected function getPropelProperties()
  {
    $allProps = $this->getProject()->getProperties();
    $renamedPropelProps = array();
        foreach ($allProps as $key => $propValue) {
            if (strpos($key, "propel.") === 0) {
                $newKey = substr($key, strlen("propel."));
                $j = strpos($newKey, '.');
                while ($j !== false) {
                    $newKey =  substr($newKey, 0, $j) . ucfirst(substr($newKey, $j + 1));
                    $j = strpos($newKey, '.');
                }
        $renamedPropelProps[$newKey] = $propValue;
            }
        }
    return $renamedPropelProps;
  }

    /**
     * Adds the propel.xxx properties to the passed Capsule context, changing names to just xxx.
     *
     * Also, move xxx.yyy properties to xxxYyy as PHP doesn't like the xxx.yyy syntax.
     *
     * @param Capsule $context
   * @see getPropelProperties()
     */
    public function populateContextProperties(Capsule $context)
    {
        foreach ($this->getPropelProperties() as $key => $propValue) {
      $this->log('Adding property ${' . $key . '} to context', PROJECT_MSG_DEBUG);
      $context->put($key, $propValue);
        }
    }

  /**
   * Checks this class against Basic requrements of any propel datamodel task.
   *
   * @throws BuildException 	- if schema fileset was not defined
   * 							- if no output directory was specified
   */
    protected function validate()
    {
        if (empty($this->schemaFilesets)) {
            throw new BuildException("You must specify a fileset of XML schemas.");
        }

        // Make sure the output directory is set.
        if ($this->outputDirectory === null) {
            throw new BuildException("The output directory needs to be defined!");
        }

    }

}
