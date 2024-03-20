<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;

/**
 * Manager for Markdown Data Dictionary
 *
 * @author Charles Crossan <crossan007@gmail.com>
 */
class DataDictionaryExportManager extends AbstractManager
{
    /**
     * @var array
     */
    public const COLUMN_TABLE_HEADERS = ['Column Name', 'PHP Name', 'Type', 'Length', 'PK', 'NN', 'UQ', 'AI', 'FK', 'Default', 'Description'];

    /**
     * @return void
     */
    public function build(): void
    {
        foreach ($this->getDatabases() as $database) {
            $markdownContent = $this->buildMarkdownForDatabase($database);
            $this->writeFile($markdownContent, $database->getName());
        }
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return string
     */
    protected function buildMarkdownForDatabase(Database $database): string
    {
        $databaseName = $database->getName();
        $this->log("building markdown for db: $databaseName");

        $tables = $this->getOrderedTables($database);

        $tocMd = $this->buildToc($tables);
        $tablesMd = array_map([$this, 'buildMarkdownForTable'], $tables);
        $tableSectionMd = implode(PHP_EOL, $tablesMd);

        return <<< EOT
# Data Dictionary for $databaseName

<a name="TOC"></a>
# Table of Contents
$tocMd

$tableSectionMd
EOT;
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return array<\Propel\Generator\Model\Table>
     */
    protected function getOrderedTables(Database $database): array
    {
        $tables = $database->getTables();
        usort($tables, function ($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

            return $tables;
    }

    /**
     * @param array $tables
     *
     * @return string
     */
    protected function buildToc(array $tables): string
    {
        $tableCount = 1;

        $tableItems = [];
        foreach ($tables as $table) {
            $tableName = $table->getName();
            $anchorName = $this->buildAnchorName($tableName);
            $tableItems[] = "$tableCount. [$tableName](#$anchorName)";
            $tableCount++;
        }

        return implode(PHP_EOL, $tableItems);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    protected function buildMarkdownForTable(Table $table): string
    {
        $this->log('   adding table ' . $table->getName());

        $tableName = $table->getName();
        $anchorName = $this->buildAnchorName($tableName);

        $flags = $this->getTableFlags($table);

        $descriptionMd = '';
        $description = $table->getDescription();
        if ($description) {
            $descriptionMd = "\n> $description\n";
        }

        $columnsHeaderMd = implode('|', self::COLUMN_TABLE_HEADERS);
        $numberOfDataColumns = count(self::COLUMN_TABLE_HEADERS);
        $headerSepMd = '|' . str_repeat('---|', $numberOfDataColumns);

        $columnRows = array_map([$this, 'buildMarkdownForColumn'], $table->getColumns());
        $columnRowsMd = implode(PHP_EOL, $columnRows);

        return <<< EOT

---

<a name="$anchorName"></a>
## Table **$tableName** $flags
$descriptionMd
### Columns:
|$columnsHeaderMd|
$headerSepMd
$columnRowsMd

[to table of contents](#TOC)
EOT;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    protected function getTableFlags(Table $table): string
    {
        $isAbstract = $table->isAbstract();
        $skipSql = $table->isSkipSql();

        if (!$isAbstract && !$skipSql) {
            return '';
        }
        $flags = [];

        if ($isAbstract) {
            $flags[] = 'abstract';
        }
        if ($skipSql) {
            $flags[] = 'skip SQL';
        }

        return '*(' . implode(', ', $flags) . ')*';
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    protected function buildMarkdownForColumn(Column $column): string
    {
        $defaultValueString = $column->getDefaultValueString();
        $defaultValue = ($defaultValueString === 'null') ? '' : $defaultValueString;

        $columnRow = [
            $column->getName(),
            $column->getPhpName(),
            $column->getType(),
            $column->getSize(),
            $this->getFlagSymbol($column->isPrimaryKey()),
            $this->getFlagSymbol($column->isNotNull()),
            $this->getFlagSymbol($column->isUnique()),
            $this->getFlagSymbol($column->isAutoIncrement()),
            $this->buildLinksToForeignTables($column),
            $defaultValue,
            $column->getDescription(),
        ];

        return '|' . implode('|', $columnRow) . '|';
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    protected function buildLinksToForeignTables(Column $column): string
    {
        $foreignKeys = $column->getForeignKeys();
        if (!$foreignKeys) {
            return '';
        }

        $links = [];
        foreach ($foreignKeys as $fk) {
            $foreignTableName = $fk->getForeignTableName();
            $links[] = $this->buildLink($foreignTableName);
        }

        return implode(' ', $links);
    }

    /**
     * @param string $markdownContent
     * @param string $baseFilename
     *
     * @return void
     */
    protected function writeFile(string $markdownContent, string $baseFilename): void
    {
        $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $baseFilename . '.schema.md';

        $this->log('Writing md file to ' . $file);

        file_put_contents($file, $markdownContent);
    }

    /**
     * @param bool $val
     *
     * @return string
     */
    protected function getFlagSymbol(bool $val): string
    {
        return ($val) ? '*' : '';
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function buildAnchorName(string $name): string
    {
        return preg_replace('/\W/', '-', $name);
    }

    /**
     * @param string $title
     *
     * @return string
     */
    protected function buildLink(string $title): string
    {
        $anchorName = $this->buildAnchorName($title);

        return sprintf('[%s](#%s)', $title, $anchorName);
    }
}
