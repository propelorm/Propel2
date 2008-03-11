<?php

/*
 *  $Id$
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

include_once 'propel/engine/database/model/AppData.php';
include_once 'propel/engine/database/model/Database.php';
include_once 'propel/engine/database/transform/XmlToAppData.php';
include_once 'propel/engine/builder/util/transform/XmlToDataSQL.php';

/**
 * Task that transforms XML datadump files into files containing SQL INSERT statements.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Jason van Zyl  <jvanzyl@periapt.com> (Torque)
 * @author     John McNally  <jmcnally@collab.net> (Torque)
 * @author     Fedor Karpelevitch  <fedor.karpelevitch@home.com> (Torque)
 * @version    $Revision$
 * @package    propel.phing
 */
class PropelDataSQLTask extends AbstractPropelDataModelTask {

	/**
	 * Properties file that maps an SQL file to a particular database.
	 * @var        PhingFile
	 */
	private $sqldbmap;

	/**
	 * Properties file that maps a data XML file to a particular database.
	 * @var        PhingFile
	 */
	private $datadbmap;

	/**
	 * The base directory in which to find data XML files.
	 * @var        PhingFile
	 */
	private $srcDir;

	/**
	 * Set the file that maps between SQL files and databases.
	 *
	 * @param      PhingFile $sqldbmap the sql -> db map.
	 * @return     void
	 */
	public function setSqlDbMap(PhingFile $sqldbmap)
	{
		$this->sqldbmap = $sqldbmap;
	}

	/**
	 * Get the file that maps between SQL files and databases.
	 *
	 * @return     PhingFile sqldbmap.
	 */
	public function getSqlDbMap()
	{
		return $this->sqldbmap;
	}

	/**
	 * Set the file that maps between data XML files and databases.
	 *
	 * @param      PhingFile $sqldbmap the db map
	 * @return     void
	 */
	public function setDataDbMap(PhingFile $datadbmap)
	{
		$this->datadbmap = $datadbmap;
	}

	/**
	 * Get the file that maps between data XML files and databases.
	 *
	 * @return     PhingFile $datadbmap.
	 */
	public function getDataDbMap()
	{
		return $this->datadbmap;
	}

	/**
	 * Set the src directory for the data xml files listed in the datadbmap file.
	 * @param      PhingFile $srcDir data xml source directory
	 */
	public function setSrcDir(PhingFile $srcDir)
	{
		$this->srcDir = $srcDir;
	}

	/**
	 * Get the src directory for the data xml files listed in the datadbmap file.
	 *
	 * @return     PhingFile data xml source directory
	 */
	public function getSrcDir()
	{
		return $this->srcDir;
	}

	/**
	 * Search through all data models looking for matching database.
	 * @return     Database or NULL if none found.
	 */
	private function getDatabase($name)
	{
		foreach ($this->getDataModels() as $dm) {
			foreach ($dm->getDatabases() as $db) {
				if ($db->getName() == $name) {
					return $db;
				}
			}
		}
	}

	/**
	 * Main method parses the XML files and creates SQL files.
	 *
	 * @return     void
	 * @throws     Exception If there is an error parsing the data xml.
	 */
	public function main()
	{
		$this->validate();

		$targetDatabase = $this->getTargetDatabase();

		$platform = $this->getGeneratorConfig()->getConfiguredPlatform();

		// Load the Data XML -> DB Name properties
		$map = new Properties();
		try {
			$map->load($this->getDataDbMap());
		} catch (IOException $ioe) {
			throw new BuildException("Cannot open and process the datadbmap!", $ioe);
		}

		// Parse each file in the data -> db map
		foreach ($map->keys() as $dataXMLFilename) {

			$dataXMLFile = new PhingFile($this->srcDir, $dataXMLFilename);

			// if file exists then proceed
			if ($dataXMLFile->exists()) {

				$dbname = $map->get($dataXMLFilename);

				$db = $this->getDatabase($dbname);

				if (!$db) {
					throw new BuildException("Cannot find instantiated Database for name '$dbname' from datadbmap file.");
				}

				$db->setPlatform($platform);

				$outFile = $this->getMappedFile($dataXMLFilename);
				$sqlWriter = new FileWriter($outFile);

				$this->log("Creating SQL from XML data dump file: " . $dataXMLFile->getAbsolutePath());

				try {
					$dataXmlParser = new XmlToDataSQL($db, $this->getGeneratorConfig(), $this->dbEncoding);
					$dataXmlParser->transform($dataXMLFile, $sqlWriter);
				} catch (Exception $e) {
					throw new BuildException("Exception parsing data XML: " . $e->getMessage(), $x);
				}

				// Place the generated SQL file(s)
				$p = new Properties();
				if ($this->getSqlDbMap()->exists()) {
					$p->load($this->getSqlDbMap());
				}

				$p->setProperty($outFile->getName(), $db->getName());
				$p->store($this->getSqlDbMap(), "Sqlfile -> Database map");

			} else {
				$this->log("File '" . $dataXMLFile->getAbsolutePath()
						. "' in datadbmap does not exist, so skipping it.", Project::MSG_WARN);
			}

		} // foreach data xml file

	} // main()

}
