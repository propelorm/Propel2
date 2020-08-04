<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\TestCase;

class DummyObject
{
    /**
     * @var mixed
     */
    private $id;

    /**
     * @param mixed $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function hashCode()
    {
        return (string)$this->id;
    }
}

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/1133.
 *
 * @group database
 */
class Issue1133Test extends TestCase
{
    /**
     * @return void
     */
    public function testIssue1133Append()
    {
        $testCollection = new ObjectCollection();
        $testCollection->setModel(DummyObject::class);

        for ($i = 0; $i < 3; $i++) {
            $testCollection->append(new DummyObject($i));
        }

        $firstToRemove = $testCollection[0];
        $objectThatShouldNotBeRemoved = $testCollection[2];

        // breaks index numbering
        $testCollection->removeObject($firstToRemove);
        $objectThatWillBeRemoved = new DummyObject(3);
        $testCollection->append($objectThatWillBeRemoved);
        $testCollection->removeObject($objectThatWillBeRemoved);

        $this->assertContains($objectThatShouldNotBeRemoved, $testCollection, 'ObjectCollection does not contain item that should be in collection.');
        $this->assertNotContains($objectThatWillBeRemoved, $testCollection, 'ObjectCollection contains item that should be removed.');
    }

    /**
     * @return void
     */
    public function testIssue1133OffsetSet()
    {
        $testCollection = new ObjectCollection();
        $testCollection->setModel(DummyObject::class);

        for ($i = 0; $i < 3; $i++) {
            $testCollection->append(new DummyObject($i));
        }

        $firstToRemove = $testCollection[0];
        $objectThatShouldNotBeRemoved = $testCollection[2];

        // breaks index numbering
        $testCollection->removeObject($firstToRemove);

        $objectThatWillBeRemoved = new DummyObject(3);
        // calls offsetSet
        $testCollection[] = $objectThatWillBeRemoved;
        $testCollection->removeObject($objectThatWillBeRemoved);

        $this->assertContains($objectThatShouldNotBeRemoved, $testCollection, 'ObjectCollection does not contain item that should be in collection.');
        $this->assertNotContains($objectThatWillBeRemoved, $testCollection, 'ObjectCollection contains item that should be removed.');
    }
}
