@echo off
title SITANI - STOP ALL SERVICES

echo ==========================================
echo MENGHENTIKAN SEMUA SERVICE SITANI
echo ==========================================
echo.

call :KillPort 8000 "GIS SERVICE"
call :KillPort 8001 "AUTH SERVICE"
call :KillPort 8002 "USER SERVICE"
call :KillPort 8003 "API GATEWAY"
call :KillPort 8004 "MASTER SERVICE"
call :KillPort 8005 "FARMING SERVICE"
call :KillPort 8006 "REPORTING SERVICE"
call :KillPort 8080 "WEB APP"

echo.
echo ==========================================
echo SEMUA SERVICE TELAH DIHENTIKAN
echo ==========================================
pause
exit

:KillPort
set PORT=%~1
set NAME=%~2

echo Mengecek %NAME% (Port %PORT%) ...

for /f "tokens=5" %%a in ('netstat -ano ^| findstr :%PORT% ^| findstr LISTENING') do (
    echo Menghentikan PID %%a ...
    taskkill /PID %%a /F >nul 2>&1
)

echo %NAME% selesai.
echo.
goto :eof