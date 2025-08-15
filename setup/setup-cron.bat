@echo off
setlocal enabledelayedexpansion

:: Enhanced HyperAbyss Cron Job Setup for Windows
:: Handles both installation and removal

title HyperAbyss Scheduled Tasks Setup

echo ========================================
echo  HyperAbyss Scheduled Tasks Manager
echo ========================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Please run as Administrator
    pause
    exit /b 1
)

:: Get script directory
set SCRIPT_DIR=%~dp0
set PROJECT_DIR=%SCRIPT_DIR%..
set PHP_PATH=C:\laragon\bin\php\php-8.2.12-Win32-vs16-x64\php.exe

:: Check if PHP exists
if not exist "%PHP_PATH%" (
    echo Warning: PHP not found at %PHP_PATH%
    set /p PHP_PATH="Enter full path to php.exe: "
    if not exist "!PHP_PATH!" (
        echo ERROR: PHP not found at !PHP_PATH!
        pause
        exit /b 1
    )
)

echo Found PHP at: %PHP_PATH%
echo Project directory: %PROJECT_DIR%
echo.

:: Main menu
:MENU
echo Choose an option:
echo 1) Install all scheduled tasks
echo 2) Remove all scheduled tasks
echo 3) Install individual tasks
echo 4) View current tasks
echo 5) Test task execution
echo 6) Exit
echo.
set /p choice="Enter choice (1-6): "

if "%choice%"=="1" goto INSTALL_ALL
if "%choice%"=="2" goto REMOVE_ALL
if "%choice%"=="3" goto INSTALL_INDIVIDUAL
if "%choice%"=="4" goto VIEW_TASKS
if "%choice%"=="5" goto TEST_TASKS
if "%choice%"=="6" goto EXIT
goto MENU

:INSTALL_ALL
echo.
echo Installing all HyperAbyss scheduled tasks...
echo.

:: Server monitoring (every minute)
echo Installing server monitoring task...
schtasks /create /tn "HyperAbyss_ServerMonitor" ^
    /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\monitoring.php\"" ^
    /sc minute /mo 1 ^
    /ru SYSTEM ^
    /f >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Server monitoring task installed
) else (
    echo ✗ Server monitoring task failed
)

:: Analytics tracking (every 5 minutes)
echo Installing analytics tracking task...
schtasks /create /tn "HyperAbyss_Analytics" ^
    /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\analytics-tracker.php\"" ^
    /sc minute /mo 5 ^
    /ru SYSTEM ^
    /f >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Analytics tracking task installed
) else (
    echo ✗ Analytics tracking task failed
)

:: Discord sync (every hour)
echo Installing Discord sync task...
schtasks /create /tn "HyperAbyss_Discord" ^
    /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\discord-sync.php\"" ^
    /sc hourly /mo 1 ^
    /ru SYSTEM ^
    /f >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Discord sync task installed
) else (
    echo ✗ Discord sync task failed
)

:: Daily cleanup (2 AM)
echo Installing daily cleanup task...
schtasks /create /tn "HyperAbyss_Cleanup" ^
    /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\cleanup.php\"" ^
    /sc daily /st 02:00 ^
    /ru SYSTEM ^
    /f >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Daily cleanup task installed
) else (
    echo ✗ Daily cleanup task failed
)

:: Database backup (daily at 3 AM)
echo Installing database backup task...
schtasks /create /tn "HyperAbyss_Backup" ^
    /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\backup.php\"" ^
    /sc daily /st 03:00 ^
    /ru SYSTEM ^
    /f >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Database backup task installed
) else (
    echo ✗ Database backup task failed
)

:: Health check (every 15 minutes)
echo Installing health check task...
schtasks /create /tn "HyperAbyss_HealthCheck" ^
    /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\api\health-check.php\"" ^
    /sc minute /mo 15 ^
    /ru SYSTEM ^
    /f >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Health check task installed
) else (
    echo ✗ Health check task failed
)

echo.
echo All tasks installation completed!
goto MENU

:REMOVE_ALL
echo.
echo Removing all HyperAbyss scheduled tasks...
echo.

set tasks=HyperAbyss_ServerMonitor HyperAbyss_Analytics HyperAbyss_Discord HyperAbyss_Cleanup HyperAbyss_Backup HyperAbyss_HealthCheck

for %%t in (%tasks%) do (
    echo Removing %%t...
    schtasks /delete /tn "%%t" /f >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✓ %%t removed
    ) else (
        echo ✗ %%t not found or failed to remove
    )
)

