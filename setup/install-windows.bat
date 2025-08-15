@::HyperAbyss-Setup-v1.0
@set setupver=1.0
@setlocal DisableDelayedExpansion
@echo off

::============================================================================
::
::   HyperAbyss ARK Cluster Setup Script v%setupver%
::   Enhanced Windows/Laragon Installation
::
::============================================================================

:: Set environment variables for proper execution
setlocal EnableExtensions
setlocal DisableDelayedExpansion
set "PathExt=.COM;.EXE;.BAT;.CMD;.VBS;.VBE;.JS;.JSE;.WSF;.WSH;.MSC"
set "SysPath=%SystemRoot%\System32"
set "Path=%SystemRoot%\System32;%SystemRoot%;%SystemRoot%\System32\Wbem;%SystemRoot%\System32\WindowsPowerShell\v1.0\"

if exist "%SystemRoot%\Sysnative\reg.exe" (
    set "SysPath=%SystemRoot%\Sysnative"
    set "Path=%SystemRoot%\Sysnative;%SystemRoot%;%SystemRoot%\Sysnative\Wbem;%SystemRoot%\Sysnative\WindowsPowerShell\v1.0\;%Path%"
)

set "ComSpec=%SysPath%\cmd.exe"
set "_cmdf=%~f0"

:: Re-launch with proper architecture if needed
if exist %SystemRoot%\Sysnative\cmd.exe if not defined relaunch (
    setlocal EnableDelayedExpansion
    start %SystemRoot%\Sysnative\cmd.exe /c ""!_cmdf!" %* relaunch"
    exit /b
)

::============================================================================

title HyperAbyss Setup v%setupver% - Enhanced Installation

:: Initialize variables
set "TOTAL_STEPS=12"
set "CURRENT_STEP=0"
set "ERRORS_FOUND=0"
set "WARNINGS_FOUND=0"
set "LARAGON_PATH=C:\laragon"
set "PROJECT_NAME=hyperabyss"

:: Create timestamped log file
for /f "tokens=1-3 delims=/- " %%a in ('date /t') do set "logdate=%%c%%a%%b"
for /f "tokens=1-2 delims=: " %%a in ('time /t') do set "logtime=%%a%%b"
set "LOG_FILE=%~dp0install_log_%logdate%_%logtime%.txt"

:: Check admin privileges
call :step_start "Checking administrator privileges"
net session >nul 2>&1
if %errorlevel% neq 0 (
    call :step_failure "Must run as Administrator"
    echo.
    echo [ERROR] This script requires Administrator privileges
    echo Right-click the script and select "Run as administrator"
    echo.
    pause
    exit /b 1
)
call :step_success "Administrator privileges confirmed"

call :print_header
call :log_message "INFO" "Setup started by %USERNAME% at %date% %time%"

setlocal EnableDelayedExpansion
set "PROJECT_PATH=!LARAGON_PATH!\www\!PROJECT_NAME!"

:: Step 1: Check Laragon
call :step_start "Validating Laragon installation"
if not exist "!LARAGON_PATH!" (
    call :handle_missing_laragon
    if !errorlevel! neq 0 goto :setup_failed
) else (
    call :step_success "Laragon found at !LARAGON_PATH!"
)

:: Step 2: Check PHP
call :step_start "Checking PHP 8.2+ availability"
call :check_php_version
if !errorlevel! neq 0 (
    call :handle_missing_php
    if !errorlevel! neq 0 goto :setup_failed
)
call :step_success "PHP 8.2+ available: !PHP_PATH!"

:: Step 3: Check MySQL
call :step_start "Checking MySQL availability"
call :check_mysql
if !errorlevel! neq 0 (
    call :handle_missing_mysql
    if !errorlevel! neq 0 goto :setup_failed
)
call :step_success "MySQL found: !MYSQL_PATH!"

:: Step 4: Check Composer
call :step_start "Checking Composer availability"
call :check_composer
if !errorlevel! neq 0 (
    call :install_composer
    if !errorlevel! neq 0 (
        call :step_warning "Composer unavailable - skipping dependencies"
        set /a WARNINGS_FOUND+=1
    ) else (
        call :step_success "Composer installed successfully"
    )
) else (
    call :step_success "Composer found"
)

