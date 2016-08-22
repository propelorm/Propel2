@echo off

setlocal

set DIR=%~dp0
call %DIR%\base.bat :init

if exist .\test.sq3 (
    del /F .\test.sq3
)

if exist "%dir%..\test.sq3" (
    del /F "%dir%..\test.sq3"
)

