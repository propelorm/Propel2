<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Tests\TestCase;

/**
 * Test class for OMBuilder.
 *
 * @author François Zaninotto
 */
class AbstractOMBuilderRelatedByTest extends TestCase
{
    public static $database;

    /**
     * @return void
     */
    public function setUp(): void
    {
        // run only once to save execution time
        if (null == self::$database) {
            $schemaReader = new SchemaReader(new DefaultPlatform());
            $appData = $schemaReader->parseFile(realpath(__DIR__ . '/../../../../../Fixtures/bookstore/schema.xml'));
            self::$database = $appData->getDatabase('bookstore');
        }
    }

    protected function getForeignKey($tableName, $index)
    {
        $fks = self::$database->getTable($tableName)->getForeignKeys();

        return $fks[$index];
    }

    public static function getRelatedBySuffixDataProvider()
    {
        return [
            ['book', 0, '', ''],
            ['essay', 0, 'RelatedByFirstAuthorId', 'RelatedByFirstAuthorId'],
            ['essay', 1, 'RelatedBySecondAuthorId', 'RelatedBySecondAuthorId'],
            ['essay', 2, 'RelatedById', 'RelatedByNextEssayId'],
            ['bookstore_employee', 0, 'RelatedById', 'RelatedBySupervisorId'],
            ['composite_essay', 0, 'RelatedById0', 'RelatedByFirstEssayId'],
            ['composite_essay', 1, 'RelatedById1', 'RelatedBySecondEssayId'],
            ['man', 0, 'RelatedByWifeId', 'RelatedByWifeId'],
            ['woman', 0, 'RelatedByHusbandId', 'RelatedByHusbandId'],
        ];
    }

    /**
     * @dataProvider getRelatedBySuffixDataProvider
     *
     * @return void
     */
    public function testGetRelatedBySuffix($table, $index, $expectedSuffix, $expectedReverseSuffix)
    {
        $fk = $this->getForeignKey($table, $index);
        $this->assertEquals($expectedSuffix, TestableOMBuilder::getRefRelatedBySuffix($fk));
        $this->assertEquals($expectedReverseSuffix, TestableOMBuilder::getRelatedBySuffix($fk));
    }
}

class TestableOMBuilder extends AbstractOMBuilder
{
    public static function getRelatedBySuffix(ForeignKey $fk): string
    {
        return parent::getRelatedBySuffix($fk);
    }

    public static function getRefRelatedBySuffix(ForeignKey $fk): string
    {
        return parent::getRefRelatedBySuffix($fk);
    }

    /**
     * @return void
     */
    public function getUnprefixedClassName(): string
    {
        return '';
    }

    /**
     * @return void
     */
    protected function addClassOpen(&$script): void
    {
    }

    /**
     * @return void
     */
    protected function addClassBody(&$script): void
    {
    }

    /**
     * @return void
     */
    protected function addClassClose(&$script): void
    {
    }
}
