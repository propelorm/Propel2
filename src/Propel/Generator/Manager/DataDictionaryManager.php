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
 * Manager for Markdown Data Dictionary
 *
 * @author Charles Crossan <crossan007@gmail.com>
 */
class DataDictionaryManager extends AbstractManager
{
    private $dictionaryColumns = ["Column Name","PHP Name", "Primary Key","Format","Length","Description"];
  
    public function build()
    {
        $count = 0;

        foreach ($this->getDatabases() as $database) {
            $MdSyntax = "# Data Dictionary for " .  $database->getName() ."\n";
            $this->log("db: " . $database->getName());
            $MdSyntax = "# Table of Contents <a name=\"TOC\"></a>\n";
            
            $tableCount=1;
            
            $tables = $database->getTables();
            usort($tables, function($a, $b)
            {
              return strcmp($a->getName(), $b->getName());
            });
            
            foreach ($tables as $tbl) {
                $MdSyntax .= $tableCount.". [" . $tbl->getName() . "](#". preg_replace('/[^A-Z|a-z]/','-',$tbl->getName()) .")\n";
                $tableCount ++;
            }

            // print the tables
            foreach ($tables as $tbl) {
                $this->log("\t+ " . $tbl->getName());
                
                $MdSyntax .= "## Table: " . $tbl->getName() . "<a name=\"".preg_replace('/[^A-Z|a-z]/','-',$tbl->getName())."\"></a>\n";
                $MdSyntax .= "[Table of Contents](#TOC)\n\n";
                
                if ($tbl->getDescription())
                {
                  $MdSyntax .= "### Description:\n";
                  $MdSyntax .= $tbl->getDescription()."\n";
                }
                
               $MdSyntax .= "### Columns:\n";
                $MdSyntax .= "|".join("|",$this->dictionaryColumns)."|\n";
                $MdSyntax .= "|".join("|",array_fill(0,count($this->dictionaryColumns),"---"))."|\n";

                foreach ($tbl->getColumns() as $col) {
                    $columnRow = [];
                    $columnRow[0] = $col->getName();
                    $columnRow[1] = $col->getPhpName();
                    $columnRow[2] = ($col->isPrimaryKey() ? "YES":"NO");
                    $columnRow[3] = $col->getType();
                    $columnRow[4] = $col->getSize();
                    $columnRow[5] = $col->getDescription();
                    
                    $MdSyntax .= "|".join("|",$columnRow)."|\n";
                }
                
                /*
                    if (count($col->getForeignKeys()) > 0) {
                        $dotSyntax .= ' [FK]';
                    } elseif ($col->isPrimaryKey()) {
                        $dotSyntax .= ' [PK]';
                    }
                    $dotSyntax .= '\l';
                }
                $dotSyntax .= '}", shape=record];';
                $dotSyntax .= "\n";

     
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

            $dotSyntax .= "}\n";*/
                
            }

            $this->writeMd($MdSyntax, $database->getName());
        }
    }

    protected function writeMd($MdSyntax, $baseFilename)
    {
        $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $baseFilename . '.schema.md';

        $this->log("Writing md file to " . $file);

        file_put_contents($file, $MdSyntax);
    }
}
