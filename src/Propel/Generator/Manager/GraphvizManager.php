<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Exception\BuildException;

/**
 * Manager for Graphviz representation.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class GraphvizManager extends AbstractManager
{
    public function build()
    {
        $count = 0;
        $dotSyntax = '';

        foreach ($this->getDataModels() as $dataModel) {
            $dotSyntax .= "digraph G {\n";

            foreach ($dataModel->getDatabases() as $database) {
                $this->log("db: " . $database->getName());

                // print the tables
                foreach ($database->getTables() as $tbl) {
                    $this->log("\t+ " . $tbl->getName());
                    $dotSyntax .= 'node'.$tbl->getName().' [label="{<table>'.$tbl->getName().'|<cols>';

                    foreach ($tbl->getColumns() as $col) {
                        $dotSyntax .= $col->getName() . ' (' . $col->getType()  . ')';
                        if (count($col->getForeignKeys()) > 0) {
                            $dotSyntax .= ' [FK]';
                        } elseif ($col->isPrimaryKey()) {
                            $dotSyntax .= ' [PK]';
                        }
                        $dotSyntax .= '\l';
                    }
                    $dotSyntax .= '}", shape=record];';
                    $dotSyntax .= "\n";

                    $count++;
                }

                // print the relations
                $count = 0;
                $dotSyntax .= "\n";
                foreach ($database->getTables() as $tbl) {
                    foreach ($tbl->getColumns() as $col) {
                        $fk = $col->getForeignKeys();

                        if (0 === count($fk)|| null === $fk) {
                            continue;
                        }

                        if (1 < count($fk)) {
                            throw new BuildException('Not sure what to do here...'); // WTF?
                        }

                        $fk = $fk[0];   // try first one
                        $dotSyntax .= 'node'.$tbl->getName();
                        $dotSyntax .= ':cols -> node'.$fk->getForeignTableName();
                        $dotSyntax .= ':table [label="' . $col->getName() . '=' . implode(',', $fk->getForeignColumns()) . ' "];';
                        $dotSyntax .= "\n";
                    }

                    $count++;
                }
            }

            $dotSyntax .= "}\n";

            $this->writeDot($dotSyntax, $database->getName());
        }
    }

    protected function writeDot($dotSyntax, $baseFilename)
    {
        $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $baseFilename . '.schema.dot';

        $this->log("Writing dot file to " . $file);

        file_put_contents($file, $dotSyntax);
    }
}
