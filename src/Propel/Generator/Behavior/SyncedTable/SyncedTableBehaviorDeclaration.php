<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\SyncedTable;

use Propel\Generator\Behavior\SyncedTable\TableSyncer\TableSyncerConfigInterface;
use Propel\Generator\Behavior\Util\BehaviorWithParameterAccess;

/**
 * Declares parameter keys and parameter value accessors.
 */
abstract class SyncedTableBehaviorDeclaration extends BehaviorWithParameterAccess implements TableSyncerConfigInterface
{
    /**
     * If no table name is supplied for the synced table, the source table name
     * will be used ammended by this suffix.
     *
     * @var string DEFAULT_SYNCED_TABLE_SUFFIX
     */
    protected const DEFAULT_SYNCED_TABLE_SUFFIX = '_synced';

    /**
     * @var string
     */
    public const PARAMETER_KEY_SYNCED_TABLE = 'table_name';

    /**
     * @deprecated Needed for BC - prefer table_attributes
     *
     * @var string
     */
    public const PARAMETER_KEY_SYNCED_PHPNAME = 'synced_phpname';

    /**
     * @var string
     */
    public const PARAMETER_KEY_TABLE_ATTRIBUTES = 'table_attributes';

    /**
     * @var string The name of the added pk column ('id' if set to 'true').
     */
    public const PARAMETER_KEY_ADD_PK = 'add_pk';

    /**
     * @var string
     */
    public const PARAMETER_KEY_COLUMNS = 'columns';

    /**
     * @var string
     */
    public const PARAMETER_KEY_FOREIGN_KEYS = 'foreign_keys';

    /**
     * @var string
     */
    public const PARAMETER_KEY_INHERIT_FROM_TABLE = 'inherit_from';

    /**
     * @var string
     */
    public const PARAMETER_KEY_SYNC = 'sync';

    /**
     * Add a prefix to synced column names. Uses table name if "true".
     *
     * @var string
     */
    public const PARAMETER_KEY_COLUMN_PREFIX = 'column_prefix';

    /**
     * Defines relation between source and synced table.
     *
     * Possible values:
     * - null|false (default): Do not create relation.
     * - true: Create both relation on model and fk constraint.
     * - 'skipSql': Create relation on the model, but no fk constraints on DB level.
     * - parameter-list: Create constraints from list with foreign key options.
     *
     * Requires PK of source table on synced table.
     *
     * @var string
     */
    public const PARAMETER_KEY_RELATION = 'relation';

    /**
     * @var string
     */
    public const PARAMETER_KEY_INHERIT_FOREIGN_KEY_RELATIONS = 'inherit_foreign_key_relations';

    /**
     * @var string
     */
    public const PARAMETER_KEY_INHERIT_FOREIGN_KEY_CONSTRAINTS = 'inherit_foreign_key_constraints';

    /**
     * @var string
     */
    public const PARAMETER_KEY_SYNC_INDEXES = 'sync_indexes';

    /**
     * Parameter can be set to 'index' or 'unique'.
     *
     * @var string
     */
    public const PARAMETER_KEY_SYNC_UNIQUE_AS = 'sync_unique_as';

    /**
     * List of column names (csv) that should not be synced.
     *
     * @var string
     */
    public const PARAMETER_KEY_IGNORE_COLUMNS = 'ignore_columns';

    /**
     * Either list of column names (csv) or 'true' to use ignored columns.
     *
     * @var string
     */
    public const PARAMETER_KEY_EMPTY_ACCESSOR_COLUMNS = 'empty_accessor_columns';

    /**
     * Ignore all columns expect PK.
     *
     * @var string
     */
    public const PARAMETER_KEY_SYNC_PK_ONLY = 'sync_pk_only';

    /**
     * What to do when parent table has skipSql="true".
     *
     * Options are:
     * - ignore: synced table is a regular DB table
     * - inherit: synced table also has skipSql="true"
     * - omit (default): do not build the synced table
     *
     * @var string
     */
    public const PARAMETER_KEY_ON_SKIP_SQL = 'on_skip_sql';

    /**
     * @return string
     */
    public function getDefaultSyncedTableSuffix(): string
    {
        return static::DEFAULT_SYNCED_TABLE_SUFFIX;
    }

    /**
     * @return array
     */
    protected function getDefaultParameters(): array
    {
        return [
            static::PARAMETER_KEY_SYNCED_TABLE => '',
            static::PARAMETER_KEY_SYNCED_PHPNAME => null,
            static::PARAMETER_KEY_ADD_PK => null,
            static::PARAMETER_KEY_SYNC => 'true',
            static::PARAMETER_KEY_FOREIGN_KEYS => null,
            static::PARAMETER_KEY_INHERIT_FOREIGN_KEY_RELATIONS => 'false',
            static::PARAMETER_KEY_INHERIT_FOREIGN_KEY_CONSTRAINTS => 'false',
            static::PARAMETER_KEY_SYNC_INDEXES => 'false',
            static::PARAMETER_KEY_SYNC_UNIQUE_AS => null,
            static::PARAMETER_KEY_RELATION => null,
            static::PARAMETER_KEY_IGNORE_COLUMNS => null,
            static::PARAMETER_KEY_EMPTY_ACCESSOR_COLUMNS => null,
            static::PARAMETER_KEY_SYNC_PK_ONLY => 'false',
        ];
    }

