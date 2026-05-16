@echo off
call "C:\Program Files\Microsoft Visual Studio\2022\Community\VC\Auxiliary\Build\vcvarsall.bat" x64 >nul 2>&1
set PHP_SDK_DIR=D:\AOT_sfc\swoole_compiler_v1051_windows_x86_64\SDK
set PHP_HOME=D:\AOT_sfc\swoole_compiler_v1051_windows_x86_64
set PHPX_HOME=D:\AOT_sfc\swoole_compiler_v1051_windows_x86_64\phpx
cd /d C:\Users\nanding\.qoder\worktree\AOT_sfc\kTpJgH
D:\AOT_sfc\swoole_compiler_v1051_windows_x86_64\swoole_compiler.exe apps/calculator/project.yml -f
echo EXIT_CODE=%errorlevel%
