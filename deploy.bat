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
REM  circuit-breakers: a stalled fetch dies in ~90s instead of hanging forever.
REM  Server->GitHub is flaky in CN, so fall back to the gh-proxy mirror; the
REM  commit hash still verifies content authenticity.
REM  Dirty-tree guard: the online admin editor writes files on the server, which
REM  makes 'git pull' abort. Fail fast with a clear message instead of a cryptic one.
ssh -i "%KEY%" -o StrictHostKeyChecking=no -o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=4 %SERVER% "cd %SSHDIR% && if git status --porcelain | grep -q .; then echo 'DIRTY: server has uncommitted changes (online editor?). Sync them down to local first.'; exit 3; fi; { timeout 90 git -c http.lowSpeedLimit=100 -c http.lowSpeedTime=120 -c http.postBuffer=524288000 pull --ff-only -q && echo PULLED_PLAIN; } || { echo 'plain pull failed -> gh-proxy'; timeout 120 git -c http.postBuffer=524288000 pull --ff-only -q https://gh-proxy.com/https://github.com/Cola-Ca0/blog.git master && echo PULLED_PROXY; } && chown -R www-data:www-data blog/ && git log --oneline -1"
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
echo      - DIRTY: server has uncommitted changes (online editor) - sync them down first
echo      - network / GitHub unreachable (plain pull already retried via gh-proxy)
echo      - SSH key not set up
echo.
pause
exit /b 1
