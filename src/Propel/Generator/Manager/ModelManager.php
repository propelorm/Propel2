<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Builder\Om\ClassTools;
use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Util\Filesystem;

/**
 * This manager creates the Object Model classes based on the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class ModelManager extends AbstractManager
{
    public function build()
    {
        $this->validate();

        $totalNbFiles	 = 0;
        $dataModels	 	 = $this->getDataModels();
        $generatorConfig = $this->getGeneratorConfig();

        $this->log('Generating PHP files...');

        foreach ($dataModels as $dataModel) {
            $this->log('Datamodel: ' . $dataModel->getName());

            foreach ($dataModel->getDatabases() as $database) {
                if ($this->getGeneratorConfig()->getBuildProperty('disableIdentifierQuoting')) {
                    $database->getPlatform()->setIdentifierQuoting(false);
                }

                $this->log(' - Database: ' . $database->getName());

                foreach ($database->getTables() as $table) {
                    if (!$table->isForReferenceOnly()) {
                        $nbWrittenFiles = 0;
                        $this->log('  + Table: ' . $table->getName());

                        // -----------------------------------------------------------------------------------------
                        // Create Peer, Object, and TableMap classes
                        // -----------------------------------------------------------------------------------------

                        // these files are always created / overwrite any existing files
                        foreach (array('peer', 'object', 'tablemap', 'query') as $target) {
                            $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                            $nbWrittenFiles += $this->doBuild($builder);
                        }

                        // -----------------------------------------------------------------------------------------
                        // Create [empty] stub Peer and Object classes if they don't exist
                        // -----------------------------------------------------------------------------------------

                        // these classes are only generated if they don't already exist
                        foreach (array('peerstub', 'objectstub', 'querystub') as $target) {
                            $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                            $nbWrittenFiles += $this->doBuild($builder, $overwrite = false);
                        }

                        // -----------------------------------------------------------------------------------------
                        // Create [empty] stub child Object classes if they don't exist
                        // -----------------------------------------------------------------------------------------

                        // If table has enumerated children (uses inheritance) then create the empty child stub classes if they don't already exist.
                        if ($col = $table->getChildrenColumn()) {
                            if ($col->isEnumeratedClasses()) {
                                foreach ($col->getChildren() as $child) {
                                    foreach (array('queryinheritance') as $target) {
                                        if (!$child->getAncestor()) {
                                            continue;
                                        }
                                        $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                                        $builder->setChild($child);
                                        $nbWrittenFiles += $this->doBuild($builder, $overwrite = true);
                                    }
                                    foreach (array('objectmultiextend', 'queryinheritancestub') as $target) {
                                        $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                                        $builder->setChild($child);
                                        $nbWrittenFiles += $this->doBuild($builder, $overwrite = false);
                                    }
                                } // foreach
                            } // if col->is enumerated
                        } // if tbl->getChildrenCol

                        // -----------------------------------------------------------------------------------------
                        // Create [empty] Interface if it doesn't exist
                        // -----------------------------------------------------------------------------------------

                        // Create [empty] interface if it does not already exist
                        if ($table->getInterface()) {
                            $builder = $generatorConfig->getConfiguredBuilder($table, 'interface');
                            $nbWrittenFiles += $this->doBuild($builder, $overwrite = false);
                        }

                        // ----------------------------------
                        // Create classes added by behaviors
                        // ----------------------------------
                        if ($table->hasAdditionalBuilders()) {
                            foreach ($table->getAdditionalBuilders() as $builderClass) {
                                $builder = new $builderClass($table);
                                $builder->setGeneratorConfig($generatorConfig);
                                $nbWrittenFiles += $this->doBuild($builder, isset($builder->overwrite) ? $builder->overwrite : true);
                            }
                        }

                        $totalNbFiles += $nbWrittenFiles;
                        if ($nbWrittenFiles == 0) {
                            $this->log("\t\t(no change)");
                        }
                    } // if !$table->isForReferenceOnly()
                } // foreach table
            } // foreach database
        } // foreach dataModel

        if ($totalNbFiles) {
            $this->log(sprintf('Object model generation complete - %d files written', $totalNbFiles));
        } else {
            $this->log('Object model generation complete - All files already up to date');
        }
    } // main()

    /**
     * Uses a builder class to create the output class.
     * This method assumes that the DataModelBuilder class has been initialized with the build properties.
     *
     * @param      \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     * @param      boolean $overwrite Whether to overwrite existing files with te new ones (default is YES).
     */
    protected function doBuild(AbstractOMBuilder $builder, $overwrite = true)
    {
        $path = $builder->getClassFilePath();
        $file = new \SplFileInfo($this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $path);

        $filesystem = new Filesystem();
        $filesystem->mkdir($file->getPath());

        // skip files already created once
        if ($file->isFile() && !$overwrite) {
            $this->log("\t-> (exists) " . $builder->getClassFilePath());

            return 0;
        }

        $script = $builder->build();
        foreach ($builder->getWarnings() as $warning) {
            $this->log($warning);
        }

        // skip unchanged files
        if ($file->isFile() && $script == file_get_contents($file->getPathname())) {
            $this->log("\t-> (unchanged) " . $builder->getClassFilePath());

            return 0;
        }

        // write / overwrite new / changed files
        $action = $file->isFile() ? 'Updating' : 'Creating';
        $this->log(sprintf("\t-> %s %s (table: %s, builder: %s)", $action, $builder->getClassFilePath(), $builder->getTable()->getName(), get_class($builder)));
        file_put_contents($file->getPathname(), $script);

        return 1;
    }
}
