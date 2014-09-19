<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Symfony\Component\Filesystem\Filesystem;
use Propel\Generator\Builder\Om\AbstractOMBuilder;

/**
 * This manager creates the Object Model classes based on the XML schema file.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ModelManager extends AbstractManager
{
    /**
     * A Filesystem object.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Sets the filesystem object.
     *
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function build()
    {
        $this->validate();

        $totalNbFiles    = 0;
        $dataModels      = $this->getDataModels();
        $generatorConfig = $this->getGeneratorConfig();

        $this->log('Generating PHP files...');

        foreach ($dataModels as $dataModel) {
            $this->log('Datamodel: ' . $dataModel->getName());

            foreach ($dataModel->getDatabases() as $database) {
                $this->log(' - Database: ' . $database->getName());

                foreach ($database->getTables() as $table) {
                    if (!$table->isForReferenceOnly()) {
                        $nbWrittenFiles = 0;
                        $this->log('  + Table: ' . $table->getName());

                        // -----------------------------------------------------------------------------------------
                        // Create Object, and TableMap classes
                        // -----------------------------------------------------------------------------------------

                        // these files are always created / overwrite any existing files
                        foreach (array('object', 'tablemap', 'query') as $target) {
                            $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                            $nbWrittenFiles += $this->doBuild($builder);
                        }

                        // -----------------------------------------------------------------------------------------
                        // Create [empty] stub Object classes if they don't exist
                        // -----------------------------------------------------------------------------------------

                        // these classes are only generated if they don't already exist
                        $overwrite = false;
                        foreach (array('objectstub', 'querystub') as $target) {
                            $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                            $nbWrittenFiles += $this->doBuild($builder, $overwrite);
                        }

                        // -----------------------------------------------------------------------------------------
                        // Create [empty] stub child Object classes if they don't exist
                        // -----------------------------------------------------------------------------------------

                        // If table has enumerated children (uses inheritance) then create the empty child stub classes if they don't already exist.
                        if ($col = $table->getChildrenColumn()) {
                            if ($col->isEnumeratedClasses()) {
                                foreach ($col->getChildren() as $child) {
                                    $overwrite = true;
                                    foreach (array('queryinheritance') as $target) {
                                        if (!$child->getAncestor() && $child->getClassName() == $table->getPhpName()) {
                                            continue;
                                        }
                                        $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                                        $builder->setChild($child);
                                        $nbWrittenFiles += $this->doBuild($builder, $overwrite);
                                    }
                                    $overwrite = false;
                                    foreach (array('objectmultiextend', 'queryinheritancestub') as $target) {
                                        $builder = $generatorConfig->getConfiguredBuilder($table, $target);
                                        $builder->setChild($child);
                                        $nbWrittenFiles += $this->doBuild($builder, $overwrite);
                                    }
                                }
                            }
                        }

                        // -----------------------------------------------------------------------------------------
                        // Create [empty] Interface if it doesn't exist
                        // -----------------------------------------------------------------------------------------

                        // Create [empty] interface if it does not already exist
                        $overwrite = false;
                        if ($table->getInterface()) {
                            $builder = $generatorConfig->getConfiguredBuilder($table, 'interface');
                            $nbWrittenFiles += $this->doBuild($builder, $overwrite);
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
                        if (0 === $nbWrittenFiles) {
                            $this->log("\t\t(no change)");
                        }
                    }
                }
            }
        }

        if ($totalNbFiles) {
            $this->log(sprintf('Object model generation complete - %d files written', $totalNbFiles));
        } else {
            $this->log('Object model generation complete - All files already up to date');
        }
    }

    /**
     * Uses a builder class to create the output class.
     * This method assumes that the DataModelBuilder class has been initialized
     * with the build properties.
     *
     * @param  AbstractOMBuilder $builder
     * @param  boolean           $overwrite
     * @return int
     */
    protected function doBuild(AbstractOMBuilder $builder, $overwrite = true)
    {
        $path = $builder->getClassFilePath();
        $file = new \SplFileInfo($this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $path);

        $this->filesystem->mkdir($file->getPath());

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
