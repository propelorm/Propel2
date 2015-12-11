@echo off

call %*
exit /b

:check

if not "%errorlevel%" == "0" (
    echo Aborted.
    exit /B 1
)

goto :eof


:init

if "%PHPUNIT_COVERAGE%"=="1" (
    set PHPUNIT=.\vendor\bin\phpunit.bat --coverage-php=tests\clover.cov
) else (
    set PHPUNIT=.\vendor\bin\phpunit.bat
)

goto :eof
