<?php

namespace Propel\Runtime;

class Events
{
    const PRE_COMMIT = 'propel.pre_commit';
    const COMMIT = 'propel.commit';

    const PRE_PERSIST = 'propel.pre_persist';
    const PERSIST = 'propel.persist';

    CONST PRE_SAVE = 'propel.pre_save';
    CONST SAVE = 'propel.save';

    CONST PRE_UPDATE = 'propel.pre_update';
    CONST UPDATE = 'propel.update';

    CONST PRE_INSERT = 'propel.pre_insert';
    CONST INSERT = 'propel.insert';

    CONST PRE_DELETE = 'propel.pre_delete';
    CONST DELETE = 'propel.delete';
}