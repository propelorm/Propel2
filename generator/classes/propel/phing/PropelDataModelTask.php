<?php

/*
 *  $Id: PropelDataModelTask.php,v 1.2 2004/07/13 15:30:54 hlellelid Exp $
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
include_once 'propel/engine/database/model/AppData.php';
include_once 'propel/engine/database/model/Database.php';
include_once 'propel/engine/database/transform/XmlToAppData.php';

/**
 * A generic class that simply loads the data model and parses a control template.
 * 
 * This class exists largely for compatibility with early Propel where this was
 * a CapsuleTask subclass.  This class also makes it easy to quickly add some custom
 * datamodel-based transformations (by allowing you to put the logic in the templates).
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.phing
 * @version $Revision: 1.2 $
 */
class PropelDataModelTask extends AbstractPropelDataModelTask {
    
    /**
     * This is the file where the generated text
     * will be placed.
     * @var string
     */
    protected $outputFile;
    
    /**
     * This is the control template that governs the output.
     * It may or may not invoke the services of worker
     * templates.
     * @var string
     */
    protected $controlTemplate;    
    
            
    /**
     * [REQUIRED] Set the output file for the
     * generation process.
     * @param string $outputFile (TODO: change this to File)
     * @return void
     */
    public function setOutputFile($outputFile) {
        $this->outputFile = $outputFile;
    }

    /**
     * Get the output file for the
     * generation process.
     * @return string
     */
    public function getOutputFile() {
        return $this->outputFile;
    }        

    /**
     * [REQUIRED] Set the control template for the
     * generating process.
     * @param string $controlTemplate
     * @return void
     */
    public function setControlTemplate ($controlTemplate) {
        $this->controlTemplate = $controlTemplate;
    }

    /**
     * Get the control template for the
     * generating process.
     * @return string
     */
    public function getControlTemplate() {
        return $this->controlTemplate;
    }
 
    protected function validate()
    {
        parent::validate();
        
        // Make sure the control template is set.
        if ($this->controlTemplate === null) {
            throw new BuildException("The control template needs to be defined!");
        }            
        // Make sure there is an output file.
        if ($this->outputFile === null) {
            throw new BuildException("The output file needs to be defined!");
        }            
    
    }
    
    /**
     * Creates Capsule context and parses control template.
     * @return void
     */
    public function main()
    {
        $this->validate();        
        $context = $this->createContext();        
        
        $context->put("dataModels", $this->getDataModels());
        
        $path = $this->outputDirectory . DIRECTORY_SEPARATOR . $this->outputFile;
        $this->log("Generating to file " . $path);
        
        try {
            $this->log("Parsing control template: " . $this->controlTemplate);
            $context->parse($this->controlTemplate, $path);
        } catch (Exception $ioe) {
            throw new BuildException("Cannot write parsed template: ". $ioe->getMessage());
        }
    }
}
