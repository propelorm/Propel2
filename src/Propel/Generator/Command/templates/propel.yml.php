propel:
    database:
        connections:
            default:
                adapter: <?php echo $rdbms . PHP_EOL ?>
                dsn: <?php echo $dsn . PHP_EOL ?>
                user: <?php echo $user . PHP_EOL ?>
                password: <?php echo $password  . PHP_EOL ?>
                settings:
                    charset: <?php echo $charset . PHP_EOL ?>
