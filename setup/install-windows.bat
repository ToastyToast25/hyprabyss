@echo off
setlocal enabledelayedexpansion

:: HyperAbyss ARK Cluster - Windows/Laragon Setup Script
:: Run as Administrator

title HyperAbyss Setup - Windows/Laragon

echo ========================================
echo  HyperAbyss ARK Cluster Setup
echo  Windows/Laragon Installation
echo ========================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Please run as Administrator
    pause
    exit /b 1
)

:: Set variables
set LARAGON_PATH=C:\laragon
set PROJECT_NAME=hyperabyss
set PROJECT_PATH=%LARAGON_PATH%\www\%PROJECT_NAME%
set PHP_PATH=%LARAGON_PATH%\bin\php\php-8.2.12-Win32-vs16-x64
set MYSQL_PATH=%LARAGON_PATH%\bin\mysql\mysql-8.0.30-winx64

echo Step 1: Checking Laragon installation...
if not exist "%LARAGON_PATH%" (
    echo ERROR: Laragon not found at %LARAGON_PATH%
    echo Please install Laragon first: https://laragon.org/download/
    pause
    exit /b 1
)
echo ✓ Laragon found

echo.
echo Step 2: Checking PHP 8.2+ availability...
if not exist "%PHP_PATH%" (
    echo ERROR: PHP 8.2+ not found
    echo Please install PHP 8.2+ through Laragon
    pause
    exit /b 1
)
echo ✓ PHP 8.2+ found

echo.
echo Step 3: Checking MySQL availability...
if not exist "%MYSQL_PATH%" (
    echo ERROR: MySQL not found
    echo Please install MySQL through Laragon
    pause
    exit /b 1
)
echo ✓ MySQL found

echo.
echo Step 4: Creating project directory...
if exist "%PROJECT_PATH%" (
    set /p overwrite="Project directory exists. Overwrite? (y/n): "
    if /i "!overwrite!" neq "y" (
        echo Setup cancelled
        pause
        exit /b 0
    )
    rmdir /s /q "%PROJECT_PATH%"
)

mkdir "%PROJECT_PATH%"
echo ✓ Project directory created

echo.
echo Step 5: Copying files...
xcopy /e /i /h /y "%~dp0.." "%PROJECT_PATH%"
echo ✓ Files copied

echo.
echo Step 6: Setting up environment file...
if not exist "%PROJECT_PATH%\.env" (
    copy "%PROJECT_PATH%\.env.example" "%PROJECT_PATH%\.env" 2>nul
    if not exist "%PROJECT_PATH%\.env" (
        echo Creating default .env file...
        (
            echo # Database Configuration
            echo DB_HOST=localhost
            echo DB_NAME=hyperabyss_cluster
            echo DB_USER=root
            echo DB_PASS=
            echo DB_PORT=3306
            echo.
            echo # RCON Passwords
            echo RAGNAROK_RCON_PASSWORD=your_password_here
            echo THEISLAND_RCON_PASSWORD=your_password_here
            echo THECENTER_RCON_PASSWORD=your_password_here
            echo FORGLAR_RCON_PASSWORD=your_password_here
            echo SVARTALFHEIM_RCON_PASSWORD=your_password_here
        ) > "%PROJECT_PATH%\.env"
    )
)
echo ✓ Environment file ready

echo.
echo Step 7: Creating required directories...
mkdir "%PROJECT_PATH%\logs" 2>nul
mkdir "%PROJECT_PATH%\assets\images" 2>nul
echo ✓ Directories created

echo.
echo Step 8: Setting up database...
set /p setup_db="Setup database automatically? (y/n): "
if /i "!setup_db!" equ "y" (
    echo Creating database...
    "%MYSQL_PATH%\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS hyperabyss_cluster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    echo Importing schema...
    "%MYSQL_PATH%\bin\mysql.exe" -u root hyperabyss_cluster < "%PROJECT_PATH%\db\schema.sql"
    
    if !errorlevel! equ 0 (
        echo ✓ Database setup complete
    ) else (
        echo ⚠ Database setup failed - please run manually
    )
) else (
    echo ⚠ Database setup skipped - run db\schema.sql manually
)

