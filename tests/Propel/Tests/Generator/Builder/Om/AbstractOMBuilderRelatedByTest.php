<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Tests\TestCase;

/**
 * Test class for OMBuilder.
 *
 * @author François Zaninotto
 * @version    $Id: OMBuilderBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 */
class AbstractOMBuilderRelatedByTest extends TestCase
{
    public static $database;

    public function setUp()
    {
        // run only once to save execution time
        if (null == self::$database) {
            $schemaReader = new SchemaReader(new DefaultPlatform());
            $appData = $schemaReader->parseFile(realpath(__DIR__ . '/../../../../../Fixtures/bookstore/schema.xml'));
            self::$database = $appData->getDatabase("bookstore");
        }
    }

    protected function getForeignKey($tableName, $index)
    {
        $fks = self::$database->getTable($tableName)->getForeignKeys();

        return $fks[$index];
    }

    public static function getRelatedBySuffixDataProvider()
    {
        return array(
            array('book', 0, '', ''),
            array('essay', 0, 'RelatedByFirstAuthorId', 'RelatedByFirstAuthorId'),
            array('essay', 1, 'RelatedBySecondAuthorId', 'RelatedBySecondAuthorId'),
            array('essay', 2, 'RelatedById', 'RelatedByNextEssayId'),
            array('bookstore_employee', 0, 'RelatedById', 'RelatedBySupervisorId'),
            array('composite_essay', 0, 'RelatedById0', 'RelatedByFirstEssayId'),
            array('composite_essay', 1, 'RelatedById1', 'RelatedBySecondEssayId'),
            array('man', 0, 'RelatedByWifeId', 'RelatedByWifeId'),
            array('woman', 0, 'RelatedByHusbandId', 'RelatedByHusbandId'),
        );
    }

    /**
     * @dataProvider getRelatedBySuffixDataProvider
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
    public static function getRelatedBySuffix(ForeignKey $fk)
    {
        return parent::getRelatedBySuffix($fk);
    }

    public static function getRefRelatedBySuffix(ForeignKey $fk)
    {
        return parent::getRefRelatedBySuffix($fk);
    }

    public function getUnprefixedClassName()
    {
    }

    protected function addClassOpen(&$script)
    {

    }

    protected function addClassBody(&$script)
    {

    }

    protected function addClassClose(&$script)
    {

    }
}
