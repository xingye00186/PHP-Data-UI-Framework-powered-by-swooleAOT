@echo off
call "C:\Program Files\Microsoft Visual Studio\18\Community\VC\Auxiliary\Build\vcvarsall.bat" x64 >nul 2>&1
f:\work\swoole_compiler\examples\vue-calc\swoole_compiler\swoole_compiler.exe apps\calculator\project.yml -f
