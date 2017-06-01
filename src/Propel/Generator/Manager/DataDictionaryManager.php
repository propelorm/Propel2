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
    private $dictionaryColumns = ["Column Name","PHP Name", "PK/FK","Format","Length","Description"];
    
    private function getAnchorName($name)
    {
      return preg_replace('/[^A-Z|a-z]/','-',$name);
    }
    
    public function build()
    {
        $count = 0;

        foreach ($this->getDatabases() as $database) {
            $MdSyntax = "# Data Dictionary for " .  $database->getName() ."\n";
            $this->log("db: " . $database->getName());
            $MdSyntax = "<a name=\"TOC\"></a>\n# Table of Contents\n";
            
            $tableCount=1;
            
            $tables = $database->getTables();
            usort($tables, function($a, $b)
            {
              return strcmp($a->getName(), $b->getName());
            });
            
            foreach ($tables as $tbl) {
                $MdSyntax .= $tableCount.". [" . $tbl->getName() . "](#". $this->getAnchorName($tbl->getName()) .")\n";
                $tableCount ++;
            }

            // print the tables
            foreach ($tables as $tbl) {
                $this->log("\t+ " . $tbl->getName());
                
                $MdSyntax .= "<a name=\"".$this->getAnchorName($tbl->getName())."\"></a>\n## Table: " . $tbl->getName() . "\n";
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
                    if (count($col->getForeignKeys()) > 0) {
                      $columnRow[2] = '';
                      foreach ($col->getForeignKeys() as $fk)
                      {
                        $columnRow[2] .= '[FK] [' . $fk->getForeignTableName() .'](#'.$this->getAnchorName($fk->getForeignTableName()).')';
                      }
                      
                    } elseif ($col->isPrimaryKey()) {
                       $columnRow[2] =  ' [PK]';
                    }
                    else
                    {
                      $columnRow[2] = "";
                    }
                      
                    $columnRow[3] = $col->getType();
                    $columnRow[4] = $col->getSize();
                    $columnRow[5] = $col->getDescription();
                    
                    $MdSyntax .= "|".join("|",$columnRow)."|\n";
                }
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
