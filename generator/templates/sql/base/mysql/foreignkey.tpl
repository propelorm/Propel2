<?php 

	$_indices = array();
	$_previousColumns = array();
	foreach($table->getPrimaryKey() as $_primaryKeyColumn)
	{
		$_previousColumns[] = $_primaryKeyColumn->getName();
		$_indices[] = implode(',', $_previousColumns);
	}
	$_tableIndices = array_merge($table->getIndices(), $table->getUnices());
	foreach($_tableIndices as $_index)
	{
		$_previousColumns = array();
		$_indexColumns = $_index->getColumns();
		foreach($_indexColumns as $_indexColumn)
		{
			$_previousColumns[] = $_indexColumn;
			$_indices[] = implode(',', $_previousColumns);
		}
	}

	$_tables = $table->getDatabase()->getTables();
	$counter = 0;
	foreach($_tables as $_table)
	{
		foreach($_table->getForeignKeys() as $_foreignKey)
		{
			if($_foreignKey->getForeignTableName() == $table->getName())
			{
				if(!in_array(implode(',',$_foreignKey->getForeignColumns()), $_indices))
				{
					$_foreignColumns = array();
					foreach($_foreignKey->getForeignColumns() as $_foreignColumn)
					{
						$_foreignColumns[] = "`" . $_foreignColumn . "`";
					}
					if($counter > 1): ?>,<?php endif; 
					$counter++;
					?> 
    INDEX `I_referenced_<?php echo $_foreignKey->getName(); ?>_<?php echo $counter; ?>` (<?php echo implode(',',$_foreignColumns); ?>)<?php
				}
			}
		}
	}

      $counter = 0;
      foreach ($table->getForeignKeys() as $fk) {
        if($counter > 0): ?>,<?php endif;
        $counter++;
        $fnames = array();
        foreach ($fk->getForeignColumns() as $column) {
            $fnames[] = "`" . $column . "`";
        }

        $lnames = array();
        foreach ($fk->getLocalColumns() as $column) {
            $lnames[] = "`" . $column . "`";
        }

        $constraintName = "`" . $table->getName() . "_ibfk_{$counter}`";
				$indexName = "`" . $table->getName() . "_ibfk_{$counter}_I`";
				if(!in_array(implode(',', $fk->getLocalColumns()), $_indices))
				{
?> 
    INDEX <?php echo $indexName; ?> (<?php echo implode(',', $lnames); ?>),<?php
				}
?> 
    CONSTRAINT <?php echo $constraintName ?> FOREIGN KEY (<?php echo implode(',', $lnames); ?>) REFERENCES <?php echo "`" . $fk->getForeignTableName() . "`" ?> (<?php echo implode(',', $fnames); ?>)<?php } 