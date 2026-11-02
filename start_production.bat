@echo off
title Live Production Launcher
cd /d "C:\laragon\www\liveproduction"

echo =======================================================
echo     LIVE PRODUCTION MONITORING SYSTEM - AUTO START
echo =======================================================
echo.

:: 1. Sinkronisasi awal Access -> MySQL
echo [1/4] Menjalankan sinkronisasi awal data Access...
php artisan sync:access-data
php artisan sync:loading-data
echo.

:: 2. Menjalankan Laravel Web Server (Port 8000)
echo [2/4] Menyalakan Web Server (Port 8000)...
start "LiveProduction - Web Server" /min cmd /k "cd /d C:\laragon\www\liveproduction && php artisan serve --host=0.0.0.0 --port=8000"

:: 3. Menjalankan Scheduler Auto Sync (Setiap 5 menit)
echo [3/4] Menyalakan Scheduler Auto Sync (Setiap 5 menit)...
start "LiveProduction - Scheduler" /min cmd /k "cd /d C:\laragon\www\liveproduction && php artisan schedule:work"

:: 4. Menyalakan Localtunnel (bmi-production-monitoring)
echo [4/4] Menyalakan Localtunnel (bmi-production-monitoring)...
start "LiveProduction - Localtunnel" /min cmd /k "npx localtunnel --port 8000 --subdomain bmi-production-monitoring"

echo.
echo =======================================================
echo  Semua layanan berhasil dijalankan!
echo  - Web Lokal   : http://localhost:8000
echo  - Web Publik  : https://bmi-production-monitoring.loca.lt
echo =======================================================
echo.
timeout /t 5
