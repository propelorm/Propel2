<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
require_once 'builder/om/OMBuilder.php';
require_once 'builder/util/XmlToAppData.php';
require_once 'platform/MysqlPlatform.php';

/**
 * Test class for OMBuilder.
 *
 * @author     François Zaninotto
 * @version    $Id: OMBuilderBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 * @package    generator.builder.om
 */
class OMBuilderTest extends PHPUnit_Framework_TestCase 
{
	public function setUp()
	{
		$xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
		$appData = $xmlToAppData->parseFile('fixtures/bookstore/schema.xml');
		$this->database = $appData->getDatabase("bookstore");
	}
	
	protected function getForeignKey($tableName, $index)
	{
		$fks = $this->database->getTable($tableName)->getForeignKeys();
		return $fks[$index];
	}
	
	public static function testGetRelatedBySuffixDataProvider()
	{
		return array(
			array('book', 0, '', ''),
			array('essay', 0, 'RelatedByFirstAuthor', 'RelatedByFirstAuthor'),
			array('essay', 1, 'RelatedBySecondAuthor', 'RelatedBySecondAuthor'),
			array('bookstore_employee', 0, 'RelatedBySupervisorId', 'RelatedById'),
		);
	}
	
	/**
	 * @dataProvider testGetRelatedBySuffixDataProvider
	 */
	public function testGetRelatedBySuffix($table, $index, $expectedSuffix, $expectedReverseSuffix)
	{
		$fk = $this->getForeignKey($table, $index);
		$this->assertEquals($expectedSuffix, TestableOMBuilder::getRelatedBySuffix($fk));
		$this->assertEquals($expectedReverseSuffix, TestableOMBuilder::getRelatedBySuffix($fk, true));
	}

}

class TestableOMBuilder extends OMBuilder
{
	public static function getRelatedBySuffix(ForeignKey $fk, $reverseOnSelf = false)
	{
		return parent::getRelatedBySuffix($fk, $reverseOnSelf);
	}
	
	public function getUnprefixedClassname() {}
}