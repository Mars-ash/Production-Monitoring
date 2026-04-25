@echo off
title Sinkronisasi Manual
echo Mengerjakan Sinkronisasi MS Access dengan PHP 64-Bit...
echo.

:: Masuk ke folder project agar file bat ini bisa dipindah ke Desktop
cd /d c:\laragon\www\liveproduction

set PHP_BIN=c:\laragon\bin\php\php-8.4.18-Win32-vs17-x64\php.exe


echo [1/2] Sinkronisasi Data Production...
%PHP_BIN% artisan sync:access-data
echo.

echo [2/2] Sinkronisasi Data Loading Machine...
%PHP_BIN% artisan sync:loading-data
echo.

echo Selesai!
pause
