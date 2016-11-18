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

        foreach ($this->getDatabases() as $database) {
            $dotSyntax = "digraph G {\n";

            $this->log("db: " . $database->getName());

            // print the tables
            foreach ($database->getEntities() as $entity) {
                $this->log("\t+ " . $entity->getName());
                $dotSyntax .= 'node'.$entity->getName().' [label="{<table>'.$entity->getName().'|<cols>';

                foreach ($entity->getFields() as $field) {
                    $dotSyntax .= $field->getName() . ' (' . $field->getType()  . ')';
                    if (count($field->getRelations()) > 0) {
                        $dotSyntax .= ' [FK]';
                    } elseif ($field->isPrimaryKey()) {
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
            foreach ($database->getEntities() as $entity) {
                foreach ($entity->getRelations() as $relation) {
                    $dotSyntax .= 'node'.$entity->getName();
                    $dotSyntax .= ':cols -> node'.$relation->getForeignEntityName();
                    $label = [];
                    foreach ($relation->getFieldObjectsMapping() as $map) {
                        $label[] = $map['local']->getName().'='.$map['foreign']->getName();
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
