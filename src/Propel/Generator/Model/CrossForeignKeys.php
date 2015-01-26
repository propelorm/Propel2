<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * A class for information about table cross foreign keys which are used in many-to-many relations.
 *
 *
 *    ___CrossTable1___                                   ___User___
 *   |  PK1 userId     |----------FK1------------------->|  id      |
 *   |                 |                    _Group__     |  name    |
 *   |  PK2 groupId    |-----+----FK2----->|  id    |    |__________|
 *   |                 |    /           \->|  id2   |
 *   |  PK3 relationId |   /               |  name  |
 *   |                 |  /                |________|
 *   |  PK4 groupId2   |-/
 *   |_________________|
 *
 *
 *    User->getCrossFks():
 *      0:
 *         getTable()                   -> User
 *         getCrossForeignKeys()        -> [FK2]
 *         getMiddleTable()             -> CrossTable1
 *         getIncomingForeignKey()      -> FK1
 *         getUnclassifiedPrimaryKeys() -> [PK3]
 *
 *    Group->getCrossFks():
 *      0:
 *         getTable()                   -> Group
 *         getCrossForeignKeys()        -> [FK1]
 *         getMiddleTable()             -> CrossTable1
 *         getIncomingForeignKey()      -> FK2
 *         getUnclassifiedPrimaryKeys() -> [PK3]
 */
class CrossForeignKeys
{

    /**
     * The middle-table.
     *
     * @var Table
     */
    protected $table;

    /**
     * The target table (which has crossRef=true).
     *
     * @var Table
     */
    protected $middleTable;

    /**
     * All other outgoing relations from the middle-table to other tables.
     *
     * @var ForeignKey[]
     */
    protected $crossForeignKeys = [];

    /**
     * The incoming foreign key from the middle-table to this table.
     *
     * @var ForeignKey
     */
    protected $incomingForeignKey;

    /**
     * @param ForeignKey $foreignKey
     * @param Table      $crossTable
     */
    public function __construct(ForeignKey $foreignKey, Table $crossTable)
    {
        $this->setIncomingForeignKey($foreignKey);
        $this->setTable($crossTable);
    }

    /**
     * @param ForeignKey $foreignKey
     */
    public function setIncomingForeignKey($foreignKey)
    {
        $this->setMiddleTable($foreignKey ? $foreignKey->getTable() : null);
        $this->incomingForeignKey = $foreignKey;
    }

    /**
     * The foreign key from the middle-table to the target table.
     *
     * @return ForeignKey
     */
    public function getIncomingForeignKey()
    {
        return $this->incomingForeignKey;
    }

    /**
     * Returns true if at least one of the local columns of $fk is not already covered by another
     * foreignKey in our collection (getCrossForeignKeys)
     *
     * E.g.
     *
     * table (local primary keys -> foreignKey):
     *
     *   pk1  -> FK1
     *   pk2
     *      \
     *        -> FK2
     *      /
     *   pk3  -> FK3
     *      \
     *        -> FK4
     *      /
     *   pk4
     *
     *  => FK1(pk1), FK2(pk2, pk3), FK3(pk3), FK4(pk3, pk4).
     *
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK1) where none fks in our collection: true
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK2) where FK1 is in our collection: true
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK3) where FK1,FK2 is in our collection: false
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK4) where FK1,FK2 is in our collection: true
     *
     * @param  ForeignKey $fk
     * @return bool
     */
    public function isAtLeastOneLocalPrimaryKeyNotCovered(ForeignKey $fk)
    {
        $primaryKeys = $fk->getLocalPrimaryKeys();
        foreach ($primaryKeys as $primaryKey) {
            $covered = false;
            foreach ($this->getCrossForeignKeys() as $crossFK) {
                if ($crossFK->hasLocalColumn($primaryKey)) {
                    $covered = true;
                    break;
                }
            }
            //at least one is not covered, so return true
            if (!$covered) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns all primary keys of middle-table which are not already covered by at least on of our cross foreignKey collection.
     *
     * @return Column[]
     */
    public function getUnclassifiedPrimaryKeys()
    {
        $pks = [];
        foreach ($this->getMiddleTable()->getPrimaryKey() as $pk) {
            //required
            $unclassified = true;
            if ($this->getIncomingForeignKey()->hasLocalColumn($pk)) {
                $unclassified = false;
            }
            if ($unclassified) {
                foreach ($this->getCrossForeignKeys() as $crossFK) {
                    if ($crossFK->hasLocalColumn($pk)) {
                        $unclassified = false;
                        break;
                    }
                }
            }
            if ($unclassified) {
                $pks[] = $pk;
            }
        }

        return $pks;
    }

    /**
     * @param ForeignKey $foreignKey
     */
    public function addCrossForeignKey(ForeignKey $foreignKey)
    {
        $this->crossForeignKeys[] = $foreignKey;
    }

    /**
     * @return bool
     */
    public function hasCrossForeignKeys()
    {
        return !!$this->crossForeignKeys;
    }

    /**
     * @param ForeignKey[] $foreignKeys
     */
    public function setCrossForeignKeys(array $foreignKeys)
    {
        $this->crossForeignKeys = $foreignKeys;
    }

    /**
     * All other outgoing relations from the middle-table to other tables.
     *
     * @return ForeignKey[]
     */
    public function getCrossForeignKeys()
    {
        return $this->crossForeignKeys;
    }

    /**
     * @param Table $foreignTable
     */
    public function setMiddleTable(Table $foreignTable)
    {
        $this->middleTable = $foreignTable;
    }

    /**
     * The middle table (which has crossRef=true).
     *
     * @return Table
     */
    public function getMiddleTable()
    {
        return $this->middleTable;
    }

    /**
     * @param Table $table
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * The source table.
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

}
