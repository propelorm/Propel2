@echo off

setlocal

set DIR=%~dp0
call %DIR%\base.bat :init

set psql=
@for %%e in (%PATHEXT%) do @for %%i in (psql%%e) do @if NOT "%%~$PATH:i"=="" set psql=%%~$PATH:i

if "%psql%" == "" (
    echo Cannot find psql binary. Is it installed?
    exit /B 1
)

if "%DB_USER%" == "" (
    echo DB_USER not set. Using 'postgres'.
    set DB_USER=postgres
)

if "%DB_NAME%" == "" (
    echo DB_NAME not set. Using 'postgres'.
    set DB_NAME=postgres
)

if "%DB_HOSTNAME%" == "" (
    set DB_HOSTNAME=127.0.0.1
)

"%psql%" --version

dropdb --host="%DB_HOSTNAME%" --username="%DB_USER%" "%DB_NAME%"

createdb --host="%DB_HOSTNAME%" --username="%DB_USER%" "%DB_NAME%"

call %DIR%\base.bat :check
if "%errorlevel%" == "1" exit /B 1

"%psql%" --host="%DB_HOSTNAME%" --username="%DB_USER%" -c ^"^
CREATE SCHEMA bookstore_schemas;^
CREATE SCHEMA contest;^
CREATE SCHEMA second_hand_books;^
CREATE SCHEMA migration;^
" "%DB_NAME%"

call %DIR%\base.bat :check
if "%errorlevel%" == "1" exit /B 1
