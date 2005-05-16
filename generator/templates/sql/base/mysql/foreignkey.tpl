<?php 

	$_tables = $table->getDatabase()->getTables();
	foreach($_tables as $_table)
	{
		foreach($_table->getForeignKeys() as $_foreignKey)
		{
			if($_foreignKey->getForeignTableName() == $table->getName())
			{
				foreach($_foreignKey->getForeignColumns() as $_foreignColumn)
				{
					if(!$table->getDatabase()->getTable($_foreignKey->getForeignTableName())->getColumn($_foreignColumn)->isPrimaryKey())
					{
						?>    INDEX `I_<?php echo $_foreignKey->getName(); ?>_to_<?php echo $_foreignColumn; ?>` (`<?php echo $_foreignColumn; ?>`),
<?php
					}
				}
			}
		}
	}

      $counter = 0;
      foreach ($table->getForeignKeys() as $fk) {
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
?>
    INDEX <?php echo $indexName; ?> (<?php echo implode(',', $lnames); ?>),
    CONSTRAINT <?php echo $constraintName ?> FOREIGN KEY (<?php echo implode(',', $lnames); ?>) REFERENCES <?php echo "`" . $fk->getForeignTableName() . "`" ?> (<?php echo implode(',', $fnames); ?>),
<?php } ?>