:: Step 5: Create project directory
call :step_start "Creating project directory"
call :create_project_directory
if !errorlevel! neq 0 goto :setup_failed
call :step_success "Project directory ready"

:: Step 6: Copy files
call :step_start "Copying project files"
call :copy_project_files
if !errorlevel! neq 0 goto :setup_failed
call :step_success "Files copied successfully"

:: Step 7: Create configuration
call :step_start "Creating configuration files"
call :create_config_files
if !errorlevel! neq 0 goto :setup_failed
call :step_success "Configuration files created"

:: Step 8: Setup database
call :step_start "Setting up database"
call :setup_database
if !errorlevel! neq 0 goto :setup_failed
call :step_success "Database configured"

:: Step 9: Install dependencies
call :step_start "Installing dependencies"
call :install_dependencies
if !errorlevel! neq 0 (
    call :step_warning "Dependency installation incomplete"
    set /a WARNINGS_FOUND+=1
) else (
    call :step_success "Dependencies installed"
)

:: Step 10: Web server config
call :step_start "Configuring web server"
call :create_webserver_config
if !errorlevel! neq 0 (
    call :step_warning "Web server config incomplete"
    set /a WARNINGS_FOUND+=1
) else (
    call :step_success "Web server configured"
)

:: Step 11: Scheduled tasks
call :step_start "Setting up scheduled tasks"
set /p "setup_tasks=Install scheduled tasks? (y/n): "
if /i "!setup_tasks!"=="y" (
    call :setup_tasks
    if !errorlevel! neq 0 (
        call :step_warning "Task setup incomplete"
        set /a WARNINGS_FOUND+=1
    ) else (
        call :step_success "Scheduled tasks configured"
    )
) else (
    call :step_success "Scheduled tasks skipped"
)

:: Step 12: File permissions
call :step_start "Setting file permissions"
call :set_file_permissions
if !errorlevel! neq 0 (
    call :step_warning "Permission setup incomplete"
    set /a WARNINGS_FOUND+=1
) else (
    call :step_success "Permissions configured"
)

:: Final summary
echo.
echo ============================================
echo   INSTALLATION COMPLETE
echo ============================================
echo.

if !ERRORS_FOUND! equ 0 (
    if !WARNINGS_FOUND! equ 0 (
        echo [SUCCESS] Installation completed successfully!
        echo.
        echo Your HyperAbyss cluster is ready:
        echo   URL: http://!PROJECT_NAME!.test
        echo   Path: !PROJECT_PATH!
        echo.
        echo Next steps:
        echo   1. Start Laragon services
        echo   2. Edit .env file with your settings
        echo   3. Update servers.json with your servers
        echo   4. Access http://!PROJECT_NAME!.test
    ) else (
        echo [WARNING] Installation completed with !WARNINGS_FOUND! warnings
        echo Check log: !LOG_FILE!
    )
) else (
    echo [ERROR] Installation had !ERRORS_FOUND! errors
    echo Check log: !LOG_FILE!
)

echo.
echo Log saved: !LOG_FILE!
echo.
pause
exit /b 0

:setup_failed
echo.
echo [ERROR] Setup failed at step !CURRENT_STEP!
echo Check log file: !LOG_FILE!
echo.
pause
exit /b 1

::============================================================================
:: FUNCTIONS
::============================================================================

:print_header
echo.
echo ============================================
echo   HyperAbyss ARK Cluster Setup v%setupver%
echo   Enhanced Windows Installation
echo ============================================
echo.
goto :eof

:step_start
set /a CURRENT_STEP+=1
echo.
echo [Step !CURRENT_STEP!/!TOTAL_STEPS!] %~1...
call :log_message "INFO" "Step !CURRENT_STEP!: %~1"
goto :eof

:step_success
echo [SUCCESS] %~1
call :log_message "SUCCESS" "%~1"
goto :eof

:step_failure
echo [ERROR] %~1
call :log_message "ERROR" "%~1"
set /a ERRORS_FOUND+=1
goto :eof

:step_warning
echo [WARNING] %~1
call :log_message "WARNING" "%~1"
goto :eof

