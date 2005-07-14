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
 
require_once 'propel/phing/AbstractPropelDataModelTask.php';
include_once 'propel/engine/builder/om/ClassTools.php';
require_once 'propel/engine/builder/DataModelBuilder.php';

/**
 * This is a temporary task that creates the OM classes based on the XML schema file using NEW builder framework.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.phing
 */
class PropelNewOMTask extends AbstractPropelDataModelTask {

    /**
     * The platform (php4, php5, etc.) for which the om is being built.
     * @var string
     */
    private $targetPlatform;
    
    /**
     * Sets the platform (php4, php5, etc.) for which the om is being built.
     * @param string $v
     */
    public function setTargetPlatform($v) {
        $this->targetPlatform = $v;
    }
    
    /**
     * Gets the platform (php4, php5, etc.) for which the om is being built.
     * @return string
     */
    public function getTargetPlatform() {
        return $this->targetPlatform;
    }
    
    public function main() {
        
        // check to make sure task received all correct params
        $this->validate();        
        
        $basepath = $this->getOutputDirectory();        
        
        // Get new Capsule context
        $generator = $this->createContext();
        $generator->put("basepath", $basepath); // make available to other templates
        
        $targetPlatform = $this->getTargetPlatform(); // convenience for embedding in strings below
                
        // we need some values that were loaded into the template context
        $basePrefix = $generator->get('basePrefix');
        $project = $generator->get('project');
        
        foreach ($this->getDataModels() as $dataModel) {
            $this->log("Processing Datamodel : " . $dataModel->getName());
            
            foreach ($dataModel->getDatabases() as $database) {
                
                $this->log("  - processing database : " . $database->getName());
                $generator->put("platform", $database->getPlatform());
                
                            
                foreach ($database->getTables() as $table) {                    
                
                    if (!$table->isForReferenceOnly()) {
                    
						DataModelBuilder::setBuildProperties($this->getPropelProperties());
						
                        $this->log("\t+ " . $table->getName());
						
						$targets = array('peer', 'object', 'peerstub', 'objectstub', 'mapbuilder');
						
						foreach($targets as $target) {
						
							$builder = DataModelBuilder::builderFactory($target);
							
							// make sure path (from package) exists:
							$path = strtr($builder->getPackage(), '.', '/');
                            $f = new PhingFile($this->getOutputDirectory(), $path);
                            if (!$f->exists()) {
                                if (!$f->mkdirs()) {
                                    throw new Exception("Error creating directories: ". $f->getPath());
                                }
                            }
							
							// Create the Base Peer class
	                        $this->log("\t\t-> " . $builder->getClassname());
	                        $path = ClassTools::getFilePath($builder->getPackage(), $builder->getClassname());  
							
							$script = $builder->build();
							
							$_f = new PhingFile($basepath, $path);
							file_put_contents($_f->getAbsolutePath(), $script);
							
						}
                        
						
						
						$peerBuilder = DataModelBuilder::getNewPeerBuilder($table);
						
                        // Create the Base Peer class
                        $this->log("\t\t-> " . $basePrefix . $peerBuilder->getPeerClassname());						
                        $path = ClassTools::getFilePath($pkbase, $basePrefix . $peerBuilder->getPeerClassname());  
																		
						$script = $peerBuilder->build();
						
						$_f = new PhingFile($basepath, $path);
						file_put_contents($_f->getAbsolutePath(), $script);
						
						// Build Object classes
						// --------------------
						
						
						
						$this->log("\t\t-> " . $basePrefix . $table->getPhpName());
						
						$objectBuilder = DataModelBuilder::getNewObjectBuilder($table);		
						$path = ClassTools::getFilePath($pkbase, $basePrefix . $table->getPhpName()); 	
						
						$script = $objectBuilder->build();
						
						$_f = new PhingFile($basepath, $path);
						file_put_contents($_f->getAbsolutePath(), $script);
						
                        #$generator->parse("om/$targetPlatform/Peer.tpl", $path);
						
						/*
                        
                        // Create the Base object class
                        $this->log("\t\t-> " . $basePrefix . $table->getPhpName());
                        $path = ClassTools::getFilePath($pkbase, $basePrefix . $table->getPhpName());
                        #$generator->parse("om/$targetPlatform/Object.tpl", $path);

                        if ($table->isTree()) {
                            // Create the Base NodePeer class
                            $this->log("\t\t-> " . $basePrefix . $table->getPhpName() . "NodePeer");
                            $path = ClassTools::getFilePath($pkbase, $basePrefix . $table->getPhpName() . "NodePeer");
                            #$generator->parse("om/$targetPlatform/NodePeer.tpl", $path);
                            
                            // Create the Base Node class if the table is a tree
                            $this->log("\t\t-> " . $basePrefix . $table->getPhpName() . "Node");
                            $path = ClassTools::getFilePath($pkbase, $basePrefix . $table->getPhpName() . "Node");
                            #$generator->parse("om/$targetPlatform/Node.tpl", $path);
                        }
                        
                        // Create MapBuilder class if this table is not an alias
                        if (!$table->isAlias()) {
                            $this->log("\t\t-> " . $table->getPhpName() . "MapBuilder");
                            $path = ClassTools::getFilePath($pkmap, $table->getPhpName() . "MapBuilder");
                            #$generator->parse("om/$targetPlatform/MapBuilder.tpl", $path);
                        } // if !$table->isAlias()

                        // Create [empty] stub Peer class if it does not already exist        
                        $path = ClassTools::getFilePath($package, $table->getPhpName() . "Peer");
                        $_f = new PhingFile($basepath, $path);
                        if (!$_f->exists()) {
                            $this->log("\t\t-> " . $table->getPhpName() . "Peer");
                            #$generator->parse("om/$targetPlatform/ExtensionPeer.tpl", $path);
                        } else {
                            $this->log("\t\t-> (exists) " . $table->getPhpName() . "Peer");
                        }
                
                        // Create [empty] stub object class if it does not already exist
                        $path = ClassTools::getFilePath($package, $table->getPhpName());
                        $_f = new PhingFile($basepath, $path);
                        if (!$_f->exists()) {
                            $this->log("\t\t-> " . $table->getPhpName());
                            #$generator->parse("om/$targetPlatform/ExtensionObject.tpl", $path);
                        } else {
                            $this->log("\t\t-> (exists) " . $table->getPhpName());
                        }

                        if ($table->isTree()) {                        
                            // Create [empty] stub Node Peer class if it does not already exist        
                            $path = ClassTools::getFilePath($package, $table->getPhpName() . "NodePeer");
                            $_f = new PhingFile($basepath, $path);
                            if (!$_f->exists()) {
                                $this->log("\t\t-> " . $table->getPhpName() . "NodePeer");
                                #$generator->parse("om/$targetPlatform/ExtensionNodePeer.tpl", $path);
                            } else {
                                $this->log("\t\t-> (exists) " . $table->getPhpName() . "NodePeer");
                            }
                            
                            // Create [empty] stub Node class if it does not already exist        
                            $path = ClassTools::getFilePath($package, $table->getPhpName() . "Node");
                            $_f = new PhingFile($basepath, $path);
                            if (!$_f->exists()) {
                                $this->log("\t\t-> " . $table->getPhpName() . "Node");
                                #$generator->parse("om/$targetPlatform/ExtensionNode.tpl", $path);
                            } else {
                                $this->log("\t\t-> (exists) " . $table->getPhpName() . "Node");
                            }
                        }

                        // Create [empty] interface if it does not already exist
                        if ($table->getInterface()) {            
                            $path = ClassTools::getFilePath($package, $table->getInterface());
                            $_f = new PhingFile($basepath, $path);
                            if (!$_f->exists()) {
                                $this->log("\t\t-> " . $table->getInterface());
                                #$generator->parse("om/$targetPlatform/Interface.tpl", $path);
                            } else {
                                $this->log("\t\t-> (exists) " . $table->getInterface());
                            }
                        }
                        
                        // If table has enumerated children (uses inheritance) then create the empty child stub classes 
                        // if they don't already exist.
                        if ($table->getChildrenColumn()) {
                            $col = $table->getChildrenColumn();
                            if ($col->isEnumeratedClasses()) {
                                foreach ($col->getChildren() as $child) {
                                    #$generator->put("child", $child);                    
                                    $path = ClassTools::getFilePath($package, $child->getClassName());
                                    $_f = new PhingFile($basepath, $path); 
                                    if (!$_f->exists()) {
                                        $this->log("\t\t-> " . $child->getClassName());
                                        #$generator->parse("om/$targetPlatform/MultiExtendObject.tpl", $path);
                                    } else {
                                        $this->log("\t\t-> (exists) " . $child->getClassName());
                                    }
                                } // foreach
                            } // if col->is enumerated
                        } // if tbl->getChildrenCol
                    	*/
						
                    } // if !$table->isForReferenceOnly()                    					
					
                } // foreach table    
        
            } // foreach database
        
        } // foreach dataModel

    
    } // main()
}