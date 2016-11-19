<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Schema\Dumper;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Entity;
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
     * @param \DOMDocument $document
     */
    public function __construct(\DOMDocument $document = null)
    {
        if (null === $document) {
            $document = new \DOMDocument('1.0', 'utf-8');
            $document->formatOutput = true;
        }

        $this->document = $document;
    }

    /**
     * Dumps a single Database model into an XML formatted version.
     *
     * @param  Database $database The database model
     * @return string   The dumped XML formatted output
     */
    public function dump(Database $database)
    {
        $this->appendDatabaseNode($database, $this->document);

        return trim($this->document->saveXML());
    }

    /**
     * Dumps a single Schema model into an XML formatted version.
     *
     * @param  Schema  $schema                The schema object
     * @param  boolean $doFinalInitialization Whether or not to validate the schema
     * @return string
     */
    public function dumpSchema(Schema $schema, $doFinalInitialization = true)
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
     * @param Database $database   The Database model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendDatabaseNode(Database $database, \DOMNode $parentNode)
    {
        $databaseNode = $parentNode->appendChild($this->document->createElement('database'));
        $databaseNode->setAttribute('name', $database->getName());
        $databaseNode->setAttribute('defaultIdMethod', $database->getDefaultIdMethod());

        if ($package = $database->getPackage()) {
            $databaseNode->setAttribute('package', $package);
        }

        if ($schema = $database->getSchema()) {
            $databaseNode->setAttribute('schema', $schema);
        }

        if ($namespace = $database->getNamespace()) {
            $databaseNode->setAttribute('namespace', $namespace);
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
        if (Database::DEFAULT_STRING_FORMAT !== $defaultStringFormat) {
            $databaseNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        if ($database->isHeavyIndexing()) {
            $databaseNode->setAttribute('heavyIndexing', 'true');
        }

        if ($tablePrefix = $database->getTablePrefix()) {
            $databaseNode->setAttribute('tablePrefix', $tablePrefix);
        }

        /*
            FIXME - Before we can add support for domains in the schema, we need
            to have a method of the Field that indicates whether the field was mapped
            to a SPECIFIC domain (since Field->getDomain() will always return a Domain object)

            foreach ($this->domainMap as $domain) {
                $this->appendDomainNode($databaseNode);
            }
         */
        foreach ($database->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $databaseNode);
        }

        foreach ($database->getEntities() as $entity) {
            $this->appendEntityNode($entity, $databaseNode);
        }
    }

    /**
     * Appends the generated <vendor> XML node to its parent node.
     *
     * @param VendorInfo $vendorInfo The VendorInfo model instance
     * @param \DOMNode   $parentNode The parent DOMNode object
     */
    private function appendVendorInformationNode(VendorInfo $vendorInfo, \DOMNode $parentNode)
    {
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
     * Appends the generated <entity> XML node to its parent node.
     *
     * @param Entity    $entity      The Entity model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendEntityNode(Entity $entity, \DOMNode $parentNode)
    {
        $entityNode = $parentNode->appendChild($this->document->createElement('entity'));
        $entityNode->setAttribute('name', $entity->getName());

        $database = $entity->getDatabase();
        $schema = $entity->getSchema();
        if ($schema && $schema !== $database->getSchema()) {
            $entityNode->setAttribute('schema', $schema);
        }

        if (IdMethod::NO_ID_METHOD !== ($idMethod = $entity->getIdMethod())) {
            $entityNode->setAttribute('idMethod', $idMethod);
        }

        if ($tableName = $entity->getCommonTableName()) {
            $entityNode->setAttribute('tableName', $tableName);
        }

        $package = $entity->getPackage();
        if ($package && !$entity->isPackageOverriden()) {
            $entityNode->setAttribute('package', $package);
        }

        if ($namespace = $entity->getNamespace()) {
            $entityNode->setAttribute('namespace', $namespace);
        }

        if ($entity->isSkipSql()) {
            $entityNode->setAttribute('skipSql', 'true');
        }

        if ($entity->isCrossRef()) {
            $entityNode->setAttribute('isCrossRef', 'true');
        }

        if ($entity->isReadOnly()) {
            $entityNode->setAttribute('readOnly', 'true');
        }

        if ($entity->isReloadOnInsert()) {
            $entityNode->setAttribute('reloadOnInsert', 'true');
        }

        if ($entity->isReloadOnUpdate()) {
            $entityNode->setAttribute('reloadOnUpdate', 'true');
        }

        if (null !== ($referenceOnly = $entity->isForReferenceOnly())) {
            $entityNode->setAttribute('forReferenceOnly', $referenceOnly ? 'true' : 'false');
        }

        if ($alias = $entity->getAlias()) {
            $entityNode->setAttribute('alias', $alias);
        }

        if ($description = $entity->getDescription()) {
            $entityNode->setAttribute('description', $description);
        }

        $defaultStringFormat = $entity->getDefaultStringFormat();
        if (Entity::DEFAULT_STRING_FORMAT !== $defaultStringFormat) {
            $entityNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        $defaultAccessorVisibility = $entity->getDefaultAccessorVisibility();
        if ($defaultAccessorVisibility !== Entity::VISIBILITY_PUBLIC) {
            $entityNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $entity->getDefaultMutatorVisibility();
        if ($defaultMutatorVisibility !== Entity::VISIBILITY_PUBLIC) {
            $entityNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        foreach ($entity->getFields() as $field) {
            $this->appendFieldNode($field, $entityNode);
        }

        foreach ($entity->getRelations() as $relation) {
            $this->appendRelationNode($relation, $entityNode);
        }

        foreach ($entity->getIdMethodParameters() as $parameter) {
            $this->appendIdMethodParameterNode($parameter, $entityNode);
        }

        foreach ($entity->getIndices() as $index) {
            $this->appendIndexNode($index, $entityNode);
        }

        foreach ($entity->getUnices() as $index) {
            $this->appendUniqueIndexNode($index, $entityNode);
        }

        foreach ($entity->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $entityNode);
        }

        foreach ($entity->getBehaviors() as $behavior) {
            $this->appendBehaviorNode($behavior, $entityNode);
        }
    }

    /**
     * Appends the generated <behavior> XML node to its parent node.
     *
     * @param Behavior $behavior   The Behavior model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendBehaviorNode(Behavior $behavior, \DOMNode $parentNode)
    {
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
     * Appends the generated <field> XML node to its parent node.
     *
     * @param Field   $field     The Field model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendFieldNode(Field $field, \DOMNode $parentNode)
    {
        $fieldNode = $parentNode->appendChild($this->document->createElement('field'));
        $fieldNode->setAttribute('name', $field->getName());

        if ($columnName = $field->getColumnName()) {
            $fieldNode->setAttribute('columnName', $columnName);
        }

        $fieldNode->setAttribute('type', $field->getType());

        $domain = $field->getDomain();
        if ($size = $domain->getSize()) {
            $fieldNode->setAttribute('size', $size);
        }

        if ($scale = $domain->getScale()) {
            $fieldNode->setAttribute('scale', $scale);
        }

        $platform = $field->getPlatform();
        if ($platform && !$field->isDefaultSqlType($platform)) {
            $fieldNode->setAttribute('sqlType', $domain->getSqlType());
        }

        if ($description = $field->getDescription()) {
            $fieldNode->setAttribute('description', $description);
        }

        if ($field->isPrimaryKey()) {
            $fieldNode->setAttribute('primaryKey', 'true');
        }

        if ($field->isAutoIncrement()) {
            $fieldNode->setAttribute('autoIncrement', 'true');
        }

        if ($field->isNotNull()) {
            $fieldNode->setAttribute('required', 'true');
        }

        $defaultValue = $domain->getDefaultValue();
        if ($defaultValue) {
            $type = $defaultValue->isExpression() ? 'defaultExpr' : 'defaultValue';
            $fieldNode->setAttribute($type, $defaultValue->getValue());
        }

        if ($field->isInheritance()) {
            $fieldNode->setAttribute('inheritance', $field->getInheritanceType());
            foreach ($field->getInheritanceList() as $inheritance) {
                $this->appendInheritanceNode($inheritance, $fieldNode);
            }
        }

        if ($field->isNodeKey()) {
            $fieldNode->setAttribute('nodeKey', 'true');
            if ($nodeKeySeparator = $field->getNodeKeySep()) {
                $fieldNode->setAttribute('nodeKeySep', $nodeKeySeparator);
            }
        }

        foreach ($field->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $fieldNode);
        }
    }

    /**
     * Appends the generated <inheritance> XML node to its parent node.
     *
     * @param Inheritance $inheritance The Inheritance model instance
     * @param \DOMNode    $parentNode  The parent DOMNode object
     */
    private function appendInheritanceNode(Inheritance $inheritance, \DOMNode $parentNode)
    {
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
     * @param Relation $relation The Relation model instance
     * @param \DOMNode   $parentNode The parent DOMNode object
     */
    private function appendRelationNode(Relation $relation, \DOMNode $parentNode)
    {
        $relationNode = $parentNode->appendChild($this->document->createElement('foreign-key'));
        $relationNode->setAttribute('target', $relation->getForeignEntityName());

        if ($relation->hasName()) {
            $relationNode->setAttribute('name', $relation->getName());
        }
        $relationNode->setAttribute('field', $relation->getField());

        if ($refField = $relation->getRefField()) {
            $relationNode->setAttribute('refField', $refField);
        }

        if ($defaultJoin = $relation->getDefaultJoin()) {
            $relationNode->setAttribute('defaultJoin', $defaultJoin);
        }

        if ($onDeleteBehavior = $relation->getOnDelete()) {
            $relationNode->setAttribute('onDelete', $onDeleteBehavior);
        }

        if ($onUpdateBehavior = $relation->getOnUpdate()) {
            $relationNode->setAttribute('onUpdate', $onUpdateBehavior);
        }

        for ($i = 0, $size = count($relation->getLocalFields()); $i < $size; $i++) {
            $refNode = $relationNode->appendChild($this->document->createElement('reference'));
            $refNode->setAttribute('local', $relation->getLocalFieldName($i));
            $refNode->setAttribute('foreign', $relation->getForeignFieldName($i));
        }

        foreach ($relation->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $relationNode);
        }
    }

    /**
     * Appends the generated <id-method-parameter> XML node to its parent node.
     *
     * @param IdMethodParameter $parameter  The IdMethodParameter model instance
     * @param \DOMNode          $parentNode The parent DOMNode object
     */
    private function appendIdMethodParameterNode(IdMethodParameter $parameter, \DOMNode $parentNode)
    {
        $idMethodParameterNode = $parentNode->appendChild($this->document->createElement('id-method-parameter'));
        if ($name = $parameter->getName()) {
            $idMethodParameterNode->setAttribute('name', $name);
        }
        $idMethodParameterNode->setAttribute('value', $parameter->getValue());
    }

    /**
     * Appends the generated <index> XML node to its parent node.
     *
     * @param Index    $index      The Index model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendIndexNode(Index $index, \DOMNode $parentNode)
    {
        $this->appendGenericIndexNode('index', $index, $parentNode);
    }

    /**
     * Appends the generated <unique> XML node to its parent node.
     *
     * @param Unique   $unique     The Unique model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendUniqueIndexNode(Unique $index, \DOMNode $parentNode)
    {
        $this->appendGenericIndexNode('unique', $index, $parentNode);
    }

    /**
     * Appends a generice <index> or <unique> XML node to its parent node.
     *
     * @param string   $nodeType   The node type (index or unique)
     * @param Index    $index      The Index model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendGenericIndexNode($nodeType, Index $index, \DOMNode $parentNode)
    {
        $indexNode = $parentNode->appendChild($this->document->createElement($nodeType));
        $indexNode->setAttribute('name', $index->getName());

        foreach ($index->getFields() as $fieldName) {
            $indexFieldNode = $indexNode->appendChild($this->document->createElement($nodeType.'-field'));
            $indexFieldNode->setAttribute('name', $fieldName);

            if ($size = $index->getFieldSize($fieldName)) {
                $indexFieldNode->setAttribute('size', $size);
            }
        }

        foreach ($index->getVendorInformation() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $indexNode);
        }
    }
}
