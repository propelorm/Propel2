<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;

class doCountMethod extends BuildComponent
{
    public function process()
    {
        $body = "
\$dbMap = \$this->getConfiguration()->getDatabase(\$this->getDbName());
\$db = \$this->getConfiguration()->getAdapter(\$this->getDbName());

\$con = \$this->getConfiguration()->getConnectionManager(\$this->getDbName())->getWriteConnection();

\$key = \$this->getQueryKey();
if (\$key && \$this->cacheContains(\$key)) {
    \$params = \$this->getParams();
    \$sql = \$this->cacheFetch(\$key);
} else {
    \$needsComplexCount = \$this->getGroupByFields()
        || \$this->getOffset()
        || \$this->getLimit() >= 0
        || \$this->getHaving()
        || in_array(Criteria::DISTINCT, \$this->getSelectModifiers())
        || count(\$this->selectQueries) > 0
    ;

    \$params = array();
    if (\$needsComplexCount) {
        if (\$this->needsSelectAliases()) {
            if (\$this->getHaving()) {
                throw new LogicException('Propel cannot create a COUNT query when using HAVING and  duplicate field names in the SELECT part');
            }
            \$db->turnSelectFieldsToAliases(\$this);
        }
        \$selectSql = \$this->createSelectSql(\$params);
        \$sql = 'SELECT COUNT(*) FROM (' . \$selectSql . ') propelmatch4cnt';
    } else {
        // Replace SELECT fields with COUNT(*)
        \$this->clearSelectFields()->addSelectField('COUNT(*)');
        \$sql = \$this->createSelectSql(\$params);
    }

    if (\$key) {
            \$this->cacheStore(\$key, \$sql);
        }
}

try {
    \$stmt = \$con->prepare(\$sql);
    \$db->bindValues(\$stmt, \$params, \$dbMap);
    \$stmt->execute();
} catch (\\Exception \$e) {
    \$this->getConfiguration()->log(\$e->getMessage(), Configuration::LOG_ERR);
    throw new PropelException(sprintf('Unable to execute COUNT statement [%s]', \$sql));
}

return \$con->getDataFetcher(\$stmt);
";
        $this->addMethod('doCount')->setBody($body);
    }
}
