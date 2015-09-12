<?php

/**
* This file is part of the Propel package.
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*
* @license MIT License
*/

namespace Propel\Tests\Runtime;

use Propel\Tests\Bookstore\Map\TypeObjectTableMap;
use Propel\Tests\Bookstore\TypeObject;
use Propel\Tests\Bookstore\TypeObjectQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class TypeTest extends BookstoreTestBase
{
    public function testTypeHintClass()
    {
        $reflection = new \ReflectionClass('Propel\Tests\Bookstore\TypeObject');
        $method = $reflection->getMethod('setDummyObject');
        $param = $method->getParameters()[0];

        $this->assertEquals('Propel\Tests\Runtime\TypeTests\DummyObjectClass', $param->getClass()->name);
        $this->assertTrue($param->allowsNull());
    }

    public function testTypeHintArray()
    {
        $reflection = new \ReflectionClass('Propel\Tests\Bookstore\TypeObject');
        $method = $reflection->getMethod('setSomeArray');
        $param = $method->getParameters()[0];

        $this->assertTrue($param->isArray());
        $this->assertTrue($param->allowsNull());
    }

    public function testInterface()
    {
        $reflection = new \ReflectionClass('Propel\Tests\Bookstore\TypeObject');
        $method = $reflection->getMethod('setTypeObject');
        $param = $method->getParameters()[0];

        $this->assertEquals('Propel\Tests\Runtime\TypeTests\TypeObjectInterface', $param->getClass()->name);
        $this->assertTrue($param->allowsNull());
    }

    public function testObjectType()
    {
        TypeObjectQuery::create()->deleteAll();

        $a = 'abc123$%&';
        $b = '3456&*(][';
        $c = "_$%^xxx\0d2";

        $objectInstance = new TypeTests\DummyObjectClass();
        $objectInstance->setPropPublic($a);
        $objectInstance->setPropProtected($b);
        $objectInstance->setPropPrivate($c);

        $typeObjectEntity = new TypeObject();
        $this->assertNull($typeObjectEntity->getDetails(), 'object columns are null by default');

        $typeObjectEntity->setDetails($objectInstance);
        $this->assertEquals($objectInstance, $typeObjectEntity->getDetails());
        $this->assertEquals($a, $typeObjectEntity->getDetails()->getPropPublic());
        $this->assertEquals($b, $typeObjectEntity->getDetails()->getPropProtected());
        $this->assertEquals($c, $typeObjectEntity->getDetails()->getPropPrivate());

        $typeObjectEntity->save();
        
        $typeObjectEntity->setDetails($objectInstance);
        $this->assertFalse($typeObjectEntity->isModified('details'));


        $clone = clone $objectInstance;
        $clone->setPropPublic('changed');

        $typeObjectEntity->setDetails($clone);
        $this->assertTrue($typeObjectEntity->isModified('details'));
            

        TypeObjectTableMap::clearInstancePool();
        $typeObjectEntity = TypeObjectQuery::create()->findOne();

        $this->assertEquals($objectInstance, $typeObjectEntity->getDetails());
        $this->assertEquals($a, $typeObjectEntity->getDetails()->getPropPublic());
        $this->assertEquals($b, $typeObjectEntity->getDetails()->getPropProtected());
        $this->assertEquals($c, $typeObjectEntity->getDetails()->getPropPrivate());

        // change propPublic, same object
        $detailsObject = $typeObjectEntity->getDetails();
        $detailsObject->setPropPublic('changed');
        $typeObjectEntity->setDetails($detailsObject);
        $typeObjectEntity->save();
        TypeObjectTableMap::clearInstancePool();
        $typeObjectEntity = TypeObjectQuery::create()->findOne();

        $this->assertEquals($detailsObject, $typeObjectEntity->getDetails());
        $this->assertEquals('changed', $typeObjectEntity->getDetails()->getPropPublic());


        // same but with a more complex object
        $q = TypeObjectQuery::create();
        $typeObjectEntity->setDetails($q);
        $this->assertEquals($q, $typeObjectEntity->getDetails());

        $typeObjectEntity->save();

        TypeObjectTableMap::clearInstancePool();
        $typeObjectEntity = TypeObjectQuery::create()->findOne();

        $this->assertEquals($q, $typeObjectEntity->getDetails());
    }

}