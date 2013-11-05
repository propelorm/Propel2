<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sluggable;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Unique;

/**
 * Adds a slug column
 *
 * @author Francois Zaninotto
 * @author Massimiliano Arione
 */
class SluggableBehavior extends Behavior
{
    private $builder;
    protected $parameters = [
        'slug_column'     => 'slug',
        'slug_pattern'    => '',
        'replace_pattern' => '/\W+/',
        'replacement'     => '-',
        'separator'       => '-',
        'permanent'       => 'false',
        'scope_column'    => '',
    ];

    /**
     * Adds the slug_column to the current table.
     *
     */
    public function modifyTable()
    {
        $table = $this->getTable();

        if (!$table->hasColumn($this->getParameter('slug_column'))) {
            $table->addColumn(array(
                'name'     => $this->getParameter('slug_column'),
                'type'     => 'VARCHAR',
                'size'     => 255,
                'required' => false,
            ));
            // add a unique to column
            $unique = new Unique($this->getColumnForParameter('slug_column'));
            $unique->setName($table->getCommonName() . '_slug');
            $unique->addColumn($table->getColumn($this->getParameter('slug_column')));
            if ($this->getParameter('scope_column')) {
                $unique->addColumn($table->getColumn($this->getParameter('scope_column')));
            }
            $table->addUnique($unique);
        }
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @return string The related getter, e.g. 'getSlug'
     */
    public function getColumnGetter()
    {
        return 'get' . $this->getColumnForParameter('slug_column')->getPhpName();
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @return string The related setter, e.g. 'setSlug'
     */
    public function getColumnSetter()
    {
        return 'set' . $this->getColumnForParameter('slug_column')->getPhpName();
    }

    public function queryMethods($builder)
    {
        $this->builder = $builder;
        $script = '';
        if ('slug' !== $this->getParameter('slug_column')) {
            $this->addFilterBySlug($script);
        }
        $this->addFindOneBySlug($script);

        return $script;
    }

    protected function addFilterBySlug(&$script)
    {
        $script .= "
/**
 * Filter the query on the slug column
 *
 * @param     string \$slug The value to use as filter.
 *
 * @return    " . $this->builder->getQueryClassName() . " The current query, for fluid interface
 */
public function filterBySlug(\$slug)
{
    return \$this->addUsingAlias(" . $this->builder->getColumnConstant($this->getColumnForParameter('slug_column')) . ", \$slug, Criteria::EQUAL);
}
";
    }

    protected function addFindOneBySlug(&$script)
    {
        $script .= "
/**
 * Find one object based on its slug
 *
 * @param     string \$slug The value to use as filter.
 * @param     ConnectionInterface \$con The optional connection object
 *
 * @return    " . $this->builder->getObjectClassName() . " the result, formatted by the current formatter
 */
public function findOneBySlug(\$slug, \$con = null)
{
    return \$this->filterBySlug(\$slug)->findOne(\$con);
}
";
    }
}
