<?php
    // SQLite is typeless, so we'll treat everything like string
    print "'" . sqlite_escape_string($column->getValue()) . "'";
?>