echo.
echo All tasks removal completed!
goto MENU

:INSTALL_INDIVIDUAL
echo.
echo Individual task installation:
echo 1) Server Monitor (every minute)
echo 2) Analytics Tracker (every 5 minutes)
echo 3) Discord Sync (hourly)
echo 4) Daily Cleanup (2 AM)
echo 5) Database Backup (3 AM)
echo 6) Health Check (every 15 minutes)
echo 7) Back to main menu
echo.
set /p task_choice="Enter choice (1-7): "

if "%task_choice%"=="1" (
    schtasks /create /tn "HyperAbyss_ServerMonitor" /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\monitoring.php\"" /sc minute /mo 1 /ru SYSTEM /f
    echo Server Monitor task installed
)
if "%task_choice%"=="2" (
    schtasks /create /tn "HyperAbyss_Analytics" /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\analytics-tracker.php\"" /sc minute /mo 5 /ru SYSTEM /f
    echo Analytics Tracker task installed
)
if "%task_choice%"=="3" (
    schtasks /create /tn "HyperAbyss_Discord" /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\discord-sync.php\"" /sc hourly /mo 1 /ru SYSTEM /f
    echo Discord Sync task installed
)
if "%task_choice%"=="4" (
    schtasks /create /tn "HyperAbyss_Cleanup" /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\cleanup.php\"" /sc daily /st 02:00 /ru SYSTEM /f
    echo Daily Cleanup task installed
)
if "%task_choice%"=="5" (
    schtasks /create /tn "HyperAbyss_Backup" /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\scripts\backup.php\"" /sc daily /st 03:00 /ru SYSTEM /f
    echo Database Backup task installed
)
if "%task_choice%"=="6" (
    schtasks /create /tn "HyperAbyss_HealthCheck" /tr "\"%PHP_PATH%\" \"%PROJECT_DIR%\api\health-check.php\"" /sc minute /mo 15 /ru SYSTEM /f
    echo Health Check task installed
)
if "%task_choice%"=="7" goto MENU

echo.
pause
goto MENU

:VIEW_TASKS
echo.
echo Current HyperAbyss scheduled tasks:
echo ===================================
schtasks /query /tn "HyperAbyss_*" /fo table /nh 2>nul
if %errorlevel% neq 0 (
    echo No HyperAbyss tasks found
) else (
    echo.
    echo Task details:
    echo -------------
    for %%t in (HyperAbyss_ServerMonitor HyperAbyss_Analytics HyperAbyss_Discord HyperAbyss_Cleanup HyperAbyss_Backup HyperAbyss_HealthCheck) do (
        echo.
        echo %%t:
        schtasks /query /tn "%%t" /fo list /v 2>nul | findstr /i "TaskName Schedule Next"
    )
)
echo.
pause
goto MENU

:TEST_TASKS
echo.
echo Testing task execution...
echo ========================
echo.

echo Testing PHP execution...
"%PHP_PATH%" -v
if %errorlevel% neq 0 (
    echo ERROR: PHP execution failed
    pause
    goto MENU
)
echo ✓ PHP is working

echo.
echo Testing script files...
if exist "%PROJECT_DIR%\scripts\monitoring.php" (
    echo ✓ monitoring.php found
) else (
    echo ✗ monitoring.php missing
)

if exist "%PROJECT_DIR%\scripts\analytics-tracker.php" (
    echo ✓ analytics-tracker.php found
) else (
    echo ✗ analytics-tracker.php missing
)

if exist "%PROJECT_DIR%\scripts\discord-sync.php" (
    echo ✓ discord-sync.php found
) else (
    echo ✗ discord-sync.php missing
)

echo.
echo Testing one script execution...
echo Running health check...
"%PHP_PATH%" "%PROJECT_DIR%\api\health-check.php"
echo.
echo Test completed!
pause
goto MENU

:EXIT
echo.
echo Task Summary:
echo =============
echo Server Monitor: Checks server status every minute
echo Analytics: Updates player statistics every 5 minutes  
echo Discord Sync: Updates Discord stats hourly
echo Cleanup: Removes old logs and data daily at 2 AM
echo Backup: Creates database backup daily at 3 AM
echo Health Check: System health monitoring every 15 minutes
echo.
echo To view task logs, check Windows Event Viewer:
echo Applications and Services Logs > Microsoft > Windows > TaskScheduler
echo.
echo Goodbye!
pause
exit /b 0