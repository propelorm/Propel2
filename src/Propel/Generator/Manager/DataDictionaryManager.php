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
class DataDictionaryManager extends AbstractManager {
    const DICTIONARYCOLUMNS = ["Column Name","PHP Name", "PK/FK","Format","Length","Description"];
    
    private function getAnchorName($name) {
      return preg_replace('/[^A-Z|a-z]/','-',$name);
    }
    
    public function build() {
        $count = 0;

        foreach ($this->getDatabases() as $database) {
            $markDownSyntax = "# Data Dictionary for " .  $database->getName() ."\n";
            $this->log("db: " . $database->getName());
            $markDownSyntax = "<a name=\"TOC\"></a>\n# Table of Contents\n";
            
            $tableCount=1;
            
            $tables = $database->getTables();
            usort($tables, function($a, $b) {
              return strcmp($a->getName(), $b->getName());
            });
            
            foreach ($tables as $tbl) {
                $markDownSyntax .= $tableCount.". [" . $tbl->getName() . "](#". $this->getAnchorName($tbl->getName()) .")\n";
                $tableCount ++;
            }

            // print the tables
            foreach ($tables as $tbl) {
                $this->log("\t+ " . $tbl->getName());
                
                $markDownSyntax .= "<a name=\"".$this->getAnchorName($tbl->getName())."\"></a>\n## Table: " . $tbl->getName() . "\n";
                $markDownSyntax .= "[Table of Contents](#TOC)\n\n";
                
                if ($tbl->getDescription()) {
                  $markDownSyntax .= "### Description:\n";
                  $markDownSyntax .= $tbl->getDescription()."\n";
                }
                
                $markDownSyntax .= "### Columns:\n";
                $markDownSyntax .= "|".join("|",self::DICTIONARYCOLUMNS)."|\n";
                $markDownSyntax .= "|".join("|",array_fill(0,count(self::DICTIONARYCOLUMNS),"---"))."|\n";

                foreach ($tbl->getColumns() as $col) {
                    $columnRow = [];
                    $columnRow[0] = $col->getName();
                    $columnRow[1] = $col->getPhpName();
                    if (count($col->getForeignKeys()) > 0) {
                      $columnRow[2] = '';
                      foreach ($col->getForeignKeys() as $fk) {
                        $columnRow[2] .= '[FK] [' . $fk->getForeignTableName() .'](#'.$this->getAnchorName($fk->getForeignTableName()).')';
                      }
                      
                    } elseif ($col->isPrimaryKey()) {
                       $columnRow[2] =  ' [PK]';
                    }
                    else {
                      $columnRow[2] = "";
                    }
                      
                    $columnRow[3] = $col->getType();
                    $columnRow[4] = $col->getSize();
                    $columnRow[5] = $col->getDescription();
                    
                    $markDownSyntax .= "|".join("|",$columnRow)."|\n";
                }
            }

            $this->writeMd($markDownSyntax, $database->getName());
        }
    }

    protected function writeMd($markDownSyntax, $baseFilename) {
        $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $baseFilename . '.schema.md';

        $this->log("Writing md file to " . $file);

        file_put_contents($file, $markDownSyntax);
    }
}
