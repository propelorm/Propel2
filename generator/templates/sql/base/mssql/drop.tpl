<?php foreach ($table->getForeignKeys() as $fk) { ?>
IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='<?php echo $fk->getName() ?>')
    ALTER TABLE <?php echo $table->getName() ?> DROP CONSTRAINT <?php echo $fk->getName()?>;
<?php } ?>
<?php 
	// this file is being included within another foreach() loop.
	// we want to create a global var that is aware of what instance
	// this is within that loop;
	global $__mssql_drop_count;

	if (!isset($__mssql_drop_count)) {
	    $__mssql_drop_count = 0;
	}
	
	$__mssql_drop_count++;	
?>
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = '<?php echo $table->getName() ?>')
BEGIN
     DECLARE @reftable_<?php echo $__mssql_drop_count ?> nvarchar(60), @constraintname_<?php echo $__mssql_drop_count ?> nvarchar(60)
     DECLARE refcursor CURSOR FOR
     select reftables.name tablename, cons.name constraintname
      from sysobjects tables,
           sysobjects reftables,
           sysobjects cons,
           sysreferences ref
       where tables.id = ref.rkeyid
         and cons.id = ref.constid
         and reftables.id = ref.fkeyid
         and tables.name = '<?php echo $table->getName() ?>'
     OPEN refcursor
     FETCH NEXT from refcursor into @reftable_<?php echo $__mssql_drop_count ?>, @constraintname_<?php echo $__mssql_drop_count ?>
     while @@FETCH_STATUS = 0
     BEGIN
       exec ('alter table '+@reftable_<?php echo $__mssql_drop_count ?>+' drop constraint '+@constraintname_<?php echo $__mssql_drop_count ?>)
       FETCH NEXT from refcursor into @reftable_<?php echo $__mssql_drop_count ?>, @constraintname_<?php echo $__mssql_drop_count ?>
     END
     CLOSE refcursor
     DEALLOCATE refcursor
     DROP TABLE <?php echo $table->getName() ?>
	 
END

