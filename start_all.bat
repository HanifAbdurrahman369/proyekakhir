@echo off
title SiTani - START ALL SERVICES

echo ==================================================
echo MENJALANKAN SEMUA SERVICE PROYEK AKHIR
echo ==================================================

start "GIS SERVICE - 8000" cmd /k "cd /d C:\laragon\www\proyekakhir\services\gis_service && php artisan serve --host=127.0.0.1 --port=8000"

timeout /t 2 >nul

start "AUTH SERVICE - 8001" cmd /k "cd /d C:\laragon\www\proyekakhir\services\auth_service && php artisan serve --host=127.0.0.1 --port=8001"

timeout /t 2 >nul

start "USER SERVICE - 8002" cmd /k "cd /d C:\laragon\www\proyekakhir\services\user_service && php artisan serve --host=127.0.0.1 --port=8002"

timeout /t 2 >nul

start "API GATEWAY - 8003" cmd /k "cd /d C:\laragon\www\proyekakhir\services\api_gateway && php artisan serve --host=0.0.0.0 --port=8003"

timeout /t 2 >nul

start "MASTER SERVICE - 8004" cmd /k "cd /d C:\laragon\www\proyekakhir\services\master_service && php artisan serve --host=127.0.0.1 --port=8004"

timeout /t 2 >nul

start "FARMING SERVICE - 8005" cmd /k "cd /d C:\laragon\www\proyekakhir\services\farming_service && php artisan serve --host=127.0.0.1 --port=8005"

timeout /t 2 >nul

start "REPORTING SERVICE - 8006" cmd /k "cd /d C:\laragon\www\proyekakhir\services\reporting_service && php artisan serve --host=127.0.0.1 --port=8006"

timeout /t 2 >nul

start "WEB APP - 8080" cmd /k "cd /d C:\laragon\www\proyekakhir\clients\web_app && php artisan serve --host=127.0.0.1 --port=8080"

echo.
echo ==================================================
echo SEMUA SERVICE SUDAH DIJALANKAN
echo ==================================================
echo.
echo GIS SERVICE       : http://127.0.0.1:8000
echo AUTH SERVICE      : http://127.0.0.1:8001
echo USER SERVICE      : http://127.0.0.1:8002
echo API GATEWAY       : http://127.0.0.1:8003
echo MASTER SERVICE    : http://127.0.0.1:8004
echo FARMING SERVICE   : http://127.0.0.1:8005
echo REPORTING SERVICE : http://127.0.0.1:8006
echo WEB APP           : http://127.0.0.1:8080
echo.