<!ELEMENT <?php echo $table->getPhpName() ?> EMPTY>
<!ATTLIST <?php echo $table->getPhpName() ?>
<?php foreach ($table->getColumns() as $col) { ?> 
	<?php echo $col->getPhpName() ?> CDATA <?php if($col->isNotNull()) { ?>#REQUIRED<?php } else { ?>IMPLIED<?php } ?><?php } ?>
>

