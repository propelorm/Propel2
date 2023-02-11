<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
<!--
    When you're part of a team, you could want to define a common configuration file and commit it into your VCS. But, of
    course, there can be some properties you don't want to share, e.g. database passwords. Propel helps you and looks for
    a propel.yml.dist file too, merging its properties with propel.yml ones. So you can define shared configuration
    properties in propel.yml.dist, committing it in your VCS, and keep propel.yml as private. The properties loaded from
    propel.yml overwrite the ones with the same name, loaded from propel.yml.dist.

    For a complete references see: http://propelorm.org/documentation/reference/configuration-file.html
-->
<config>
    <propel>
        <paths>
            <!-- The directory where Propel expects to find your `schema.xml` file. -->
            <schemaDir><?php echo $schemaDir ?></schemaDir>
            <!-- The directory where Propel should output generated object model classes. -->
            <phpDir><?php echo $phpDir ?></phpDir>
        </paths>

        <!--
        <database>
            <connections>
                <connection id="default">
                    <adapter><?php echo $rdbms ?></adapter>
                    <dsn><?php echo $dsn ?></dsn>
                    <user><?php echo $user ?></user>
                    <password></password>
                    <settings>
                        <charset><?php echo $charset ?></charset>
                    </settings>
                </connection>
            </connections>
        </database>
        -->
    </propel>
</config>
