<?php

/*
 *  $Id: NameFactory.php,v 1.1 2004/07/08 00:22:57 hlellelid Exp $
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

include_once 'propel/engine/EngineException.php';

/**
 * A name generation factory.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version $Revision: 1.1 $
 * @package propel.engine.database.model
 */
class NameFactory {

    /**
     * The class name of the PHP name generator.
     */
    const PHP_GENERATOR = 'PhpNameGenerator';

    /**
     * The fully qualified class name of the constraint name generator.
     */
    const CONSTRAINT_GENERATOR = 'ConstraintNameGenerator';

    /**
     * The single instance of this class.
     */
    private static $instance;

    /**
     * The cache of <code>NameGenerator</code> algorithms in use for
     * name generation, keyed by fully qualified class name.
     */
    private $algorithms;

    /**
     * Creates a new instance with storage for algorithm implementations.
     */
    protected function __construct()
    {
        $this->algorithms = array();
    }

    private function instance()
    {
        if (self::$instance === null) {
            self::$instance = new NameFactory();            
        }
        return self::$instance;
    }
    
    /**
     * Factory method which retrieves an instance of the named generator.
     *
     * @param name The fully qualified class name of the name
     * generation algorithm to retrieve.
     */
    protected function getAlgorithm($name)
    {
        // synchronized (algorithms)
        $algorithm = @$this->algorithms[$name];
        if ($algorithm === null) {
            try {
                include_once 'propel/engine/database/model/' . $name . '.php';
                if (!class_exists($name)) {
                    throw new Exception("Unable to instantiate class " . $name
                        . ": Make sure it's in your include_path");
                }                
                $algorithm = new $name();
            } catch (BuildException $e) {
                print $e->getMessage() . "\n";
                print $e->getTraceAsString();
            }
            $this->algorithms[$name] = $algorithm;
        }
        return $algorithm;
        
    }

    /**
     * Given a list of <code>String</code> objects, implements an
     * algorithm which produces a name.
     *
     * @param algorithmName The fully qualified class name of the
     * {@link NameGenerator}
     * implementation to use to generate names.
     * @param array $inputs Inputs used to generate a name.
     * @return The generated name.
     * @throws EngineException
     */
    public function generateName($algorithmName, $inputs)
    {
        $algorithm = self::instance()->getAlgorithm($algorithmName);
        return $algorithm->generateName($inputs);
    }
}
