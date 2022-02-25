<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Schema\Dumper;

use DOMDocument;
use DOMNode;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\VendorInfo;

/**
 * A class for dumping a schema to an XML representation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class XmlDumper implements DumperInterface
{
    /**
     * The DOMDocument object.
     *
     * @var \DOMDocument
     */
    private $document;

    /**
     * Constructor.
     *
     * @param \DOMDocument|null $document
     */
    public function __construct(?DOMDocument $document = null)
    {
        if ($document === null) {
            $document = new DOMDocument('1.0', 'utf-8');
            $document->formatOutput = true;
        }

        $this->document = $document;
    }

    /**
     * Dumps a single Database model into an XML formatted version.
     *
     * @param \Propel\Generator\Model\Database $database The database model
     *
     * @return string The dumped XML formatted output
     */
    public function dump(Database $database): string
    {
        $this->appendDatabaseNode($database, $this->document);

        return trim($this->document->saveXML());
    }

    /**
     * Dumps a single Schema model into an XML formatted version.
     *
     * @param \Propel\Generator\Model\Schema $schema The schema object
     * @param bool $doFinalInitialization Whether to validate the schema
     *
     * @return string
     */
    public function dumpSchema(Schema $schema, bool $doFinalInitialization = true): string
    {
        $rootNode = $this->document->createElement('app-data');
        $this->document->appendChild($rootNode);
        foreach ($schema->getDatabases($doFinalInitialization) as $database) {
            $this->appendDatabaseNode($database, $rootNode);
        }

        return trim($this->document->saveXML());
    }

    /**
     * Appends the generated <database> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Database $database The Database model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendDatabaseNode(Database $database, DOMNode $parentNode): void
    {
        /** @var \DOMElement $databaseNode */
        $databaseNode = $parentNode->appendChild($this->document->createElement('database'));
        $databaseNode->setAttribute('name', $database->getName());
        $databaseNode->setAttribute('defaultIdMethod', $database->getDefaultIdMethod());

        if ($package = $database->getPackage()) {
            $databaseNode->setAttribute('package', $package);
        }

        if ($schema = $database->getSchema()) {
            $databaseNode->setAttribute('schema', $schema);
        }

        $absoluteNamespace = $database->getNamespace(true);
        if ($absoluteNamespace) {
            $databaseNode->setAttribute('namespace', $absoluteNamespace);
        }

        if ($baseClass = $database->getBaseClass()) {
            $databaseNode->setAttribute('baseClass', $baseClass);
        }

        if ($baseQueryClass = $database->getBaseQueryClass()) {
            $databaseNode->setAttribute('baseQueryClass', $baseQueryClass);
        }

        if ($defaultNamingMethod = $database->getDefaultPhpNamingMethod()) {
            $databaseNode->setAttribute('defaultPhpNamingMethod', $defaultNamingMethod);
        }

        $defaultAccessorVisibility = $database->getDefaultAccessorVisibility();
        if ($defaultAccessorVisibility !== Database::VISIBILITY_PUBLIC) {
            $databaseNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $database->getDefaultMutatorVisibility();
        if ($defaultMutatorVisibility !== Database::VISIBILITY_PUBLIC) {
            $databaseNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        $defaultStringFormat = $database->getDefaultStringFormat();
        if ($defaultStringFormat !== Database::DEFAULT_STRING_FORMAT) {
            $databaseNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        if ($database->isHeavyIndexing()) {
            $databaseNode->setAttribute('heavyIndexing', 'true');
        }

        if ($tablePrefix = $database->getTablePrefix()) {
            $databaseNode->setAttribute('tablePrefix', $tablePrefix);
        }

        if ($database->isIdentifierQuotingEnabled()) {
            $databaseNode->setAttribute('identifierQuoting', 'true');
        }

        /*
            FIXME - Before we can add support for domains in the schema, we need
            to have a method of the Column that indicates whether the column was mapped
            to a SPECIFIC domain (since Column->getDomain() will always return a Domain object)

            foreach ($this->domainMap as $domain) {
                $this->appendDomainNode($databaseNode);
            }
         */
        foreach ($database->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $databaseNode);
        }

        foreach ($database->getTables() as $table) {
            $this->appendTableNode($table, $databaseNode);
        }
    }

    /**
     * Appends the generated <vendor> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\VendorInfo $vendorInfo The VendorInfo model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendVendorInformationNode(VendorInfo $vendorInfo, DOMNode $parentNode): void
    {
        /** @var \DOMElement $vendorNode */
        $vendorNode = $parentNode->appendChild($this->document->createElement('vendor'));
        $vendorNode->setAttribute('type', $vendorInfo->getType());

        foreach ($vendorInfo->getParameters() as $key => $value) {
            $parameterNode = $this->document->createElement('parameter');
            $parameterNode->setAttribute('name', $key);
            $parameterNode->setAttribute('value', $value);
            $vendorNode->appendChild($parameterNode);
        }
    }

    /**
     * Appends the generated <table> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Table $table The Table model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendTableNode(Table $table, DOMNode $parentNode): void
    {
        /** @var \DOMElement $tableNode */
        $tableNode = $parentNode->appendChild($this->document->createElement('table'));
        $tableNode->setAttribute('name', $table->getCommonName());

        $database = $table->getDatabase();
        $schema = $table->getSchema();
        if ($schema && $schema !== $database->getSchema()) {
            $tableNode->setAttribute('schema', $schema);
        }

        if (($idMethod = $table->getIdMethod()) !== IdMethod::NO_ID_METHOD) {
            $tableNode->setAttribute('idMethod', $idMethod);
        }

        if ($phpName = $table->getPhpName()) {
            $tableNode->setAttribute('phpName', $phpName);
        }

        $package = $table->getPackage();
        if ($package && !$table->isPackageOverriden()) {
            $tableNode->setAttribute('package', $package);
        }

        $absoluteNamespace = $table->getNamespace(true);
        if ($absoluteNamespace && $absoluteNamespace !== $database->getNamespace(true)) {
            $tableNode->setAttribute('namespace', $absoluteNamespace);
        }

        if ($table->isSkipSql()) {
            $tableNode->setAttribute('skipSql', 'true');
        }

        if ($table->isAbstract()) {
            $tableNode->setAttribute('abstract', 'true');
        }

        if ($interface = $table->getInterface()) {
            $tableNode->setAttribute('interface', $interface);
        }

        if ($table->isCrossRef()) {
            $tableNode->setAttribute('isCrossRef', 'true');
        }

        $phpNamingMethod = $table->getPhpNamingMethod();
        if ($phpNamingMethod && $phpNamingMethod !== $database->getDefaultPhpNamingMethod()) {
            $tableNode->setAttribute('phpNamingMethod', $phpNamingMethod);
        }

        if ($baseClass = $table->getBaseClass()) {
            $tableNode->setAttribute('baseClass', $baseClass);
        }

        if ($baseQueryClass = $table->getBaseQueryClass()) {
            $tableNode->setAttribute('baseQueryClass', $baseQueryClass);
        }

        if ($table->isReadOnly()) {
            $tableNode->setAttribute('readOnly', 'true');
        }

        if ($table->isReloadOnInsert()) {
            $tableNode->setAttribute('reloadOnInsert', 'true');
        }

        if ($table->isReloadOnUpdate()) {
            $tableNode->setAttribute('reloadOnUpdate', 'true');
        }

        if (($referenceOnly = $table->isForReferenceOnly()) !== null) {
            $tableNode->setAttribute('forReferenceOnly', $referenceOnly ? 'true' : 'false');
        }

        if ($alias = $table->getAlias()) {
            $tableNode->setAttribute('alias', $alias);
        }

        if ($description = $table->getDescription()) {
            $tableNode->setAttribute('description', $description);
        }

        $defaultStringFormat = $table->getDefaultStringFormat();
        if ($defaultStringFormat !== Table::DEFAULT_STRING_FORMAT) {
            $tableNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        $defaultAccessorVisibility = $table->getDefaultAccessorVisibility();
        if ($defaultAccessorVisibility !== Table::VISIBILITY_PUBLIC) {
            $tableNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $table->getDefaultMutatorVisibility();
        if ($defaultMutatorVisibility !== Table::VISIBILITY_PUBLIC) {
            $tableNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        foreach ($table->getColumns() as $column) {
            $this->appendColumnNode($column, $tableNode);
        }

        foreach ($table->getForeignKeys() as $foreignKey) {
            $this->appendForeignKeyNode($foreignKey, $tableNode);
        }

        foreach ($table->getIdMethodParameters() as $parameter) {
            $this->appendIdMethodParameterNode($parameter, $tableNode);
        }

        foreach ($table->getIndices() as $index) {
            $this->appendIndexNode($index, $tableNode);
        }

        foreach ($table->getUnices() as $index) {
            $this->appendUniqueIndexNode($index, $tableNode);
        }

        foreach ($table->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $tableNode);
        }

        foreach ($table->getBehaviors() as $behavior) {
            $this->appendBehaviorNode($behavior, $tableNode);
        }
    }

    /**
     * Appends the generated <behavior> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Behavior $behavior The Behavior model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendBehaviorNode(Behavior $behavior, DOMNode $parentNode): void
    {
        /** @var \DOMElement $behaviorNode */
        $behaviorNode = $parentNode->appendChild($this->document->createElement('behavior'));
        $behaviorNode->setAttribute('name', $behavior->getName());

        if ($behavior->allowMultiple()) {
            $behaviorNode->setAttribute('id', $behavior->getId());
        }

        foreach ($behavior->getParameters() as $name => $value) {
            $parameterNode = $behaviorNode->appendChild($this->document->createElement('parameter'));
            $parameterNode->setAttribute('name', $name);
            $parameterNode->setAttribute('value', $value);
        }
    }

    /**
     * Appends the generated <column> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Column $column The Column model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendColumnNode(Column $column, DOMNode $parentNode): void
    {
        /** @var \DOMElement $columnNode */
        $columnNode = $parentNode->appendChild($this->document->createElement('column'));
        $columnNode->setAttribute('name', $column->getName());

        if ($phpName = $column->getPhpName()) {
            $columnNode->setAttribute('phpName', $phpName);
        }

        $columnNode->setAttribute('type', $column->getType());

        $domain = $column->getDomain();
        if ($size = $domain->getSize()) {
            $columnNode->setAttribute('size', (string)$size);
        }

        if (($scale = $domain->getScale()) !== null) {
            $columnNode->setAttribute('scale', (string)$scale);
        }

        $platform = $column->getPlatform();
        if ($platform && !$column->isDefaultSqlType($platform)) {
            $columnNode->setAttribute('sqlType', $domain->getSqlType());
        }

        if ($description = $column->getDescription()) {
            $columnNode->setAttribute('description', $description);
        }

        if ($column->isPrimaryKey()) {
            $columnNode->setAttribute('primaryKey', 'true');
        }

        if ($column->isAutoIncrement()) {
            $columnNode->setAttribute('autoIncrement', 'true');
        }

        if ($column->isNotNull()) {
            $columnNode->setAttribute('required', 'true');
        }

        $defaultValue = $domain->getDefaultValue();
        if ($defaultValue) {
            $type = $defaultValue->isExpression() ? 'defaultExpr' : 'defaultValue';
            $columnNode->setAttribute($type, $defaultValue->getValue());
        }

        if ($column->isInheritance()) {
            $columnNode->setAttribute('inheritance', $column->getInheritanceType());
            foreach ($column->getInheritanceList() as $inheritance) {
                $this->appendInheritanceNode($inheritance, $columnNode);
            }
        }

        if ($column->isNodeKey()) {
            $columnNode->setAttribute('nodeKey', 'true');
            if ($nodeKeySeparator = $column->getNodeKeySep()) {
                $columnNode->setAttribute('nodeKeySep', $nodeKeySeparator);
            }
        }

        foreach ($column->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $columnNode);
        }
    }

    /**
     * Appends the generated <inheritance> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Inheritance $inheritance The Inheritance model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendInheritanceNode(Inheritance $inheritance, DOMNode $parentNode): void
    {
        /** @var \DOMElement $inheritanceNode */
        $inheritanceNode = $parentNode->appendChild($this->document->createElement('inheritance'));
        $inheritanceNode->setAttribute('key', $inheritance->getKey());
        $inheritanceNode->setAttribute('class', $inheritance->getClassName());

        if ($ancestor = $inheritance->getAncestor()) {
            $inheritanceNode->setAttribute('extends', $ancestor);
        }
    }

    /**
     * Appends the generated <foreign-key> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\ForeignKey $foreignKey The ForeignKey model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendForeignKeyNode(ForeignKey $foreignKey, DOMNode $parentNode): void
    {
        /** @var \DOMElement $foreignKeyNode */
        $foreignKeyNode = $parentNode->appendChild($this->document->createElement('foreign-key'));
        $foreignKeyNode->setAttribute('foreignTable', $foreignKey->getForeignTableCommonName());

        if ($schema = $foreignKey->getForeignSchemaName()) {
            $foreignKeyNode->setAttribute('foreignSchema', $schema);
        }

        $foreignKeyNode->setAttribute('name', $foreignKey->getName());

        if ($phpName = $foreignKey->getPhpName()) {
            $foreignKeyNode->setAttribute('phpName', $phpName);
        }

        if ($refPhpName = $foreignKey->getRefPhpName()) {
            $foreignKeyNode->setAttribute('refPhpName', $refPhpName);
        }

        if ($defaultJoin = $foreignKey->getDefaultJoin()) {
            $foreignKeyNode->setAttribute('defaultJoin', $defaultJoin);
        }

        if ($onDeleteBehavior = $foreignKey->getOnDelete()) {
            $foreignKeyNode->setAttribute('onDelete', $onDeleteBehavior);
        }

        if ($onUpdateBehavior = $foreignKey->getOnUpdate()) {
            $foreignKeyNode->setAttribute('onUpdate', $onUpdateBehavior);
        }

        for ($i = 0, $size = count($foreignKey->getLocalColumns()); $i < $size; $i++) {
            $refNode = $foreignKeyNode->appendChild($this->document->createElement('reference'));
            $refNode->setAttribute('local', $foreignKey->getLocalColumnName($i));
            $refNode->setAttribute('foreign', $foreignKey->getForeignColumnName($i));
        }

        foreach ($foreignKey->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $foreignKeyNode);
        }
    }

    /**
     * Appends the generated <id-method-parameter> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\IdMethodParameter $parameter The IdMethodParameter model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendIdMethodParameterNode(IdMethodParameter $parameter, DOMNode $parentNode): void
    {
        /** @var \DOMElement $idMethodParameterNode */
        $idMethodParameterNode = $parentNode->appendChild($this->document->createElement('id-method-parameter'));
        if ($name = $parameter->getName()) {
            $idMethodParameterNode->setAttribute('name', $name);
        }
        $idMethodParameterNode->setAttribute('value', $parameter->getValue());
    }

    /**
     * Appends the generated <index> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Index $index The Index model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendIndexNode(Index $index, DOMNode $parentNode): void
    {
        $this->appendGenericIndexNode('index', $index, $parentNode);
    }

    /**
     * Appends the generated <unique> XML node to its parent node.
     *
     * @param \Propel\Generator\Model\Unique $index The Unique model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendUniqueIndexNode(Unique $index, DOMNode $parentNode): void
    {
        $this->appendGenericIndexNode('unique', $index, $parentNode);
    }

    /**
     * Appends a generice <index> or <unique> XML node to its parent node.
     *
     * @param string $nodeType The node type (index or unique)
     * @param \Propel\Generator\Model\Index $index The Index model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     *
     * @return void
     */
    private function appendGenericIndexNode(string $nodeType, Index $index, DOMNode $parentNode): void
    {
        /** @var \DOMElement $indexNode */
        $indexNode = $parentNode->appendChild($this->document->createElement($nodeType));
        $indexNode->setAttribute('name', $index->getName());

        foreach ($index->getColumns() as $columnName) {
            $indexColumnNode = $indexNode->appendChild($this->document->createElement($nodeType . '-column'));
            $indexColumnNode->setAttribute('name', $columnName);

            if ($size = $index->getColumnSize($columnName)) {
                $indexColumnNode->setAttribute('size', $size);
            }
        }

        foreach ($index->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $indexNode);
        }
    }
}
