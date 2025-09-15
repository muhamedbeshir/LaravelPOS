@echo off
echo BulldozerSystem Python Launcher Utilities
echo ==========================================
echo.

:menu
echo Choose an option:
echo 1. Setup environment (install dependencies)
echo 2. Generate new cryptographic keys
echo 3. Test activation system
echo 4. Get customer UUID for activation
echo 5. Generate activation code for customer
echo 6. Complete build example
echo 7. Package application only
echo 8. Build executable
echo 9. Exit
echo.

set /p choice="Enter your choice (1-9): "

if "%choice%"=="1" goto setup
if "%choice%"=="2" goto genkeys
if "%choice%"=="3" goto testact
if "%choice%"=="4" goto getuuid
if "%choice%"=="5" goto gencode
if "%choice%"=="6" goto buildex
if "%choice%"=="7" goto package
if "%choice%"=="8" goto buildexe
if "%choice%"=="9" goto exit

echo Invalid choice. Please try again.
echo.
goto menu

:setup
echo Running setup...
python setup.py
pause
goto menu

:genkeys
echo Generating new cryptographic keys...
python generate_keys.py
pause
goto menu

:testact
echo Testing activation system...
python examples\test_activation.py
pause
goto menu

:getuuid
echo Getting customer UUID...
python examples\get_customer_uuid.py
pause
goto menu

:gencode
echo Generating activation code...
python keygen.py
pause
goto menu

:buildex
echo Running complete build example...
python examples\complete_build_example.py
pause
goto menu

:package
echo Packaging application...
set /p project="Enter Laravel project path: "
set /p php="Enter PHP runtime path: "
python packager.py "%project%" --php-runtime "%php%"
pause
goto menu

:buildexe
echo Building executable...
set /p project="Enter Laravel project path: "
set /p php="Enter PHP runtime path: "
python build.py "%project%" "%php%"
pause
goto menu

:exit
echo Goodbye!
exit /b 0