<?php 

	$_indices = array();
	$_previousColumns = array();
	// we're building an array of indices here which is smart about multi-column indices.
	// for example, if we have to indices foo(ColA) and bar(ColB, ColC), we have actually three indices already defined:
	// ColA, ColB+ColC, and ColB (but not ColC!). This is because of the way SQL multi-column indices work.
	// we will later match found, defined foreign key and referenced column definitions against this array to know whether we should create a new index for mysql or not
	foreach($table->getPrimaryKey() as $_primaryKeyColumn)
	{
		// do the above for primary keys
		$_previousColumns[] = "`" . $_primaryKeyColumn->getName() . "`";
		$_indices[] = implode(',', $_previousColumns);
	}
	$_tableIndices = array_merge($table->getIndices(), $table->getUnices());
	foreach($_tableIndices as $_index)
	{
		// same procedure, this time for unices and indices
		$_previousColumns = array();
		$_indexColumns = $_index->getColumns();
		foreach($_indexColumns as $_indexColumn)
		{
			$_previousColumns[] = "`" . $_indexColumn . "`";
			$_indices[] = implode(',', $_previousColumns);
		}
	}

	$_tables = $table->getDatabase()->getTables();
	$counter = 0;
	// we're determining which tables have foreign keys that point to this table, since MySQL needs an index on any column that is referenced by another table (yep, MySQL _is_ a PITA)
	foreach($_tables as $_table)
	{
		foreach($_table->getForeignKeys() as $_foreignKey)
		{
			if($_foreignKey->getForeignTableName() == $table->getName())
			{
				$_foreignColumns = array();
				foreach($_foreignKey->getForeignColumns() as $_foreignColumn)
				{
					$_foreignColumns[] = "`" . $_foreignColumn . "`";
				}
				if(!in_array(implode(',', $_foreignColumns), $_indices))
				{
					// no matching index defined in the schema, so we have to create one
					$counter++;
					if($counter > 1): ?>,<?php endif; 
					?> 
    INDEX `I_referenced_<?php echo $_foreignKey->getName(); ?>_<?php echo $counter; ?>` (<?php echo implode(',',$_foreignColumns); ?>)<?php
				}
			}
		}
	}

			$hasReferencedColumns = $counter > 0;
      $counter = 0;
      foreach ($table->getForeignKeys() as $fk) {
        if($counter > 0 || $hasReferencedColumns): ?>,<?php endif;
        $counter++;
        $fnames = array();
        foreach ($fk->getForeignColumns() as $column) {
            $fnames[] = "`" . $column . "`";
        }

        $lnames = array();
        foreach ($fk->getLocalColumns() as $column) {
            $lnames[] = "`" . $column . "`";
        }

        $constraintName = "`" . $fk->getName() . "`";
				$indexName = "`" . substr_replace($fk->getName(), 'FI_',  strrpos($fk->getName(), 'FK_'), 3) . "`";
				if(!in_array(implode(',', $lnames), $_indices))
				{
					// no matching index defined in the schema, so we have to create one. MySQL needs indices on any columns that serve as foreign keys. these are not auto-created prior to 4.1.2
?> 
    INDEX <?php echo $indexName; ?> (<?php echo implode(',', $lnames); ?>),<?php
				}
?> 
    CONSTRAINT <?php echo $constraintName ?> 
      FOREIGN KEY (<?php echo implode(',', $lnames); ?>)
      REFERENCES <?php echo "`" . $fk->getForeignTableName() . "`" ?> (<?php echo implode(',', $fnames); ?>)
<?php if ($fk->hasOnUpdate()) { ?>
      ON UPDATE <?php echo $fk->getOnUpdate(); ?> 
<?php } if ($fk->hasOnDelete()) { ?>
      ON DELETE <?php echo $fk->getOnDelete();
	}
}