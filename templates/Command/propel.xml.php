<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
<config>
    <propel>
        <database>
            <connections>
                <connection id="default">
                    <adapter><?php echo $rdbms ?></adapter>
                    <dsn><?php echo $dsn ?></dsn>
                    <user><?php echo $user ?></user>
                    <password><?php echo $password ?></password>
                    <settings>
                        <charset><?php echo $charset ?></charset>
                    </settings>
                </connection>
            </connections>
        </database>
    </propel>
</config>
