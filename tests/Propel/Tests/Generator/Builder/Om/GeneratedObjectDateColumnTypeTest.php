<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use GeneratedObjectDateColumnTypeEntity;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Connection\StatementWrapper;
use Propel\Tests\TestCase;

class GeneratedObjectDateColumnTypeTest extends TestCase
{
    public function setUp(): void
    {
        if (!\class_exists('GeneratedObjectDateColumnTypeEntity')) {
            $schema = <<<'XML'
<database name="generated_object_date_column_type">
    <table name="generated_object_date_column_type_entity">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="datecolumn" type="DATE"/>
    </table>
</database>
XML;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testInsertDateColumn(): void
    {
        assert(\class_exists(GeneratedObjectDateColumnTypeEntity::class));
        $entity = new GeneratedObjectDateColumnTypeEntity();
        $this->assertTrue(\method_exists($entity, 'setDatecolumn'));
        $this->assertTrue(\method_exists($entity, 'save'));
        $dateValue = new DateTimeImmutable('2021-06-25 12:26');
        $entity->setDatecolumn($dateValue);

        $insertStatement = $this->createMockInsertStatement();
        $insertStatement
            ->method('bindValue')
            ->withConsecutive(
                [':p0', null, PDO::PARAM_INT],
                [':p1', $dateValue->format('Y-m-d'), PDO::PARAM_STR]
            );

        $con = $this->createMockConnection();
        $con
            ->expects($this->once())
            ->method('prepare')
            ->willReturnCallback(function ($sql) use ($insertStatement) {
                $this->assertEquals(
                    'INSERT INTO generated_object_date_column_type_entity '
                    . '(id, datecolumn) VALUES (:p0, :p1)',
                    $sql
                );
                return $insertStatement;
            });
        $entity->save($con);
    }

    /**
     * @return ConnectionInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockConnection(): ConnectionInterface
    {
        $con = $this->createPartialMock(PdoConnection::class, [
            'prepare',
            'transaction',
            'lastInsertId',
        ]);
        $con
            ->method('transaction')
            ->willReturnCallback(function ($callable) {
                return \call_user_func($callable);
            });
        $con
            ->method('lastInsertId')
            ->willReturn(2);
        return $con;
    }

    /**
     * @return StatementInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockInsertStatement(): StatementInterface
    {
        $insertStatement = $this->createPartialMock(StatementWrapper::class, [
            'bindValue',
            'execute',
        ]);
        $insertStatement
            ->method('execute')
            ->willReturn(true);
        return $insertStatement;
    }
}
