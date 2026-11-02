@echo off
title Stop Live Production
echo =======================================================
echo     MENGHENTIKAN LAYANAN LIVE PRODUCTION
echo =======================================================
echo.

taskkill /FI "WINDOWTITLE eq LiveProduction*" /F /T >nul 2>&1
echo Layanan Web Server, Scheduler, dan Tunnel telah dihentikan.
echo.
timeout /t 3
