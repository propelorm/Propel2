<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\CrossRelation;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\OraclePlatform;
use Propel\Generator\Platform\SqlsrvPlatform;

class LazyLoadingMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isLazyLoad()) {
                $this->addLazyLoader($field);
            }
        }
    }


    /**
     * @param CrossRelation $crossRelation
     */
    protected function addCrossRelationLoader(CrossRelation $crossRelation)
    {
        $objectClassName = $this->getObjectClassName();

        foreach ($crossRelation->getRelations() as $relation) {
            $methodName = 'load' . ucfirst($this->getCrossRelationRelationVarName($relation));

            $body = "
\$query = \$this->getConfiguration()->getEntityMap('{$relation->getForeignEntity()->getFullClassName()}')->createQuery();



return \$query->find();
";

            $this->addMethod($methodName)
                ->addSimpleParameter('entity', $objectClassName)
                ->setBody($body);
        }
    }

    /**
     * Adds the function body for the lazy loader method.
     *
     * @param Field $field
     */
    protected function addLazyLoader(Field $field)
    {
        $platform = $this->getPlatform();
        $fieldName = $field->getName();

        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
        $this->getDefinition()->declareUse('Propel\Runtime\ActiveQuery\\ModelCriteria');

        $methodName = 'load' . ucfirst($fieldName);

        $body = "";

        // pdo_sqlsrv driver requires the use of PDOStatement::bindField() or a hex string will be returned
        if ($field->getType() === PropelTypes::BLOB && $platform instanceof SqlsrvPlatform) {
            $body .= "
\$c = \$this->buildPkeyCriteria(\$entity);
\$c->addSelectField(".$field->getFQConstantName().");
try {
    \$row = array(0 => null);
    \$dataFetcher = \$c->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find();
    if (\$dataFetcher instanceof PDODataFetcher) {
        \$dataFetcher->bindField(1, \$row[0], PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    }
    \$row = \$dataFetcher->fetch(PDO::FETCH_BOUND);
    \$dataFetcher->close();";
} else {
    $body .= "
\$c = \$this->buildPkeyCriteria(\$entity);
\$c->addSelectField(".$field->getFQConstantName().");
try {
    \$dataFetcher = \$c->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find();
    \$row = \$dataFetcher->fetch();
    \$dataFetcher->close();";
        }

        $body .= "

    \$firstField = \$row ? current(\$row) : null;
";

        if ($field->getType() === PropelTypes::CLOB && $platform instanceof OraclePlatform) {
            // PDO_OCI returns a stream for CLOB objects, while other PDO adapters return a string...
            $body .= "
    if (\$firstField) {
        return stream_get_contents(\$firstField);
    }";
        } elseif ($field->isLobType() && !$platform->hasStreamBlobImpl()) {
            $body .= "
    if (\$firstField !== null) {
        \$$fieldName = fopen('php://memory', 'r+');
        fwrite(\$$fieldName, \$firstField);
        rewind(\$$fieldName);
        return \$$fieldName;
    } else {
        return null;
    }";
        } elseif ($field->isPhpPrimitiveType()) {
            $body .= "
    return (\$firstField !== null) ? (".$field->getPhpType().") \$firstField : null;";
        } elseif ($field->isPhpObjectType()) {
            $body .= "
    return (\$firstField !== null) ? new ".$field->getPhpType()."(\$firstField) : null;";
        } else {
            $body .= "
    return \$firstField;";
        }

        $body .= "
} catch (Exception \$e) {
    throw new PropelException(\"Error loading value for [$fieldName] field on demand.\", 0, \$e);
}";
        $objectClassName = $this->getObjectClassName();
        $this->getDefinition()->declareUse($this->getObjectClassName(true));

        $this->addMethod($methodName)
            ->addSimpleParameter('entity', $objectClassName)
            ->setBody($body);
    }
}