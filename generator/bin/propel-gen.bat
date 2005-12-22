@echo off

rem *********************************************************************
rem ** The Propel generator convenience script for Windows based systems
rem ** $Id$
rem *********************************************************************

rem This script will do the following:
rem - check for PHING_COMMAND env, if found, use it.
rem   - if not found detect php, if found use it, otherwise err and terminate
rem - check for PROPEL_GEN_HOME evn, if found use it
rem   - if not found error and leave

if "%OS%"=="Windows_NT" @setlocal

rem %~dp0 is expanded pathname of the current script under NT
set DEFAULT_PROPEL_GEN_HOME=%~dp0..

goto init
goto cleanup

:init

if "%PROPEL_GEN_HOME%" == "" set PROPEL_GEN_HOME=%DEFAULT_PROPEL_GEN_HOME%
set DEFAULT_PROPEL_GEN_HOME=

if "%PHING_COMMAND%" == "" goto no_phingcommand

goto run
goto cleanup

:run
%PHING_COMMAND% -f %PROPEL_GEN_HOME%\build.xml -Dusing.propel-gen=true -Dproject.dir=%*
goto cleanup

:no_phingcommand
REM echo ------------------------------------------------------------------------
REM echo WARNING: Set environment var PHING_COMMAND to the location of your phing
REM echo          executable (e.g. C:\PHP\phing.bat). 
REM echo Proceeding with assumption that phing.bat is on Path
REM echo ------------------------------------------------------------------------
set PHING_COMMAND=phing.bat
goto init

:cleanup
if "%OS%"=="Windows_NT" @endlocal
REM pause
