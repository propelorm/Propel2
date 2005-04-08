<?php foreach( $databaseNames as $databaseName) { ?>
drop database if exists <?php echo $databaseName ?>;
create database <?php echo $databaseName ?>;
<?php } /* foreach */ ?>
