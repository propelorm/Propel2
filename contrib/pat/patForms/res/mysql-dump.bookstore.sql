
# This first example is tested with a Bookstore project on MySql
# (default setting Sqlite has not been tested)
#
# Additionally, you'll need some data in your tables. In case you 
# don't have - here's a mini-dump to get the example running.

INSERT INTO `author` VALUES (1, 'Martin', 'Heidegger');
INSERT INTO `book` VALUES (1, 'Sein und Zeit', '3484701226', NULL, NULL);
INSERT INTO `publisher` VALUES (1, 'Max Niemeyer Verlag');