<?php
namespace Propel\Generator\Util;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\PhpNameGenerator;
use Propel\Generator\Exception\BehaviorNotFoundException;
use Propel\Generator\Config\GeneratorConfigInterface;

/**
 * Service class to find composer and installed packages
 *
 * @author Thomas Gossmann
 *        
 */
class BehaviorLocator
{

    const BEHAVIOR_PACKAGE_TYPE = 'propel-behavior';

    private $behaviors = null;

    private $composerDir = null;

    /**
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig = null;

    /**
     * Creates the composer finder
     *
     * @param GeneratorConfigInterface $config build config
     */
    public function __construct(GeneratorConfigInterface $config = null)
    {
        $this->generatorConfig = $config;
        if (null !== $config) {
            $this->composerDir = $config->getBuildProperty('builderComposerDir');
        }
    }

    /**
     * Searches the composer.lock file
     *  
     * @return SplFileInfo the found composer.lock or null if composer.lock isn't found
     */
    private function findComposerLock()
    {
        if (null !== $this->composerDir) {
            $filePath = $this->composerDir . '/composer.lock';
            
            if (file_exists($filePath)) {
                return new SplFileInfo($filePath, dirname($filePath), dirname($filePath));
            }
        }
        
        $finder = new Finder();
        $result = $finder->name('composer.lock')
            ->in($this->getSearchDirs())
            ->depth(0);
        
        if (count($result)) {
            return $result->getIterator()->current();
        }
        
        return null;
    }

    /**
     * Returns the directories to search the composer lock file in
     *
     * @return array[string]
     */
    private function getSearchDirs()
    {
        return [
            getcwd(),
            getcwd() . '/../',                  // cwd is a subfolder
            __DIR__ . '/../../../../../../',    // vendor/propel/propel
            __DIR__ . '/../../../../'           // propel development environment
        ];
    }

    /**
     * Returns the loaded behaviors and loads them if not done before
     *
     * @return array behaviors
     */
    public function getBehaviors()
    {
        if (null === $this->behaviors) {
            $lock = $this->findComposerLock();
            
            if (null === $lock) {
                $this->behaviors = [];
            } else {
                $this->behaviors = $this->loadBehaviors($lock);
            }
        }
        
        return $this->behaviors;
    }

    /**
     * Returns the class name for a given behavior name
     *
     * @param string $name The behavior name (e.g. timetampable)
     * @throws BehaviorNotFoundException when the behavior cannot be found
     * @return string the class name
     */
    public function getBehavior($name)
    {
        if (false !== strpos($name, '\\')) {
            $class = $name;
        } else {
            $class = $this->getCoreBehavior($name);
            
            if (!class_exists($class)) {
                $behaviors = $this->getBehaviors();
                if (array_key_exists($name, $behaviors)) {
                    $class = $behaviors[$name]['class'];
                }
            }
        }
        
        if (!class_exists($class)) {
            throw new BehaviorNotFoundException(sprintf('Unknown behavior "%s". You may try running `composer update` or passing the `--composer-dir` option.', $name));
        }
        
        return $class;
    }

    /**
     * Searches for the given behavior name in the Propel\Generator\Behavior namespace as
     * \Propel\Generator\Behavior\[Bname]\[Bname]Behavior
     *
     * @param string $name The behavior name (ie: timestampable)
     * @return string The behavior fully qualified class name
     */
    private function getCoreBehavior($name)
    {
        $generator = new PhpNameGenerator();
        $phpName = $generator->generateName([$name, PhpNameGenerator::CONV_METHOD_PHPNAME]);
        return sprintf('\\Propel\\Generator\\Behavior\\%s\\%sBehavior', $phpName, $phpName);
    }

    /**
     * Finds all behaviors by parsing composer.lock file
     *
     * @param SplFileInfo $composerLock            
     */
    private function loadBehaviors($composerLock)
    {
        $behaviors = [];
        
        if (null === $composerLock) {
            return $behaviors;
        }
        
        $json = json_decode($composerLock->getContents(), true);
        
        if (array_key_exists('packages', $json)) {
            foreach ($json['packages'] as $package) {
                if (array_key_exists('type', $package) && $package['type'] == self::BEHAVIOR_PACKAGE_TYPE) {
                    
                    // find propel behavior information
                    if (array_key_exists('extra', $package)) {
                        $extra = $package['extra'];
                        
                        if (array_key_exists('name', $extra) && array_key_exists('class', $extra)) {
                            $behaviors[$extra['name']] = [
                                'name' => $extra['name'],
                                'class' => $extra['class'],
                                'package' => $package['name']
                            ];
                        } else {
                            throw new BuildException(sprintf('Cannot read behavior name and class from package %s', $package['name']));
                        }
                    }
                }
            }
        }
        
        return $behaviors;
    }
}