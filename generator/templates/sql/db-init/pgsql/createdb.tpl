<?php foreach( $databaseNames as $databaseName) { ?>
drop database <?php echo $databaseName ?>;
create database <?php echo $databaseName ?>;
<?php } /* foreach */ ?>
