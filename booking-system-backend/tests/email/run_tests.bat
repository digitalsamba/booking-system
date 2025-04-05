@echo off
echo Email Testing Suite
echo =================
echo.

if "%~1"=="" (
    echo Usage: run_tests.bat your@email.com [provider]
    echo   provider: optional, defaults to value in .env
    exit /b 1
)

set EMAIL=%1
set PROVIDER=%2

if "%PROVIDER%"=="" (
    echo Testing with default provider from .env
) else (
    echo Testing with provider: %PROVIDER%
)

echo.
echo 1. Testing Direct API Call
echo --------------------------
php email_direct_test.php %EMAIL% %PROVIDER%

echo.
echo 2. Testing Email Service
echo -----------------------
php email_service_test.php %EMAIL% %PROVIDER%

echo.
echo 3. Testing Template Email
echo -----------------------
php template_email_test.php %EMAIL% %PROVIDER%

echo.
echo All tests completed! 