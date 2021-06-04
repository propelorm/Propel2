<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Table;
use SplFileInfo;

/**
 * Generates a database loader file, which is used to register all table maps with the DatabaseMap.
 */
class TableMapLoaderScriptBuilder
{
    public const FILENAME = 'loadDatabase.php';

    /**
     * @var \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     */
    protected $generatorConfig;

    /**
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     */
    public function __construct(GeneratorConfigInterface $generatorConfig)
    {
        $this->generatorConfig = $generatorConfig;
    }

    /**
     * @param array $schemas
     *
     * @return string
     */
    public function build(array $schemas): string
    {
        $vars = $this->buildVars($schemas);

        return $this->renderTemplate($vars);
    }

    /**
     * @param \Propel\Generator\Model\Schema[] $schemas
     *
     * @return array
     */
    protected function buildVars(array $schemas): array
    {
        $databaseNameToTableMapNames = [];

        foreach ($schemas as $schema) {
            foreach ($schema->getDatabases(false) as $database) {
                $databaseName = $database->getName();
                $tableMapNames = array_map([$this, 'getFullyQualifiedTableMapClassName'], $database->getTables());
                if (array_key_exists($databaseName, $databaseNameToTableMapNames)) {
                    $existing = $databaseNameToTableMapNames[$databaseName];
                    $tableMapNames = array_merge($existing, $tableMapNames);
                }
                sort($tableMapNames);
                $databaseNameToTableMapNames[$databaseName] = $tableMapNames;
            }
        }

        return [
            'databaseNameToTableMapNames' => $databaseNameToTableMapNames,
        ];
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    protected function getFullyQualifiedTableMapClassName(Table $table): string
    {
        $builder = new TableMapBuilder($table);
        $builder->setGeneratorConfig($this->generatorConfig);

        return $builder->getFullyQualifiedClassName();
    }

    /**
     * @param array $vars
     *
     * @return string
     */
    protected function renderTemplate(array $vars): string
    {
        $filePath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'templates', 'tableMapLoaderScript.php']);
        $template = new PropelTemplate();
        $template->setTemplateFile($filePath);

        return $template->render($vars);
    }

    /**
     * Return file info object pointing to the designated script location. The file does not necessarily exist.
     *
     * @return \SplFileInfo
     */
    public function getFile(): SplFileInfo
    {
        $configDir = $this->generatorConfig->getConfigProperty('paths.loaderScriptDir') ?? $this->generatorConfig->getConfigProperty('paths.phpConfDir');

        return new SplFileInfo($configDir . DIRECTORY_SEPARATOR . self::FILENAME);
    }
}
