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

require_once 'propel/phing/PropelDataModelTemplateTask.php';
include_once 'propel/engine/builder/om/ClassTools.php';

/**
 * This Task creates the OM classes based on the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.phing
 */
class PropelDataDTDTask extends PropelDataModelTemplateTask {


	public function main() {

		// check to make sure task received all correct params
		$this->validate();

		if (!$this->mapperElement) {
			throw new BuildException("You must use a <mapper/> element to describe how names should be transformed.");
		}

		$basepath = $this->getOutputDirectory();

		// Get new Capsule context
		$generator = $this->createContext();
		$generator->put("basepath", $basepath); // make available to other templates

		// we need some values that were loaded into the template context
		$basePrefix = $generator->get('basePrefix');
		$project = $generator->get('project');

		foreach ($this->getDataModels() as $dataModel) {

			$this->log("Processing Datamodel : " . $dataModel->getName());

			foreach ($dataModel->getDatabases() as $database) {

				$outFile = $this->getMappedFile($dataModel->getName());

				$generator->put("tables", $database->getTables());
				$generator->parse("data/dtd/dataset.tpl", $outFile->getAbsolutePath());

				$this->log("Generating DTD for database: " . $database->getName());
				$this->log("Creating DTD file: " . $outFile->getPath());

				foreach ($database->getTables() as $tbl) {
					$this->log("\t + " . $tbl->getName());
					$generator->put("table", $tbl);
					$generator->parse("data/dtd/table.tpl", $outFile->getAbsolutePath(), true);
				}

			} // foreach database

		} // foreach dataModel


	} // main()
}