:log_message
echo [%date% %time%] [%~1] %~2 >> "!LOG_FILE!"
goto :eof

:handle_missing_laragon
echo [ERROR] Laragon not found at !LARAGON_PATH!
echo.
echo Options:
echo   1. Open Laragon download page
echo   2. Continue without Laragon (manual setup)
echo   3. Exit
echo.
set /p "choice=Select option (1-3): "

if "!choice!"=="1" (
    start https://laragon.org/download/
    echo Please install Laragon and restart this script
    exit /b 1
) else if "!choice!"=="2" (
    call :step_warning "Continuing without Laragon"
    set "LARAGON_PATH="
    exit /b 0
) else (
    exit /b 1
)

:check_php_version
set "PHP_PATH="
set "PHP_FOUND=0"

if defined LARAGON_PATH (
    for /d %%D in ("!LARAGON_PATH!\bin\php\php-8.*") do (
        set "DIR_NAME=%%~nxD"
        call :parse_php_version "!DIR_NAME!"
        if !PHP_FOUND! equ 1 (
            set "PHP_PATH=%%D\php.exe"
            goto :php_done
        )
    )
) else (
    php --version >nul 2>&1
    if !errorlevel! equ 0 (
        set "PHP_PATH=php"
        set "PHP_FOUND=1"
    )
)

:php_done
if !PHP_FOUND! equ 0 exit /b 1
exit /b 0

:parse_php_version
set "version_str=%~1"
set "version_str=!version_str:php-=!"
set "major_minor=!version_str:~0,3!"
set "major_minor=!major_minor:.=!"
if !major_minor! geq 82 set "PHP_FOUND=1"
goto :eof

:handle_missing_php
echo [ERROR] PHP 8.2+ required but not found
echo.
echo Options:
echo   1. Open PHP download page
echo   2. Continue (will cause errors)
echo   3. Exit
echo.
set /p "choice=Select option (1-3): "

if "!choice!"=="1" (
    start https://windows.php.net/download/
    echo Install PHP 8.2+ and restart script
    exit /b 1
) else if "!choice!"=="2" (
    call :step_warning "Continuing without PHP"
    exit /b 0
) else (
    exit /b 1
)

:check_mysql
set "MYSQL_PATH="
set "MYSQL_FOUND=0"

if defined LARAGON_PATH (
    for /d %%D in ("!LARAGON_PATH!\bin\mysql\mysql-*") do (
        set "MYSQL_PATH=%%D"
        set "MYSQL_FOUND=1"
        goto :mysql_done
    )
) else (
    mysql --version >nul 2>&1
    if !errorlevel! equ 0 (
        set "MYSQL_PATH=mysql"
        set "MYSQL_FOUND=1"
    )
)

:mysql_done
if !MYSQL_FOUND! equ 0 exit /b 1
exit /b 0

:handle_missing_mysql
echo [ERROR] MySQL required but not found
echo.
echo Options:
echo   1. Open MySQL download page
echo   2. Continue (database setup will fail)
echo   3. Exit
echo.
set /p "choice=Select option (1-3): "

if "!choice!"=="1" (
    start https://dev.mysql.com/downloads/mysql/
    echo Install MySQL and restart script
    exit /b 1
) else if "!choice!"=="2" (
    call :step_warning "Continuing without MySQL"
    exit /b 0
) else (
    exit /b 1
)

:check_composer
set "COMPOSER_CMD="

if defined LARAGON_PATH (
    if exist "!LARAGON_PATH!\bin\composer\composer.phar" (
        set "COMPOSER_CMD=!PHP_PATH! !LARAGON_PATH!\bin\composer\composer.phar"
        exit /b 0
    )
)

composer --version >nul 2>&1
if !errorlevel! equ 0 (
    set "COMPOSER_CMD=composer"
    exit /b 0
)

exit /b 1

:install_composer
echo Installing Composer...
call :log_message "INFO" "Installing Composer"