echo.
echo Step 9: Setting up virtual host...
set APACHE_CONF=%LARAGON_PATH%\etc\apache2\sites-enabled\auto.%PROJECT_NAME%.test.conf

if not exist "%APACHE_CONF%" (
    (
        echo ^<VirtualHost *:80^>
        echo     DocumentRoot "%PROJECT_PATH%"
        echo     ServerName %PROJECT_NAME%.test
        echo     ServerAlias *.%PROJECT_NAME%.test
        echo     ^<Directory "%PROJECT_PATH%"^>
        echo         AllowOverride All
        echo         Require all granted
        echo     ^</Directory^>
        echo ^</VirtualHost^>
        echo.
        echo ^<VirtualHost *:443^>
        echo     DocumentRoot "%PROJECT_PATH%"
        echo     ServerName %PROJECT_NAME%.test
        echo     ServerAlias *.%PROJECT_NAME%.test
        echo     SSLEngine on
        echo     SSLCertificateFile "%LARAGON_PATH%\etc\ssl\laragon.crt"
        echo     SSLCertificateKeyFile "%LARAGON_PATH%\etc\ssl\laragon.key"
        echo     ^<Directory "%PROJECT_PATH%"^>
        echo         AllowOverride All
        echo         Require all granted
        echo     ^</Directory^>
        echo ^</VirtualHost^>
    ) > "%APACHE_CONF%"
    echo ✓ Virtual host configured
) else (
    echo ✓ Virtual host already exists
)

echo.
echo Step 10: Creating .htaccess...
if not exist "%PROJECT_PATH%\.htaccess" (
    (
        echo RewriteEngine On
        echo.
        echo # Security headers
        echo Header always set X-Content-Type-Options nosniff
        echo Header always set X-Frame-Options DENY
        echo Header always set X-XSS-Protection "1; mode=block"
        echo.
        echo # API routing
        echo RewriteRule ^api/(.*)$ /api/enhanced-api.php?endpoint=$1 [QSA,L]
        echo.
        echo # Asset caching
        echo ^<FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$"^>
        echo     ExpiresActive On
        echo     ExpiresDefault "access plus 1 month"
        echo     Header append Vary Accept-Encoding
        echo ^</FilesMatch^>
        echo.
        echo # Deny access to sensitive files
        echo ^<FilesMatch "\.(env|sql|log|dat)$"^>
        echo     Require all denied
        echo ^</FilesMatch^>
    ) > "%PROJECT_PATH%\.htaccess"
    echo ✓ .htaccess created
)

echo.
echo Step 11: Installing cron jobs (Task Scheduler)...
set /p install_tasks="Install scheduled tasks? (y/n): "
if /i "!install_tasks!" equ "y" (
    call "%PROJECT_PATH%\setup\setup-cron.bat"
    echo ✓ Scheduled tasks installed
) else (
    echo ⚠ Scheduled tasks skipped
)

echo.
echo Step 12: Setting file permissions...
icacls "%PROJECT_PATH%\logs" /grant Everyone:(OI)(CI)F /T >nul 2>&1
icacls "%PROJECT_PATH%\.env" /grant Administrators:F /remove Everyone >nul 2>&1
echo ✓ Permissions set

echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo Your HyperAbyss website is ready at:
echo HTTP:  http://%PROJECT_NAME%.test
echo HTTPS: https://%PROJECT_NAME%.test
echo.
echo Next steps:
echo 1. Start Laragon services
echo 2. Edit %PROJECT_PATH%\.env with your settings
echo 3. Update %PROJECT_PATH%\servers.json with your servers
echo 4. Visit http://%PROJECT_NAME%.test to test
echo.
echo For SSL setup, see README.md
echo.
pause