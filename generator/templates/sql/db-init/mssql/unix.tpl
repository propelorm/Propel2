#!/bin/sh

#foreach ($databaseModel in $appData.Databases)
dropdb $databaseModel.Name
createdb $databaseModel.Name
#end