if defined LARAGON_PATH (
    if not exist "!LARAGON_PATH!\bin\composer" mkdir "!LARAGON_PATH!\bin\composer"
    powershell -Command "& {Invoke-WebRequest -Uri 'https://getcomposer.org/composer.phar' -OutFile '!LARAGON_PATH!\bin\composer\composer.phar'}" >>"!LOG_FILE!" 2>&1
    if !errorlevel! equ 0 (
        set "COMPOSER_CMD=!PHP_PATH! !LARAGON_PATH!\bin\composer\composer.phar"
        exit /b 0
    )
)

exit /b 1

:create_project_directory
if exist "!PROJECT_PATH!" (
    echo [WARNING] Directory exists: !PROJECT_PATH!
    set /p "overwrite=Overwrite? (y/n): "
    if /i "!overwrite!" neq "y" (
        echo Setup cancelled
        exit /b 1
    )
    rmdir /s /q "!PROJECT_PATH!" 2>nul
)

mkdir "!PROJECT_PATH!" 2>>"!LOG_FILE!"
if not exist "!PROJECT_PATH!" exit /b 1
exit /b 0

:copy_project_files
echo Cloning project from GitHub...
git clone https://github.com/ToastyToast25/hyprabyss.git "!PROJECT_PATH!" --depth 1 >>"!LOG_FILE!" 2>&1
if !errorlevel! neq 0 (
    echo [ERROR] Git clone failed. Ensure Git is installed and the repo is accessible.
    exit /b 1
)

:: Remove .git folder to clean up
rd /s /q "!PROJECT_PATH!\.git" 2>>"!LOG_FILE!"

:: Remove the setup folder from the final installation (optional, but cleans up)
rd /s /q "!PROJECT_PATH!\setup" 2>>"!LOG_FILE!"

exit /b 0

:create_config_files
call :create_env_file
if !errorlevel! neq 0 exit /b 1

call :create_servers_file
exit /b !errorlevel!

:create_env_file
(
echo # Database Configuration
echo DB_HOST=127.0.0.1
echo DB_NAME=hyperabyss_cluster
echo DB_USER=root
echo DB_PASS=
echo DB_PORT=3306
echo DB_CHARSET=utf8mb4
echo.
echo # Security Configuration
echo CORS_ENABLED=true
echo CORS_ORIGINS=*
echo HTTPS_ONLY=false
echo API_KEY_REQUIRED=false
echo API_KEY=your-secret-api-key-here
echo.
echo # Rate Limiting
echo RATE_LIMIT_REQUESTS=60
echo RATE_LIMIT_WINDOW=60
echo.
echo # Server Settings
echo REFRESH_INTERVAL=15
echo.
echo # Discord Webhook ^(optional^)
echo DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
echo DISCORD_NOTIFICATIONS_ENABLED=false
echo.
echo # Application Settings
echo APP_TIMEZONE=America/New_York
echo LOG_LEVEL=INFO
) > "!PROJECT_PATH!\.env" 2>>"!LOG_FILE!"

if not exist "!PROJECT_PATH!\.env" exit /b 1
exit /b 0

:create_servers_file
(
echo {
echo   "servers": [
echo     {
echo       "key": "ragnarok",
echo       "name": "Cluster [HyperAbyss] Ragnarok PvP/3X/ORP/8Man",
echo       "ip": "198.23.225.136",
echo       "port": 7795,
echo       "query_port": 27028,
echo       "rcon_port": 27028,
echo       "rcon_password": "Frostbite2531!hrm",
echo       "map": "Ragnarok_WP",
echo       "enabled": true
echo     }
echo   ]
echo }
) > "!PROJECT_PATH!\servers.json" 2>>"!LOG_FILE!"

if not exist "!PROJECT_PATH!\servers.json" exit /b 1
exit /b 0

:setup_database
call :log_message "INFO" "Setting up database"

set /p "mysql_pass=Enter MySQL root password (Enter for none): "

if "!mysql_pass!"=="" (
    if defined LARAGON_PATH (
        set "MYSQL_CMD="!MYSQL_PATH!\bin\mysql.exe" -u root"
    ) else (
        set "MYSQL_CMD=mysql -u root"
    )
) else (
    if defined LARAGON_PATH (
        set "MYSQL_CMD="!MYSQL_PATH!\bin\mysql.exe" -u root -p!mysql_pass!"
    ) else (
        set "MYSQL_CMD=mysql -u root -p!mysql_pass!"
    )
)

