@echo off
chcp 65001 >nul
echo ========================================
echo   STREAMING PLATFORM - INSTALLATION
echo ========================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [WARNING] Not running as Administrator
    echo Some operations may require admin privileges
    echo.
    timeout /t 2 /nobreak >nul
)

echo [1/4] Creating required directories...
if not exist "uploads" mkdir uploads
if not exist "videos" mkdir videos
if not exist "storage" mkdir storage

echo [2/4] Setting file permissions...
icacls "uploads" /grant "Everyone:(OI)(CI)F" /T >nul 2>&1
icacls "videos" /grant "Everyone:(OI)(CI)F" /T >nul 2>&1
icacls "storage" /grant "Everyone:(OI)(CI)F" /T >nul 2>&1

echo [3/4] Checking XAMPP services...
echo Please ensure XAMPP is running with Apache and MySQL...
echo.
timeout /t 3 /nobreak >nul

echo [4/4] Importing database...
echo This will create the streaming_db database with sample data...
echo.
mysql -u root -p < "sql\setup.sql"
if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Database import failed!
    echo Possible reasons:
    echo 1. MySQL not running in XAMPP
    echo 2. Wrong MySQL password
    echo 3. Database already exists
    echo.
    echo You can manually import sql\setup.sql in phpMyAdmin
    pause
    exit /b 1
)

echo.
echo ========================================
echo   ✅ INSTALLATION COMPLETE!
echo ========================================
echo.
echo 🎬 Streaming Platform is ready!
echo.
echo 📍 Access URLs:
echo - Home: http://localhost/streaming-platform/public/
echo - API Test: http://localhost/streaming-platform/test_api.php
echo - phpMyAdmin: http://localhost/phpmyadmin
echo.
echo 🔐 Test Credentials:
echo - Admin: admin@stream.com / admin123
echo - User:  user@stream.com / user123
echo.
echo 📚 API Documentation available on the homepage
echo.
echo Press any key to open in browser...
pause >nul
start http://localhost/streaming-platform/public/