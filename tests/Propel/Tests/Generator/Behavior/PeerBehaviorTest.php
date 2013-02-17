<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Behavior\Table3Peer;
use Propel\Tests\Bookstore\Behavior\Map\Table3TableMap;

/**
 * Tests the generated Peer behavior hooks.
 *
 * @author Francois Zaninotto
 */
class PeerBehaviorTest extends BookstoreTestBase
{
    public function testStaticAttributes()
    {
        $this->assertEquals(Table3Peer::$customStaticAttribute, 1, 'staticAttributes hook is called when adding attributes');
        $this->assertEquals(
            'Propel\Generator\Builder\Om\PeerBuilder',
            Table3Peer::$staticAttributeBuilder,
            'staticAttributes hook is called with the peer builder as parameter'
        );
    }

    public function testStaticMethods()
    {
        $this->assertTrue(
            method_exists('\Propel\Tests\Bookstore\Behavior\Table3Peer', 'hello'),
            'staticMethods hook is called when adding methods'
        );
        $this->assertEquals(
            'Propel\Generator\Builder\Om\PeerBuilder',
            Table3Peer::hello(),
            'staticMethods hook is called with the peer builder as parameter'
        );
    }

    public function testPreSelect()
    {
        $con = Propel::getServiceContainer()->getReadConnection(Table3TableMap::DATABASE_NAME);
        $con->preSelect = 0;
        Table3Peer::doSelect(new Criteria, $con);
        $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doSelect()');
        $con->preSelect = 0;
        Table3Peer::doSelectOne(new Criteria, $con);
        $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doSelectOne()');
        $con->preSelect = 0;
        Table3Peer::doSelectStmt(new Criteria, $con);
        $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doSelectStmt()');
        // and for the doSelectJoin and doCountJoin methods, well just believe my word

        $con->preSelect = 0;
        Table3Peer::doSelect(new Criteria, $con);
        $this->assertEquals(
            'Propel\Generator\Builder\Om\PeerBuilder',
            $con->preSelect,
            'preSelect hook is called with the peer builder as parameter'
        );
    }

    public function testPeerFilter()
    {
        Table3TableMap::TABLE_NAME;
        $this->assertTrue(class_exists('Propel\Tests\Bookstore\Behavior\Base\testPeerFilter'),
            'peerFilter hook allows complete manipulation of the generated script'
        );
        $this->assertEquals('Propel\Generator\Builder\Om\PeerBuilder', \Propel\Tests\Bookstore\Behavior\Base\testPeerFilter::FOO,
            'peerFilter hook is called with the peer builder as parameter'
        );
    }
}
