@echo off
setlocal

rem ------------------------------------------------------------
rem  DeepSID HVSC update - Step 03: Identify multiple players
rem ------------------------------------------------------------

rem Always use the folder containing this BAT file as work folder
cd /d "%~dp0"

set "PLAYER_ID=C:\Wamp\www\chordian\deepsid\utility\player_id_win64_v201\player-id.exe"
set "CSV_FILE=%~dp0output.csv"
set "PYTHON_DIR=C:\Wamp\www\chordian\deepsid\utility\python\specific"
set "PROCESSED_CSV=%PYTHON_DIR%\specific_players.csv"
set "PHP_EXE=C:\Wamp\bin\php\php8.2.18\php.exe"
set "IMPORTER=%~dp0import.php"

rem Use the existing HVSC environment variable, if available
if not defined HVSC (
    set "HVSC=C:\Users\jchuu\Music\HVSC\_High Voltage SID Collection"
    echo HVSC environment variable was not set.
    echo Using: "%HVSC%"
) else (
    echo Using existing HVSC environment variable:
    echo "%HVSC%"
)

echo.
echo Analyzing multiple players...

"%PLAYER_ID%" -h -m > "%CSV_FILE%"

if errorlevel 1 (
    echo.
    echo ERROR: player-id.exe failed.
    echo No database import was performed.
    pause
    exit /b 1
)

if not exist "%CSV_FILE%" (
    echo.
    echo ERROR: The CSV file was not created:
    echo "%CSV_FILE%"
    pause
    exit /b 1
)

for %%F in ("%CSV_FILE%") do (
    if %%~zF EQU 0 (
        echo.
        echo ERROR: The CSV file is empty:
        echo "%CSV_FILE%"
        pause
        exit /b 1
    )
)

echo Player analysis completed.
echo CSV file: "%CSV_FILE%"

echo.
echo Preparing specialized player CSV...

copy /Y "%CSV_FILE%" "%PYTHON_DIR%\_specific.csv" >nul

if errorlevel 1 (
    echo.
    echo ERROR: Failed to copy CSV to Python folder.
    pause
    exit /b 1
)

pushd "%PYTHON_DIR%"

python specific.py

if errorlevel 1 (
    popd
    echo.
    echo ERROR: Python processing failed.
    pause
    exit /b 1
)

popd

if not exist "%PROCESSED_CSV%" (
    echo.
    echo ERROR: The processed CSV file was not created:
    echo "%PROCESSED_CSV%"
    pause
    exit /b 1
)

for %%F in ("%PROCESSED_CSV%") do (
    if %%~zF EQU 0 (
        echo.
        echo ERROR: The processed CSV file is empty:
        echo "%PROCESSED_CSV%"
        pause
        exit /b 1
    )
)

echo Specialized player CSV completed.
echo CSV file: "%PROCESSED_CSV%"

echo.
echo Importing player data into the database...

"%PHP_EXE%" "%IMPORTER%" "%PROCESSED_CSV%"

if errorlevel 1 (
    echo.
    echo ERROR: The PHP importer failed.
    pause
    exit /b 1
)

echo.
echo Player identification and database import completed successfully.
pause

endlocal
exit /b 0
