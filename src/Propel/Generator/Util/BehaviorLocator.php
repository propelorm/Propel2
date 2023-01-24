<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Util;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BehaviorNotFoundException;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\PhpNameGenerator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Service class to find composer and installed packages
 *
 * @author Thomas Gossmann
 */
class BehaviorLocator
{
    /**
     * @var string
     */
    public const BEHAVIOR_PACKAGE_TYPE = 'propel-behavior';

    /**
     * @var array|null
     */
    private $behaviors;

    /**
     * @var string|null
     */
    private $composerDir;

    /**
     * Creates the composer finder
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface|null $config build config
     */
    public function __construct(?GeneratorConfigInterface $config = null)
    {
        if ($config !== null) {
            $this->composerDir = $config->get()['paths']['composerDir'];
        }
    }

    /**
     * Searches a composer file
     *
     * @param string $fileName
     *
     * @return \Symfony\Component\Finder\SplFileInfo|null The found composer file or null if composer file isn't found
     */
    private function findComposerFile(string $fileName): ?SplFileInfo
    {
        if ($this->composerDir !== null) {
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
     * @return \Symfony\Component\Finder\SplFileInfo|null The found composer.lock or null if composer.lock isn't found
     */
    private function findComposerLock(): ?SplFileInfo
    {
        return $this->findComposerFile('composer.lock');
    }

    /**
     * Searches the composer.json file
     *
     * @return \Symfony\Component\Finder\SplFileInfo|null the found composer.json or null if composer.json isn't found
     */
    private function findComposerJson(): ?SplFileInfo
    {
        return $this->findComposerFile('composer.json');
    }

    /**
     * Returns the directories to search the composer lock file in
     *
     * @return list<string>
     */
    private function getSearchDirs(): array
    {
        $workingDirectory = (string)getcwd();

        return [
            $workingDirectory,
            $workingDirectory . '/../', // cwd is a subfolder
            __DIR__ . '/../../../../../../../', // vendor/propel/propel
            __DIR__ . '/../../../../', // propel development environment
        ];
    }

    /**
     * Returns the loaded behaviors and loads them if not done before
     *
     * @return array behaviors
     */
    public function getBehaviors(): array
    {
        if ($this->behaviors === null) {
            // find behaviors in composer.lock file
            $lock = $this->findComposerLock();

            if ($lock === null) {
                $this->behaviors = [];
            } else {
                $this->behaviors = $this->loadBehaviorsFromLockFile($lock);
            }

            // find behavior in composer.json (useful when developing a behavior)
            $json = $this->findComposerJson();

            if ($json !== null) {
                $behavior = $this->loadBehavior(json_decode($json->getContents(), true));

                if ($behavior !== null) {
                    $this->behaviors[$behavior['name']] = $behavior;
                }
            }
        }

        return $this->behaviors;
    }

    /**
     * Returns the class name for a given behavior name
     *
     * @param string $name The behavior name (e.g. timetampable)
     *
     * @throws \Propel\Generator\Exception\BehaviorNotFoundException when the behavior cannot be found
     *
     * @return string the class name
     */
    public function getBehavior(string $name): string
    {
        if (strpos($name, '\\') !== false) {
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
     *
     * @return string The behavior fully qualified class name
     */
    private function getCoreBehavior(string $name): string
    {
        $generator = new PhpNameGenerator();
        $phpName = $generator->generateName([$name, PhpNameGenerator::CONV_METHOD_PHPNAME]);

        return sprintf('\\Propel\\Generator\\Behavior\\%s\\%sBehavior', $phpName, $phpName);
    }

    /**
     * Finds all behaviors in composer.lock file
     *
     * @param \Symfony\Component\Finder\SplFileInfo $composerLock
     *
     * @return array
     */
    private function loadBehaviorsFromLockFile(SplFileInfo $composerLock): array
    {
        $behaviors = [];

        $json = json_decode($composerLock->getContents(), true);

        foreach (['packages', 'packages-dev'] as $packageSectionName) {
            if (!isset($json[$packageSectionName])) {
                continue;
            }
            foreach ($json[$packageSectionName] as $package) {
                $behavior = $this->loadBehavior($package);

                if ($behavior !== null) {
                    $behaviors[$behavior['name']] = $behavior;
                }
            }
        }

        return $behaviors;
    }

    /**
     * Reads the propel behavior data from a given composer package
     *
     * @param array $package
     *
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return array|null Behavior data
     */
    private function loadBehavior(array $package): ?array
    {
        if (isset($package['type']) && $package['type'] == self::BEHAVIOR_PACKAGE_TYPE) {
            // find propel behavior information
            if (isset($package['extra'])) {
                $extra = $package['extra'];

                if (isset($extra['name']) && isset($extra['class'])) {
                    return [
                        'name' => $extra['name'],
                        'class' => $extra['class'],
                        'package' => $package['name'],
                    ];
                }

                throw new BuildException(sprintf('Cannot read behavior name and class from package %s', $package['name']));
            }
        }

        return null;
    }
}
