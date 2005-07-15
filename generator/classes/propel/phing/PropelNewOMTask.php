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
						
							$builder = DataModelBuilder::builderFactory($table, $target);
							
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
	                        $path = $builder->getClassFilePath();
							
							$script = $builder->build();
							
							$_f = new PhingFile($basepath, $path);
							file_put_contents($_f->getAbsolutePath(), $script);
							
						}
                        
						
						
						
                    } // if !$table->isForReferenceOnly()                    					
					
                } // foreach table    
        
            } // foreach database
        
        } // foreach dataModel

    
    } // main()
}