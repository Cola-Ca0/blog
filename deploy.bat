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
git status --short
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
git -c http.lowSpeedLimit=1000 -c http.lowSpeedTime=20 push -q
if errorlevel 1 goto :fail
echo       OK

echo.
echo [3/3] Server pull...
REM  guard: circuit-breakers -- a stalled fetch dies in ~20s instead of hanging forever
ssh -i "%KEY%" -o StrictHostKeyChecking=no -o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=4 %SERVER% "cd %SSHDIR% && timeout 60 git -c http.lowSpeedLimit=1000 -c http.lowSpeedTime=20 pull --ff-only -q && chown -R www-data:www-data blog/ && git log --oneline -1 && echo PULLED"
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
