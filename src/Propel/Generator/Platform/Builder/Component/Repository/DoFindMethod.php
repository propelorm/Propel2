<?php

namespace Propel\Generator\Platform\Builder\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the doFind method based on a fast direct SQL execution.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class DoFindMethod extends BuildComponent
{
    use NamingTrait;
    use RepositoryTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();
        $entity = $this->getEntity();

        $pkType = $entity->getFirstPrimaryKeyField()->getPhpType();
        if ($entity->hasCompositePrimaryKey()) {
            $pkType = 'array';
        }

        // this method is not needed if the entity has no primary key
        if (!$entity->hasPrimaryKey()) {
            return '';
        }

//        $entityMapClassName = $this->getEntityMapClassName();
//        $ARClassName = $this->getObjectClassName();

//        $this->getDefinition()->declareClassFromBuilder($this->getStubObjectBuilder());
//        $this->declareClasses('\PDO');
        $selectFields = array();
        foreach ($entity->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                $selectFields[] = $this->quoteIdentifier($this->getPlatform()->getName($field));
            }
        }
        $conditions = array();
        foreach ($entity->getPrimaryKey() as $index => $field) {
            $conditions []= sprintf('%s = :p%d', $this->quoteIdentifier($field->getName()), $index);
        }
        $query = sprintf(
            'SELECT %s FROM %s WHERE %s',
            implode(', ', $selectFields),
            $this->quoteIdentifier($entity->getTableName()),
            implode(' AND ', $conditions)
        );
        $pks = array();
        if ($entity->hasCompositePrimaryKey()) {
            foreach ($entity->getPrimaryKey() as $index => $field) {
                $pks []= "\$key[$index]";
            }
        } else {
            $pks []= "\$key";
        }

        $pkHashFromRow = $this->getFirstLevelCacheKeySnippet($pks);

        $dbName = $entity->getDatabase()->getName();

        $body = "
        \$connection = \$this->getConfiguration()->getConnectionManager('$dbName')->getWriteConnection();
        \$sql = '$query';
        try {
            \$stmt = \$connection->prepare(\$sql);";
        if ($entity->hasCompositePrimaryKey()) {
            foreach ($entity->getPrimaryKey() as $index => $field) {
                $body .= $this->getPlatform()->getFieldBindingPHP($field, "':p$index'", "\$key[$index]", '            ');
            }
        } else {
            $pk = $entity->getPrimaryKey();
            $field = $pk[0];
            $body .= $this->getPlatform()->getFieldBindingPHP($field, "':p0'", "\$key", '            ');
        }

        $body .= "
            \$stmt->execute();
        } catch (Exception \$e) {
            Propel::log(\$e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', \$sql), 0, \$e);
        }

        \$obj = null;
        if (\$row = \$stmt->fetch(\\PDO::FETCH_NUM)) {
            \$populateInfo = \$this->getEntityMap()->populateObject(\$row);
        } else {
            return null;
        }

        //\$this->addFirstLevelCache($pkHashFromRow, \$obj);

        return \$populateInfo[0];
        ";

//        if ($entity->getChildrenField()) {
//            $body .="
//            \$cls = {$entityMapClassName}::getOMClass(\$row, 0, false);
//            /** @var $ARClassName \$obj */
//            \$obj = new \$cls();";
//        } else {
//            $body .="
//            /** @var $ARClassName \$obj */
//            \$obj = new $ARClassName();";
//        }
//        $body .= "
//            \$obj->hydrate(\$row);
//            {$entityMapClassName}::addInstanceToPool(\$obj, $pkHashFromRow);
//        }
//        \$stmt->closeCursor();
//
//        return \$obj;
//";

        $this->addMethod('doFind', 'protected')
            ->addSimpleParameter('key')
            ->setType($entityClassName)
            ->setDescription('doFind implementation for SQL Platforms')
            ->setBody($body);
    }
}