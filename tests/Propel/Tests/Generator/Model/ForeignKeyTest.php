<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\ForeignKey;

/**
 * Unit test suite for the ForeignKey model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class ForeignKeyTest extends ModelTestCase
{
    /**
     * @return void
     */
    public function testCreateNewForeignKey()
    {
        $fk = new ForeignKey('book_author');

        $this->assertSame('book_author', $fk->getName());
        $this->assertFalse($fk->hasOnUpdate());
        $this->assertFalse($fk->hasOnDelete());
        $this->assertFalse($fk->isComposite());
        $this->assertFalse($fk->isSkipSql());
    }

    /**
     * @return void
     */
    public function testForeignKeyIsForeignPrimaryKey()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock();
        $foreignTable = $this->getTableMock('authors');

        $localTable = $this->getTableMock('books', [
            'platform' => $platform,
            'database' => $database,
        ]);

        $idColumn = $this->getColumnMock('id');
        $authorIdColumn = $this->getColumnMock('author_id');

        $database
            ->expects($this->any())
            ->method('getTable')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignTable));

        $foreignTable
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue([$idColumn]));

        $foreignTable
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('id'))
            ->will($this->returnValue($idColumn));

        $localTable
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($authorIdColumn));

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->setForeignTableCommonName('authors');
        $fk->addReference('author_id', 'id');

        $fkMapping = $fk->getColumnObjectsMapping();

        $this->assertTrue($fk->isForeignPrimaryKey());
        $this->assertCount(1, $fk->getForeignColumnObjects());
        $this->assertSame($authorIdColumn, $fkMapping[0]['local']);
        $this->assertSame($idColumn, $fkMapping[0]['foreign']);
        $this->assertSame($idColumn, $fk->getForeignColumn(0));
    }

    /**
     * @return void
     */
    public function testForeignKeyIsForeignNonPrimaryKey()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock();
        $foreignTable = $this->getTableMock('bookstore_employee_account');

        $localTable = $this->getTableMock('acct_audit_log', [
            'platform' => $platform,
            'database' => $database,
        ]);

        $idColumn = $this->getColumnMock('id');
        $secondaryColumn = $this->getColumnMock('secondary');
        $loginColumn = $this->getColumnMock('login');
        $uidColumn = $this->getColumnMock('uid');

        $database
            ->expects($this->any())
            ->method('getTable')
            ->with($this->equalTo('bookstore_employee_account'))
            ->will($this->returnValue($foreignTable));

        $foreignTable
            ->expects($this->any())
            ->method('getPrimaryKey')
            ->will($this->returnValue([$idColumn, $secondaryColumn]));

        $foreignTable
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('login'))
            ->will($this->returnValue($loginColumn));

        $localTable
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('uid'))
            ->will($this->returnValue($uidColumn));

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->setForeignTableCommonName('bookstore_employee_account');
        $fk->addReference('uid', 'login');

        $fkMapping = $fk->getColumnObjectsMapping();
        $this->assertFalse($fk->isForeignPrimaryKey());
        $this->assertTrue($fk->isForeignNonPrimaryKey());
        $this->assertCount(1, $fk->getForeignColumnObjects());
        $this->assertSame($uidColumn, $fkMapping[0]['local']);
        $this->assertSame($loginColumn, $fkMapping[0]['foreign']);
        $this->assertSame($loginColumn, $fk->getForeignColumn(0));
    }
    /**
     * @return void
     */
    public function testForeignKeyDoesNotUseRequiredColumns()
    {
        $column = $this->getColumnMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(false));

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column));

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isLocalColumnsRequired());
    }

    /**
     * @return void
     */
    public function testForeignKeyUsesRequiredColumns()
    {
        $column = $this->getColumnMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(true));

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column));

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalColumnsRequired());
    }

    /**
     * @return void
     */
    public function testCantGetInverseForeignKey()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(false);
        $foreignTable = $this->getTableMock('authors');

        $localTable = $this->getTableMock('books', [
            'platform' => $platform,
            'database' => $database,
        ]);

        $database
            ->expects($this->any())
            ->method('getTable')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignTable));

        $inversedFk = new ForeignKey();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setTable($localTable);

        $foreignTable
            ->expects($this->any())
            ->method('getForeignKeys')
            ->will($this->returnValue([]));

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->addReference('author_id', 'id');
        $fk->setForeignTableCommonName('authors');

        $this->assertSame('authors', $fk->getForeignTableCommonName());
        $this->assertSame('authors', $fk->getForeignTableName());
        $this->assertNull($fk->getInverseFK());
        $this->assertFalse($fk->isMatchedByInverseFK());
    }

    /**
     * @return void
     */
    public function testGetInverseForeignKey()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(true);
        $foreignTable = $this->getTableMock('authors');

        $localTable = $this->getTableMock('books', [
            'platform' => $platform,
            'database' => $database,
        ]);

        $database
            ->expects($this->any())
            ->method('getTable')
            ->with($this->equalTo('bookstore.authors'))
            ->will($this->returnValue($foreignTable));

        $inversedFk = new ForeignKey();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setTable($localTable);
        $inversedFk->setForeignSchemaName('bookstore');
        $inversedFk->setForeignTableCommonName('authors');

        $foreignTable
            ->expects($this->any())
            ->method('getForeignKeys')
            ->will($this->returnValue([$inversedFk]));

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->addReference('author_id', 'id');
        $fk->setForeignSchemaName('bookstore');
        $fk->setForeignTableCommonName('authors');

        $this->assertSame('authors', $fk->getForeignTableCommonName());
        $this->assertSame('bookstore.authors', $fk->getForeignTableName());
        $this->assertInstanceOf('Propel\Generator\Model\Table', $fk->getForeignTable());
        $this->assertSame($inversedFk, $fk->getInverseFK());
        $this->assertTrue($fk->isMatchedByInverseFK());
    }

    /**
     * @return void
     */
    public function testGetLocalColumn()
    {
        $column = $this->getColumnMock('id');

        $table = $this->getTableMock('books');
        $table
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column));

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('author_id', 'id');

        $this->assertCount(1, $fk->getLocalColumnObjects());
        $this->assertInstanceOf('Propel\Generator\Model\Column', $fk->getLocalColumn(0));
    }

    /**
     * @return void
     */
    public function testForeignKeyIsNotLocalPrimaryKey()
    {
        $pks = [$this->getColumnMock('id')];

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks));

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('book_id', 'id');

        $this->assertFalse($fk->isLocalPrimaryKey());
    }

    /**
     * @return void
     */
    public function testForeignKeyIsLocalPrimaryKey()
    {
        $pks = [
            $this->getColumnMock('book_id'),
            $this->getColumnMock('author_id'),
        ];

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks));

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalPrimaryKey());
    }

    /**
     * @return void
     */
    public function testGetOtherForeignKeys()
    {
        $fk = new ForeignKey();

        $fks[] = new ForeignKey();
        $fks[] = $fk;
        $fks[] = new ForeignKey();

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getForeignKeys')
            ->will($this->returnValue($fks));

        $fk->setTable($table);

        $this->assertCount(2, $fk->getOtherFks());
    }

    /**
     * @return void
     */
    public function testSetForeignSchemaName()
    {
        $fk = new ForeignKey();
        $fk->setForeignSchemaName('authors');

        $this->assertSame('authors', $fk->getForeignSchemaName());
    }

    /**
     * @return void
     */
    public function testClearReferences()
    {
        $fk = new ForeignKey();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');
        $fk->clearReferences();

        $this->assertCount(0, $fk->getLocalColumns());
        $this->assertCount(0, $fk->getForeignColumns());
    }

    /**
     * @return void
     */
    public function testAddMultipleReferences()
    {
        $fk = new ForeignKey();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isComposite());
        $this->assertCount(2, $fk->getLocalColumns());
        $this->assertCount(2, $fk->getForeignColumns());

        $this->assertSame('book_id', $fk->getLocalColumnName(0));
        $this->assertSame('id', $fk->getForeignColumnName(0));
        $this->assertSame('id', $fk->getMappedForeignColumn('book_id'));

        $this->assertSame('author_id', $fk->getLocalColumnName(1));
        $this->assertSame('id', $fk->getForeignColumnName(1));
        $this->assertSame('id', $fk->getMappedForeignColumn('author_id'));
    }

    /**
     * @return void
     */
    public function testAddSingleStringReference()
    {
        $fk = new ForeignKey();
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalColumns());
        $this->assertCount(1, $fk->getForeignColumns());

        $this->assertSame('author_id', $fk->getMappedLocalColumn('id'));
    }

    /**
     * @return void
     */
    public function testAddSingleArrayReference()
    {
        $reference = ['local' => 'author_id', 'foreign' => 'id'];

        $fk = new ForeignKey();
        $fk->addReference($reference);

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalColumns());
        $this->assertCount(1, $fk->getForeignColumns());

        $this->assertSame($reference['local'], $fk->getMappedLocalColumn($reference['foreign']));
    }

    /**
     * @return void
     */
    public function testAddSingleColumnReference()
    {
        $fk = new ForeignKey();
        $fk->addReference(
            $this->getColumnMock('author_id'),
            $this->getColumnMock('id')
        );

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalColumns());
        $this->assertCount(1, $fk->getForeignColumns());

        $this->assertSame('author_id', $fk->getMappedLocalColumn('id'));
    }

    /**
     * @return void
     */
    public function testSetTable()
    {
        $table = $this->getTableMock('book');
        $table
            ->expects($this->once())
            ->method('getSchema')
            ->will($this->returnValue('books'));

        $fk = new ForeignKey();
        $fk->setTable($table);

        $this->assertInstanceOf('Propel\Generator\Model\Table', $fk->getTable());
        $this->assertSame('books', $fk->getSchemaName());
        $this->assertSame('book', $fk->getTableName());
    }

    /**
     * @return void
     */
    public function testSetDefaultJoin()
    {
        $fk = new ForeignKey();
        $fk->setDefaultJoin('INNER');

        $this->assertSame('INNER', $fk->getDefaultJoin());
    }

    /**
     * @return void
     */
    public function testSetNames()
    {
        $fk = new ForeignKey();
        $fk->setName('book_author');
        $fk->setPhpName('Author');
        $fk->setRefPhpName('Books');

        $this->assertSame('book_author', $fk->getName());
        $this->assertSame('Author', $fk->getPhpName());
        $this->assertSame('Books', $fk->getRefPhpName());
    }

    /**
     * @return void
     */
    public function testSkipSql()
    {
        $fk = new ForeignKey();
        $fk->setSkipSql(true);

        $this->assertTrue($fk->isSkipSql());
    }

    /**
     * @return void
     */
    public function testGetOnActionBehaviors()
    {
        $fk = new ForeignKey();
        $fk->setOnUpdate('SETNULL');
        $fk->setOnDelete('CASCADE');

        $this->assertSame('SET NULL', $fk->getOnUpdate());
        $this->assertTrue($fk->hasOnUpdate());

        $this->assertSame('CASCADE', $fk->getOnDelete());
        $this->assertTrue($fk->hasOnDelete());
    }

    /**
     * @dataProvider provideOnActionBehaviors
     *
     * @return void
     */
    public function testNormalizeForeignKey($behavior, $normalized)
    {
        $fk = new ForeignKey();

        $this->assertSame($normalized, $fk->normalizeFKey($behavior));
    }

    public function provideOnActionBehaviors()
    {
        return [
            [null, ''],
            ['none', ''],
            ['NONE', ''],
            ['setnull', 'SET NULL'],
            ['SETNULL', 'SET NULL'],
            ['cascade', 'CASCADE'],
            ['CASCADE', 'CASCADE'],
            ['NOACTION', 'NO ACTION'],
        ];
    }

    /**
     * @dataProvider provideOnActionBehaviorsWithDefault
     *
     * @return void
     */
    public function testNormalizeForeignKeyWithDefault($behavior, $default, $normalized)
    {
        $fk = new ForeignKey();

        $this->assertSame($normalized, $fk->normalizeFKey($behavior, $default));
    }

    public function provideOnActionBehaviorsWithDefault()
    {
        return [
            [null, 'RESTRICT', 'RESTRICT'],
            ['none', 'RESTRICT', 'RESTRICT'],
            ['NONE', 'RESTRICT', 'RESTRICT'],
            ['setnull', 'RESTRICT', 'SET NULL'],
            ['SETNULL', 'RESTRICT', 'SET NULL'],
            ['cascade', 'RESTRICT', 'CASCADE'],
            ['CASCADE', 'RESTRICT', 'CASCADE'],
            [ForeignKey::NOACTION, ForeignKey::RESTRICT, ForeignKey::NOACTION],
            [null, null, ''],
        ];
    }
}
