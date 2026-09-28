@echo off
REM ============================================================
REM  deploy.bat - Cola_CaO Blog one-click deploy
REM
REM  Flow:  commit locally -> push to GitHub -> server pulls
REM  Use:   double-click this file, or run  deploy  in terminal
REM
REM  Requires: SSH key at %USERPROFILE%\.ssh\colacao-blog
REM            (see docs\deploy-manual.md)
REM
REM  NOTE: keep this file ASCII-only. Chinese characters here get
REM        decoded as GBK by cmd.exe and break line parsing.
REM ============================================================

setlocal
cd /d "%~dp0"

set SERVER=root@120.26.123.175
set KEY=%USERPROFILE%\.ssh\colacao-blog
set SSHDIR=/var/www/cola-blog

echo.
echo ==========================================
echo   Blog Deploy    %date% %time%
echo ==========================================
echo.

echo [1/3] Committing local changes...
git add -A
git diff --cached --quiet
if not errorlevel 1 goto :nocommit
git commit -q -m "deploy: %date% %time%"
if errorlevel 1 goto :fail
echo       OK
goto :push

:nocommit
echo       (nothing new to commit)

:push
echo.
echo [2/3] Pushing to GitHub...
git push -q
if errorlevel 1 goto :fail
echo       OK

echo.
echo [3/3] Server pull...
ssh -i "%KEY%" -o StrictHostKeyChecking=no -o ConnectTimeout=15 %SERVER% "cd %SSHDIR% && git pull --ff-only -q && chown -R www-data:www-data blog/ && echo PULLED"
if errorlevel 1 goto :fail

echo.
echo ==========================================
echo   DONE  -^>  http://120.26.123.175/
echo ==========================================
echo.
pause
exit /b 0

:fail
echo.
echo   !! FAILED - read the message above.
echo   !! Common causes:
echo      - server has uncommitted changes (you used the online editor)
echo      - network / GitHub unreachable
echo      - SSH key not set up
echo.
pause
exit /b 1
