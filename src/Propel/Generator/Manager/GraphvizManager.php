<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Model\Column;

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

        foreach ($this->getDatabases() as $database) {
            $dotSyntax = "digraph G {\n";

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
                foreach ($tbl->getForeignKeys() as $fk) {
                    $dotSyntax .= 'node'.$tbl->getName();
                    $dotSyntax .= ':cols -> node'.$fk->getForeignTableName();
                    $label = [];
                    foreach ($fk->getMapping() as $map) {
                        list ($localColumn, $foreignValueOrColumn) = $map;
                        $labelString = $localColumn->getName().'=';
                        if ($foreignValueOrColumn instanceof Column) {
                            $labelString .= $foreignValueOrColumn->getName();
                        } else {
                            $labelString .= var_export($foreignValueOrColumn, true);
                        }

                        $label[] = $labelString;
                    }
                    $dotSyntax .= ':table [label="' . implode('\l', $label) . ' ", color=gray];';
                    $dotSyntax .= "\n";
                }

                $count++;
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
