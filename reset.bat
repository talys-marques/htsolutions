@echo off
rem Volta o blog ao zero: apaga posts, usuarios, configuracoes e arquivos enviados.
rem O codigo, o tema e as extensoes nao sao tocados.
rem Considera a pasta de dados padrao (data\). Se voce mudou "data_dir" no
rem config.php, apague aquela pasta manualmente.
setlocal
cd /d "%~dp0"

rem Conta o que sera apagado (os JSON sao gravados com uma chave por linha)
set /a POSTS=0, PAGES=0, USERS=0, FILES=0
if exist "data\posts\*.json" (
  for /f %%N in ('findstr /m /r /c:"^ *.type.: .post." "data\posts\*.json" ^| find /c /v ""') do set POSTS=%%N
  for /f %%N in ('findstr /m /r /c:"^ *.type.: .page." "data\posts\*.json" ^| find /c /v ""') do set PAGES=%%N
)
if exist "data\users.json" for /f %%N in ('findstr /r /c:"^ *.role.:" "data\users.json" ^| find /c /v ""') do set USERS=%%N
for %%D in ("public\uploads" "public\wp-content") do if exist "%%~D" (
  for /f %%N in ('dir /s /b /a-d "%%~D" 2^>nul ^| findstr /v /i /l /e "\.htaccess" ^| find /c /v ""') do set /a FILES+=%%N
)

echo.
echo   ATENCAO: isto apaga TODO o conteudo do blog e nao da para desfazer.
echo.
echo   Sera apagado:
echo     - Posts:             %POSTS% (inclui rascunhos e lixeira)
echo     - Paginas:           %PAGES%
echo     - Usuarios:          %USERS%
echo     - Arquivos enviados: %FILES% (imagens e outros, inclui os copiados do WordPress)
echo     - Configuracoes, redirecionamentos e revisoes
echo.
echo   Pastas: data\, public\uploads\ e public\wp-content\ (se existir).
echo   Os arquivos .htaccess de protecao sao mantidos.
echo   Se quiser guardar algo, faca um backup antes (painel: Backup e ferramentas).
echo.
set "CONFIRM="
set /p "CONFIRM=  Digite SIM para apagar tudo: "
if /i not "%CONFIRM%"=="SIM" (
  echo.
  echo   Cancelado. Nada foi apagado.
  pause
  exit /b 1
)

call :empty "data"
call :empty "public\uploads"
if exist "public\wp-content" rd /s /q "public\wp-content"

echo.
echo   Pronto. O blog esta zerado.
echo   Abra /admin para criar a conta de administrador de novo.
echo   Se voce estava logado, entre de novo: as sessoes tambem foram apagadas.
echo.
pause
exit /b 0

rem Apaga tudo dentro da pasta, menos o .htaccess
:empty
if not exist "%~1" exit /b 0
for /f "delims=" %%D in ('dir /b /ad "%~1" 2^>nul') do rd /s /q "%~1\%%D"
for /f "delims=" %%F in ('dir /b /a-d "%~1" 2^>nul') do (
  if /i not "%%F"==".htaccess" del /f /q /a "%~1\%%F"
)
exit /b 0
