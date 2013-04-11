rm -rf build;

php54 ../../../bin/propel config:convert-xml --verbose=1;
php54 ../../../bin/propel model:build --verbose=1;