    /**
     * @return string|null
     */
    public function getSyncedTableName(): ?string
    {
        return $this->getParameter(static::PARAMETER_KEY_SYNCED_TABLE);
    }

    /**
     * @return array
     */
    public function getTableAttributes(): array
    {
        $val = $this->parameters[static::PARAMETER_KEY_TABLE_ATTRIBUTES] ?? null;

        return $val ? reset($val) : [];
    }

    /**
     * @return string|null
     */
    public function getSyncedTablePhpName(): ?string
    {
        return $this->getParameter(static::PARAMETER_KEY_SYNCED_PHPNAME);
    }

    /**
     * @return string|null
     */
    public function addPkAs(): ?string
    {
        $val = $this->getParameterTrueOrValue(static::PARAMETER_KEY_ADD_PK, false);

        return $val === true ? 'id' : $val;
    }

    /**
     * @return array|null
     */
    public function getColmns(): ?array
    {
        return $this->parameters[static::PARAMETER_KEY_COLUMNS] ?? [];
    }

    /**
     * @return array
     */
    public function getForeignKeys(): array
    {
        return $this->parameters[static::PARAMETER_KEY_FOREIGN_KEYS] ?? [];
    }

    /**
     * @return bool
     */
    public function isSync(): bool
    {
        return $this->getParameterBool(static::PARAMETER_KEY_SYNC, false);
    }

    /**
     * @return string|bool
     */
    public function useColumnPrefix()
    {
        return $this->getParameterTrueOrValue(static::PARAMETER_KEY_COLUMN_PREFIX, false);
    }

    /**
     * @return bool
     */
    public function isSyncIndexes(): bool
    {
        return $this->getParameterBool(static::PARAMETER_KEY_SYNC_INDEXES, false);
    }

    /**
     * @return string|null
     */
    public function getSyncUniqueIndexAs(): ?string
    {
        return $this->getParameter(static::PARAMETER_KEY_SYNC_UNIQUE_AS);
    }

    /**
     * @return array|null
     */
    public function getRelationAttributes(): ?array
    {
        /** @var array<array>|string|null $val */
        $val = $this->parameters[static::PARAMETER_KEY_RELATION] ?? null;
        if (is_array($val)) {
            return $this->unwrapParameterList($val);
        }
        if (is_string($val)) {
            $val = strtolower($val);
        }
        if (in_array($val, [null, false, 'false', 0, '0'], true)) {
            return null;
        }
        $attributes = [];
        if ($val === 'skipsql') {
            $attributes['skipSql'] = 'true';
        }

        return $attributes;
    }

    /**
     * @return bool
     */
    public function isInheritForeignKeyRelations(): bool
    {
        return $this->getParameterBool(static::PARAMETER_KEY_INHERIT_FOREIGN_KEY_RELATIONS, false);
    }

    /**
     * @return bool
     */
    public function isInheritForeignKeyConstraints(): bool
    {
        return $this->getParameterBool(static::PARAMETER_KEY_INHERIT_FOREIGN_KEY_CONSTRAINTS, false);
    }

    /**
     * @return array
     */
    public function getIgnoredColumnNames(): array
    {
        return $this->getParameterCsv(static::PARAMETER_KEY_IGNORE_COLUMNS, []);
    }

    /**
     * @return array|null
     */
    public function getEmptyAccessorColumnNames(): ?array
    {
        $val = $this->getParameterTrueOrCsv(static::PARAMETER_KEY_EMPTY_ACCESSOR_COLUMNS);

        return ($val === true) ? $this->getIgnoredColumnNames() : $val;
    }

    /**
     * @return bool
     */
    public function isSyncPkOnly(): bool
    {
        return $this->getParameterBool(static::PARAMETER_KEY_SYNC_PK_ONLY, false);
    }

    /**
     * @return string
     */
    public function onSkipSql(): string
    {
        $val = strtolower($this->getParameter(static::PARAMETER_KEY_ON_SKIP_SQL, 'omit'));

        return in_array($val, ['ignore', 'inherit', 'omit']) ? $val : 'omit';
    }

    /**
     * @return bool
     */
    public function inheritSkipSql(): bool
    {
        return $this->onSkipSql() === 'inherit';
    }

    /**
     * @return bool
     */
    public function omitOnSkipSql(): bool
    {
        return $this->onSkipSql() === 'omit';
    }

    /**
     * @throws \Propel\Generator\Behavior\SyncedTable\SyncedTableException
     *
     * @return array|null
     */
    public function getTableInheritance()
    {
        $val = $this->parameters[static::PARAMETER_KEY_INHERIT_FROM_TABLE] ?? null;
        if ($val === null) {
            return null;
        }
        if (is_string($val)) {
            return ['source_table' => $val];
        }

        $val = $this->unwrapParameterList($val);
        if (empty($val['source_table'])) {
            $format = 'Array input to parameter "%s" requires a table name in <parameter name="%s" value="..."/>';
            $msg = sprintf($format, static::PARAMETER_KEY_INHERIT_FROM_TABLE, 'source_table');

            throw new SyncedTableException($this, $msg);
        }

        return $val;
    }
}
