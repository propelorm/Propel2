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
            $this->composerDir = $config->get()['paths']['composerDir'];
        }
    }

    /**
     * Searches a composer file
     *
     * @return SplFileInfo the found composer file or null if composer file isn't found
     */
    private function findComposerFile($fileName)
    {
        if (null !== $this->composerDir) {
            $filePath = $this->composerDir . '/' . $fileName;

            if (file_exists($filePath)) {
                return new SplFileInfo($filePath, dirname($filePath), dirname($filePath));
            }
        }

        $finder = new Finder();
        $result = $finder->name($fileName)
            ->in($this->getSearchDirs())
            ->depth(0);

        if (count($result)) {
            return $result->getIterator()->current();
        }

        return null;
    }

    /**
     * Searches the composer.lock file
     *
     * @return SplFileInfo the found composer.lock or null if composer.lock isn't found
     */
    private function findComposerLock()
    {
        return $this->findComposerFile('composer.lock');
    }

    /**
     * Searches the composer.json file
     *
     * @return SplFileInfo the found composer.json or null if composer.json isn't found
     */
    private function findComposerJson()
    {
        return $this->findComposerFile('composer.json');
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
            getcwd() . '/../',                   // cwd is a subfolder
            __DIR__ . '/../../../../../../../',  // vendor/propel/propel
            __DIR__ . '/../../../../'            // propel development environment
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
            // find behaviors in composer.lock file
            $lock = $this->findComposerLock();

            if (null === $lock) {
                $this->behaviors = [];
            } else {
                $this->behaviors = $this->loadBehaviors($lock);
            }

            // find behavior in composer.json (useful when developing a behavior)
            $json = $this->findComposerJson();

            if (null !== $json) {
                $behavior = $this->loadBehavior(json_decode($json->getContents(), true));

                if (null !== $behavior) {
                    $this->behaviors[$behavior['name']] = $behavior;
                }
            }
        }

        return $this->behaviors;
    }

    /**
     * Returns the class name for a given behavior name
     *
     * @param  string                    $name The behavior name (e.g. timetampable)
     * @throws BehaviorNotFoundException when the behavior cannot be found
     * @return string                    the class name
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
     * @param  string $name The behavior name (ie: timestampable)
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

        if (isset($json['packages'])) {
            foreach ($json['packages'] as $package) {
                $behavior = $this->loadBehavior($package);

                if (null !== $behavior) {
                    $behaviors[$behavior['name']] = $behavior;
                }
            }
        }

        return $behaviors;
    }

    /**
     * Reads the propel behavior data from a given composer package
     *
     * @param  array          $package
     * @throws BuildException
     * @return array          behavior data
     */
    private function loadBehavior($package)
    {
        if (isset($package['type']) && $package['type'] == self::BEHAVIOR_PACKAGE_TYPE) {

            // find propel behavior information
            if (isset($package['extra'])) {
                $extra = $package['extra'];

                if (isset($extra['name']) && isset($extra['class'])) {
                    return [
                        'name' => $extra['name'],
                        'class' => $extra['class'],
                        'package' => $package['name']
                    ];
                } else {
                    throw new BuildException(sprintf('Cannot read behavior name and class from package %s', $package['name']));
                }
            }
        }

        return null;
    }
}
