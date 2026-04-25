@echo off
title Live Production - Auto Sync
echo ====================================================
echo   AUTO-SYNC DATABASE ACCESS KE MYSQL
echo   Berjalan otomatis setiap 5 menit. Jangan ditutup!
echo ====================================================
echo.

:: Path ke PHP instalasi Laragon
set PHP_BIN=c:\laragon\bin\php\php-8.4.18-Win32-vs17-x64\php.exe

:: Masuk ke folder project agar file bat ini bisa dipindah ke Desktop
cd /d c:\laragon\www\liveproduction

:LOOP
echo [%date% %time%] Mengambil data terbaru dari MS Access...
%PHP_BIN% artisan sync:access-data
%PHP_BIN% artisan sync:loading-data

echo.
echo Sinkronisasi berhasil, Menunggu 5 menit lagi...
timeout /t 300 /nobreak > NUL
echo.
goto LOOP
