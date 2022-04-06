<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Sluggable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;

/**
 * Adds a slug column
 *
 * @author Francois Zaninotto
 * @author Massimiliano Arione
 */
class SluggableBehavior extends Behavior
{
    /**
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    private $builder;

    /**
     * @var array<string, mixed>
     */
    protected $parameters = [
        'slug_column' => 'slug',
        'slug_pattern' => '',
        'replace_pattern' => '/\W+/',
        'replacement' => '-',
        'separator' => '-',
        'permanent' => 'false',
        'scope_column' => '',
        'unique_constraint' => 'true',
    ];

    /**
     * Adds the slug_column to the current table.
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();

        if (!$table->hasColumn($this->getParameter('slug_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('slug_column'),
                'type' => 'VARCHAR',
                'size' => 255,
                'required' => false,
            ]);
            // add a unique to column
            if ($this->getParameter('unique_constraint') === 'true') {
                $this->addUniqueConstraint($table);
            }
        }
    }

    /**
     * Adds a unique constraint to the table to enforce uniqueness of the slug_column
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addUniqueConstraint(Table $table): void
    {
        $unique = new Unique();
        $unique->setName($table->getCommonName() . '_slug');
        $unique->addColumn($table->getColumn($this->getParameter('slug_column')));
        if ($this->getParameter('scope_column')) {
            $unique->addColumn($table->getColumn($this->getParameter('scope_column')));
        }
        $table->addUnique($unique);
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @return string The related getter, e.g. 'getSlug'
     */
    protected function getColumnGetter(): string
    {
        return 'get' . $this->getColumnForParameter('slug_column')->getPhpName();
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @return string The related setter, e.g. 'setSlug'
     */
    protected function getColumnSetter(): string
    {
        return 'set' . $this->getColumnForParameter('slug_column')->getPhpName();
    }

    /**
     * Add code in ObjectBuilder::preSave
     *
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string The code to put at the hook
     */
    public function preSave(AbstractOMBuilder $builder): string
    {
        $const = $builder->getColumnConstant($this->getColumnForParameter('slug_column'));
        $script = "
if (\$this->isColumnModified($const) && \$this->{$this->getColumnGetter()}()) {
    \$this->{$this->getColumnSetter()}(\$this->makeSlugUnique(\$this->{$this->getColumnGetter()}()));";
        if ($this->getParameter('permanent') === 'true') {
            $script .= "
} elseif (!\$this->{$this->getColumnGetter()}()) {
    \$this->{$this->getColumnSetter()}(\$this->createSlug());
}";
        } else {
            $script .= "
} else {
    \$this->{$this->getColumnSetter()}(\$this->createSlug());
}";
        }

        return $script;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectMethods(AbstractOMBuilder $builder): string
    {
        $this->builder = $builder;
        $script = '';
        if ($this->getParameter('slug_column') !== 'slug') {
            $this->addSlugSetter($script);
            $this->addSlugGetter($script);
        }
        $this->addCreateSlug($script);
        $this->addCreateRawSlug($script);
        $this->addCleanupSlugPart($script);
        $this->addLimitSlugSize($script);
        $this->addMakeSlugUnique($script);

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addSlugSetter(string &$script): void
    {
        $script .= "
/**
 * Wrap the setter for slug value
 *
 * @param string
 * @return \$this
 */
public function setSlug(\$v)
{
    \$this->" . $this->getColumnSetter() . "(\$v);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addSlugGetter(string &$script): void
    {
        $script .= "
/**
 * Wrap the getter for slug value
 *
 * @return string
 */
public function getSlug()
{
    return \$this->" . $this->getColumnGetter() . "();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCreateSlug(string &$script): void
    {
        $script .= "
/**
 * Create a unique slug based on the object
 *
 * @return string The object slug
 */
protected function createSlug(): string
{
    \$slug = \$this->createRawSlug();
    \$slug = \$this->limitSlugSize(\$slug);
    \$slug = \$this->makeSlugUnique(\$slug);

    return \$slug;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCreateRawSlug(string &$script): void
    {
        $pattern = $this->getParameter('slug_pattern');
        $script .= "
/**
 * Create the slug from the appropriate columns
 *
 * @return string
 */
protected function createRawSlug(): string
{
    ";
        if ($pattern) {
            $script .= "return '" . str_replace(['{', '}'], ['\' . $this->cleanupSlugPart((string)$this->get', '()) . \''], $pattern) . "';";
        } else {
            $script .= 'return $this->cleanupSlugPart($this->__toString());';
        }
        $script .= "
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function addCleanupSlugPart(string &$script): void
    {
        $script .= "
/**
 * Cleanup a string to make a slug of it
 * Removes special characters, replaces blanks with a separator, and trim it
 *
 * @param string \$slug        the text to slugify
 * @param string \$replacement the separator used by slug
 * @return string the slugified text
 */
protected static function cleanupSlugPart(string \$slug, string \$replacement = '" . $this->getParameter('replacement') . "'): string
{
    // set locale explicitly
    \$localeOrigin = setlocale(LC_CTYPE, 0);
    setlocale(LC_CTYPE, 'C.UTF-8');

    // transliterate
    if (function_exists('iconv')) {
        \$slug = iconv('utf-8', 'us-ascii//TRANSLIT', \$slug);
    }

    // lowercase
    if (function_exists('mb_strtolower')) {
        \$slug = mb_strtolower(\$slug);
    } else {
        \$slug = strtolower(\$slug);
    }

    // remove accents resulting from OSX's iconv
    \$slug = str_replace(array('\'', '`', '^'), '', \$slug);

    // replace non letter or digits with separator
    \$slug = preg_replace('" . $this->getParameter('replace_pattern') . "', \$replacement, \$slug);

    // trim
    \$slug = trim(\$slug, \$replacement);

    setlocale(LC_CTYPE, \$localeOrigin);

    if (empty(\$slug)) {
        return 'n-a';
    }

    return \$slug;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function addLimitSlugSize(string &$script): void
    {
        $size = $this->getColumnForParameter('slug_column')->getSize();
        $script .= "

/**
 * Make sure the slug is short enough to accommodate the column size
 *
 * @param string \$slug The slug to check
 * @param int \$incrementReservedSpace Space to reserve
 *
 * @return string The truncated slug
 */
protected static function limitSlugSize(string \$slug, int \$incrementReservedSpace = 3): string
{
    // check length, as suffix could put it over maximum
    if (strlen(\$slug) > ($size - \$incrementReservedSpace)) {
        \$slug = substr(\$slug, 0, $size - \$incrementReservedSpace);
    }

    return \$slug;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function addMakeSlugUnique(string &$script): void
    {
        $script .= "

/**
 * Get the slug, ensuring its uniqueness
 *
 * @param string \$slug            the slug to check
 * @param string \$separator       the separator used by slug
 * @param bool \$alreadyExists   false for the first try, true for the second, and take the high count + 1
 * @return string the unique slug
 */
protected function makeSlugUnique(string \$slug, string \$separator = '" . $this->getParameter('separator') . "', bool \$alreadyExists = false)
{";
        $getter = $this->getColumnGetter();
        $script .= "
    if (!\$alreadyExists) {
        \$slug2 = \$slug;
    } else {
        \$slug2 = \$slug . \$separator;";

        if ($this->getParameter('slug_pattern') == null) {
            $script .= "

        \$count = " . $this->builder->getStubQueryBuilder()->getClassname() . "::create()
            ->filterBySlug(\$this->$getter())
            ->filterByPrimaryKey(\$this->getPrimaryKey())
        ->count();

        if (1 == \$count) {
            return \$this->$getter();
        }";
        }

        $script .= "
    }

    \$adapter = \\Propel\\Runtime\\Propel::getServiceContainer()->getAdapter('" . $this->builder->getDatabase()->getName() . "');
    \$col = 'q." . $this->getColumnForParameter('slug_column')->getPhpName() . "';
    \$compare = \$alreadyExists ? \$adapter->compareRegex(\$col, '?') : sprintf('%s = ?', \$col);

    \$query = " . $this->builder->getStubQueryBuilder()->getClassname() . "::create('q')
        ->where(\$compare, \$alreadyExists ? '^' . \$slug2 . '[0-9]+$' : \$slug2)
        ->prune(\$this)";

        if ($this->getParameter('scope_column')) {
            $scopeColumn = $this->getColumnForParameter('scope_column')->getPhpName();
            $script .= "
            ->filterBy('{$scopeColumn}', \$this->get{$scopeColumn}())";
        }
        // watch out: some of the columns may be hidden by the soft_delete behavior
        if ($this->table->hasBehavior('soft_delete')) {
            $script .= "
        ->includeDeleted()";
        }
        $script .= "
    ;

    if (!\$alreadyExists) {
        \$count = \$query->count();
        if (\$count > 0) {
            return \$this->makeSlugUnique(\$slug, \$separator, true);
        }

        return \$slug2;
    }

    \$adapter = \\Propel\\Runtime\\Propel::getServiceContainer()->getAdapter('" . $this->builder->getDatabase()->getName() . "');
    // Already exists
    \$object = \$query
        ->addDescendingOrderByColumn(\$adapter->strLength('" . $this->getColumnForParameter('slug_column')->getName() . "'))
        ->addDescendingOrderByColumn('" . $this->getColumnForParameter('slug_column')->getName() . "')
    ->findOne();

    // First duplicate slug
    if (\$object === null) {
        return \$slug2 . '1';
    }

    \$slugNum = substr(\$object->" . $getter . "(), strlen(\$slug) + 1);
    if (\$slugNum[0] == 0) {
        \$slugNum[0] = 1;
    }

    return \$slug2 . (\$slugNum + 1);
}
";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryMethods(AbstractOMBuilder $builder): string
    {
        $this->builder = $builder;
        $script = '';

        if ($this->getParameter('slug_column') !== 'slug') {
            $this->addFilterBySlug($script);
            $this->addFindOneBySlug($script);
        }

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFilterBySlug(string &$script): void
    {
        $script .= "
/**
 * Filter the query on the slug column
 *
 * @param string \$slug The value to use as filter.
 *
 * @return \$this The current query, for fluid interface
 */
public function filterBySlug(string \$slug)
{
    \$this->addUsingAlias(" . $this->builder->getColumnConstant($this->getColumnForParameter('slug_column')) . ", \$slug, Criteria::EQUAL);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFindOneBySlug(string &$script): void
    {
        $script .= "
/**
 * Find one object based on its slug
 *
 * @param string \$slug The value to use as filter.
 * @param ConnectionInterface \$con The optional connection object
 *
 * @return " . $this->builder->getObjectClassName() . " the result, formatted by the current formatter
 */
public function findOneBySlug(string \$slug, ?ConnectionInterface \$con = null)
{
    return \$this->filterBySlug(\$slug)->findOne(\$con);
}
";
    }
}