echo Testing MySQL connection...
!MYSQL_CMD! -e "SELECT 1;" >>"!LOG_FILE!" 2>&1
if !errorlevel! neq 0 (
    echo [ERROR] Cannot connect to MySQL
    echo Check: MySQL service running, correct password
    exit /b 1
)

echo Creating database...
!MYSQL_CMD! -e "CREATE DATABASE IF NOT EXISTS hyperabyss_cluster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >>"!LOG_FILE!" 2>&1
if !errorlevel! neq 0 exit /b 1

if exist "!PROJECT_PATH!\db\schema.sql" (
    echo Importing schema...
    !MYSQL_CMD! hyperabyss_cluster < "!PROJECT_PATH!\db\schema.sql" >>"!LOG_FILE!" 2>&1
    if !errorlevel! neq 0 exit /b 1
)

exit /b 0

:install_dependencies
if not exist "!PROJECT_PATH!\composer.json" (
    call :create_basic_composer_json
)

if defined COMPOSER_CMD (
    if exist "!PROJECT_PATH!\composer.json" (
        pushd "!PROJECT_PATH!"
        !COMPOSER_CMD! install --no-dev --optimize-autoloader >>"!LOG_FILE!" 2>&1
        set "result=!errorlevel!"
        popd
        exit /b !result!
    )
)
exit /b 1

:create_basic_composer_json
(
echo {
echo     "name": "hyperabyss/ark-cluster",
echo     "description": "HyperAbyss ARK Cluster Management",
echo     "type": "project",
echo     "require": {
echo         "php": "^8.2"
echo     },
echo     "autoload": {
echo         "psr-4": {
echo             "HyperAbyss\\": "src/"
echo         }
echo     }
echo }
) > "!PROJECT_PATH!\composer.json"
exit /b 0

:create_webserver_config
if defined LARAGON_PATH (
    if exist "!LARAGON_PATH!\bin\apache" (
        call :create_htaccess
        exit /b !errorlevel!
    )
)
exit /b 0

:create_htaccess
(
echo RewriteEngine On
echo RewriteCond %%{REQUEST_FILENAME} !-f
echo RewriteCond %%{REQUEST_FILENAME} !-d
echo RewriteRule ^ index.php [QSA,L]
echo.
echo # Security headers
echo Header always set X-Content-Type-Options nosniff
echo Header always set X-Frame-Options DENY
echo Header always set X-XSS-Protection "1; mode=block"
echo.
echo # API routing
echo RewriteRule ^api/^(.*)$ /api/enhanced-api.php?endpoint=$1 [QSA,L]
echo.
echo # Asset caching
echo ^<FilesMatch "\^.^(css^|js^|png^|jpg^|jpeg^|gif^|ico^|svg^)$"^>
echo     ExpiresActive On
echo     ExpiresDefault "access plus 1 month"
echo     Header append Vary Accept-Encoding
echo ^</FilesMatch^>
echo.
echo # Deny access to sensitive files
echo ^<FilesMatch "\^.^(env^|sql^|log^|dat^)$"^>
echo     Require all denied
echo ^</FilesMatch^>
) > "!PROJECT_PATH!\.htaccess" 2>>"!LOG_FILE!"

if not exist "!PROJECT_PATH!\.htaccess" exit /b 1
exit /b 0

:setup_tasks
if exist "!PROJECT_PATH!\setup\setup-cron.bat" (
    call "!PROJECT_PATH!\setup\setup-cron.bat" >>"!LOG_FILE!" 2>&1
    exit /b !errorlevel!
)
exit /b 1

:set_file_permissions
if not exist "!PROJECT_PATH!\logs" mkdir "!PROJECT_PATH!\logs"

icacls "!PROJECT_PATH!\logs" /grant Everyone:^(OI^)^(CI^)F /T >>"!LOG_FILE!" 2>&1
icacls "!PROJECT_PATH!\.env" /grant Administrators:F /remove Everyone >>"!LOG_FILE!" 2>&1

exit /b 0
