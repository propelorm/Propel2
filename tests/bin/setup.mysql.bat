@echo off

setlocal

set DIR=%~dp0
call %DIR%\base.bat :init

set mysql=
@for %%e in (%PATHEXT%) do @for %%i in (mysql%%e) do @if NOT "%%~$PATH:i"=="" set mysql=%%~$PATH:i
if "%mysql%" == "" (
    @for %%e in (%PATHEXT%) do @for %%i in (mysql5%%e) do @if NOT "%%~$PATH:i"=="" set mysql=%%~$PATH:i
)       

if "%mysql%" == "" (
    echo Cannot find mysql binary. Is it installed?
    exit /B 1
)

if "%DB_USER%" == "" (
    echo DB_USER not set. Using 'root'.
    set DB_USER=root
)

set pw_option= 
if not "%DB_PW" == "" (
    set pw_option= -p"%DB_PW%"
)

if "%DB_HOSTNAME%" == "" (
    set DB_HOSTNAME=127.0.0.1
)

"%mysql%" --host="%DB_HOSTNAME%" -u"%DB_USER%" %pw_option% -e ^"^
SET FOREIGN_KEY_CHECKS = 0;^
DROP DATABASE IF EXISTS test;^
DROP SCHEMA IF EXISTS second_hand_books;^
DROP SCHEMA IF EXISTS contest;^
DROP SCHEMA IF EXISTS bookstore_schemas;^
DROP SCHEMA IF EXISTS migration;^
SET FOREIGN_KEY_CHECKS = 1;^
"
call %DIR%\base.bat :check
if "%errorlevel%" == "1" exit /B 1

"%mysql%" --host="%DB_HOSTNAME%" -u"%DB_USER%" %pw_option% -e ^"^
SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));^
CREATE DATABASE test;^
CREATE SCHEMA bookstore_schemas;^
CREATE SCHEMA contest;^
CREATE SCHEMA second_hand_books;^
CREATE SCHEMA migration;^
"
call %DIR%\base.bat :check
if "%errorlevel%" == "1" exit /B 1
