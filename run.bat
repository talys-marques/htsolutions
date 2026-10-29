@echo off
rem Inicia o blog no seu computador: run.bat (porta 8080) ou run.bat 8090
setlocal
cd /d "%~dp0"

set "PORT=8080"
if not "%~1"=="" set "PORT=%~1"

where php >nul 2>nul
if errorlevel 1 (
  echo PHP nao encontrado. Instale com:  winget install PHP.PHP.8.5
  echo Depois feche e abra o terminal de novo.
  pause
  exit /b 1
)

rem O PHP do winget vem sem php.ini, entao as extensoes ficam desligadas.
rem Liga as que faltam, usando a pasta ext\ ao lado do php.exe.
for /f "delims=" %%B in ('php -r "echo PHP_BINARY;"') do set "PHPEXT=%%~dpBext"
set "PHPARGS="
for %%X in (mbstring gd exif fileinfo zip) do (
  php -r "exit(extension_loaded('%%X') ? 0 : 1);" || if exist "%PHPEXT%\php_%%X.dll" call set "PHPARGS=%%PHPARGS%% -d extension=%%X"
)
if defined PHPARGS set PHPARGS=-d extension_dir="%PHPEXT%"%PHPARGS%

php %PHPARGS% -r "exit(extension_loaded('mbstring') ? 0 : 1);"
if errorlevel 1 (
  echo O PHP esta sem a extensao mbstring, que o blog precisa.
  echo Ative "extension=mbstring" no php.ini ^(veja: php --ini^).
  pause
  exit /b 1
)

rem Porta ocupada? (procura um servidor escutando; funciona em qualquer idioma do Windows)
netstat -ano | findstr /r /c:":%PORT%  *0\.0\.0\.0:0 " /c:":%PORT%  *\[::\]:0 " >nul
if not errorlevel 1 (
  echo A porta %PORT% ja esta em uso. O blog pode ja estar rodando em http://localhost:%PORT%
  echo Para usar outra porta:  run.bat 8090
  pause
  exit /b 1
)

echo.
echo   Blog:   http://localhost:%PORT%
echo   Painel: http://localhost:%PORT%/admin
echo.
echo   Para parar, feche esta janela ou aperte Ctrl+C.
echo.

rem Abre o navegador depois que o servidor subir
start "" /b cmd /c "ping -n 2 127.0.0.1 >nul & start "" http://localhost:%PORT%/admin"

rem Limites de envio maiores que o padrao do PHP (2 MB), para importar exportacoes grandes do WordPress
php %PHPARGS% -d upload_max_filesize=64M -d post_max_size=64M -S localhost:%PORT% -t public public/index.php
