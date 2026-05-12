@echo off
setlocal enabledelayedexpansion
:: ============================================================================
::  VueCalc 项目自动构建脚本
::
::  文件位置: examples/vue-calc/build.bat
::  用途:     一键完成 VueCalc 项目的完整构建管道
::
::  构建管道 (3步):
::    Step 1 - SFC 编译器: Calculator.vue → gen/*.gen.php
::    Step 2 - AOT 编译器:  *.php + project.yml → vue_calc.exe
::    Step 3 - 打包分发:   exe + php8ts.dll + phpx.dll → bin/
::
::  原则:
::    参照《swoole compiler AOT 文档/最佳实践.html》第 2 节「如何打包分发」:
::    AOT 编译器采用动态链接方式，生成的 exe 依赖运行时 DLL。
::    部署包应包含: 二进制 exe + libphp.so(dll) + libphpx.so(dll)
::
::  依赖:
::    - Visual Studio 2026 (提供 MSVC cl.exe 编译器)
::    - 项目自带 PHP CLI   (F:\work\swoole_compiler\php.exe, v8.4)
::    - Swoole Compiler AOT (F:\work\swoole_compiler\swoole_compiler.exe)
::
::  用法:
::    [推荐] 在 Windows 文件管理器中双击 build.bat
::    [推荐] 在 cmd.exe 中运行:  cd /d 项目目录 && build.bat
::    [备用] MSYS2/Git Bash:    详见文件末尾注释 或 docs/BUILD.md
::
::  产物:
::    gen/Calculator.gen.php          ← SFC 编译器生成
::    gen/CalculatorLayout_gen.php    ← SFC 编译器生成
::    bin/vue_calc.exe                ← 可执行文件 (~240KB)
::    bin/php8ts.dll                  ← PHP 8 运行时 (~11MB)
::    bin/phpx.dll                    ← PHPX 桥接库 (~2MB)
::
::  注意:
::    - 本脚本必须在 cmd.exe 原生环境中运行，MSYS2/Git Bash 不支持
::    - cl.exe 的 INCLUDE/LIB 等环境变量由 vcvarsall.bat 自动设置
::    - 先用 Swoole Compiler 编译到仓库根，再打包复制到 bin/
::
::  错误码速查:
::    1-5   = 依赖/路径检查失败
::    6-7   = MSVC 环境初始化失败
::    10-12 = SFC 编译器失败
::    20-21 = AOT 编译器失败
::    30-32 = 打包失败
::  ============================================================================

:: --------------------------------------------------------------------------
:: 配置区 —— 如果路径发生变化，只需修改此处
:: --------------------------------------------------------------------------

:: 仓库根目录 (swoole_compiler)
set "REPO_ROOT=F:\work\swoole_compiler"

:: 项目子目录 (相对于 REPO_ROOT)
set "PROJECT_DIR=examples\vue-calc"

:: PHP CLI (必须使用项目自带的 PHP 8.4, 不能用系统环境 7.4)
set "PHP_CLI=%REPO_ROOT%\php.exe"

:: Swoole Compiler AOT (PHP → C++ → exe)
set "SWOOLE_COMPILER=%REPO_ROOT%\swoole_compiler.exe"

:: Visual Studio 2026 MSVC 环境初始化脚本
set "VCVARSALL=C:\Program Files\Microsoft Visual Studio\18\Community\VC\Auxiliary\Build\vcvarsall.bat"

:: SFC 编译器 (Vue → .gen.php, 相对于项目目录)
set "SFC_COMPILER=tools\sfc-compiler.php"

:: Vue 源文件 (相对于项目目录)
set "VUE_SOURCE=src\Calculator.vue"

:: AOT 工程配置文件 (相对于项目目录)
set "PROJECT_YML=project.yml"

:: 产物文件名 (由 project.yml 中 name 字段决定)
set "OUTPUT_EXE=vue_calc.exe"

:: 运行时 DLL (AOT 动态链接依赖, 参照最佳实践需打包分发)
set "DLL_PHP=%REPO_ROOT%\php8ts.dll"
set "DLL_PHPX=%REPO_ROOT%\phpx.dll"

:: 打包输出目录 (相对于项目目录)
set "DIST_DIR=bin"

:: --------------------------------------------------------------------------
:: 前置检查 —— 确保所有依赖存在
:: --------------------------------------------------------------------------

echo.
echo ========================================
echo   VueCalc Build Pipeline
echo   项目路径: %REPO_ROOT%\%PROJECT_DIR%
echo ========================================
echo.

if not exist "%REPO_ROOT%\" (
    echo [错误] 仓库根目录不存在: %REPO_ROOT%
    echo   请修改 build.bat 第 35 行的 REPO_ROOT 变量
    exit /b 1
)

if not exist "%PHP_CLI%" (
    echo [错误] PHP CLI 不存在: %PHP_CLI%
    echo   请将 php.exe 放置在仓库根目录
    exit /b 2
)

if not exist "%SWOOLE_COMPILER%" (
    echo [错误] Swoole Compiler 不存在: %SWOOLE_COMPILER%
    exit /b 3
)

if not exist "%VCVARSALL%" (
    echo [错误] vcvarsall.bat 不存在: %VCVARSALL%
    echo   请确认 Visual Studio 2026 (v18) 已安装 C++ 桌面开发工作负载
    exit /b 4
)

cd /d "%REPO_ROOT%\%PROJECT_DIR%" 2>nul
if %errorlevel% neq 0 (
    echo [错误] 无法进入项目目录: %REPO_ROOT%\%PROJECT_DIR%
    exit /b 5
)

echo [检查] 所有依赖路径验证通过
echo    PHP:         %PHP_CLI%
echo    Swoole AOT:  %SWOOLE_COMPILER%
echo    MSVC:        %VCVARSALL%
echo.

:: --------------------------------------------------------------------------
:: Step 0: 初始化 MSVC 编译环境 (cl.exe, link.exe 等)
:: --------------------------------------------------------------------------

echo ----------------------------------------
echo  Step 0: 初始化 MSVC 编译环境
echo ----------------------------------------
echo   正在调用 vcvarsall.bat x64 ...
echo   (这会将 cl.exe、link.exe 等工具加入 PATH)
echo.

call "%VCVARSALL%" x64
if %errorlevel% neq 0 (
    echo.
    echo [错误] vcvarsall.bat 执行失败 (errorlevel: %errorlevel%)
    echo.
    echo   可能原因:
    echo     1. Visual Studio 2026 未安装 C++ 桌面开发工作负载
    echo     2. VS 安装路径不是默认的 "C:\Program Files\Microsoft Visual Studio\18\"
    echo     3. 从 MSYS2/Git Bash 运行导致路径转换错误
    echo.
    echo   解决方法:
    echo     - 在 Windows 文件管理器中双击 build.bat 运行
    echo     - 或在 cmd.exe 中运行: cd /d %REPO_ROOT%\%PROJECT_DIR% ^&^& build.bat
    echo     - MSYS2 用户请参考下方手动构建命令:
    echo.
    echo       # MSYS2 手动 Step 0 (设置 MSVC 环境):
    echo       export MSVC_HOME="/c/Program Files/Microsoft Visual Studio/18/Community/VC/Tools/MSVC/14.50.35717"
    echo       export PATH="${MSVC_HOME}/bin/Hostx64/x64:$PATH"
    echo       export INCLUDE="C:\\Program Files\\Microsoft Visual Studio\\18\\Community\\VC\\Tools\\MSVC\\14.50.35717\\include;..."
    echo.
    echo   完整 MSYS2 构建命令见 docs/BUILD.md
    exit /b 6
)

:: 验证 cl.exe 是否可用 (安全检查 —— vcvarsall 正常会把它加入 PATH)
where cl >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] cl.exe (MSVC C++ 编译器) 在 PATH 中未找到
    echo   vcvarsall.bat 可能执行了但未正确设置环境变量
    exit /b 7
)

echo.
echo [完成] MSVC 环境就绪
echo.

:: --------------------------------------------------------------------------
:: Step 1: SFC 编译器 —— 解析 .vue 组件并生成 PHP 代码
:: --------------------------------------------------------------------------
:: SFC (Single File Component) 编译器将 Calculator.vue 的 3 个块:
::   <template> → 解析为布局数组 (elements + buttons)
::   <script>   → 包装为 ReactiveComponent 子类
::   <style>    → CSS class → GDI BGR 颜色映射
:: 编译为标准 PHP, 不经 AOT, 确保 AOT 编译时拿到的是确定性的结果.

echo ----------------------------------------
echo  Step 1/3: SFC 编译器 (Vue -^> .gen.php)
echo   输入:  %VUE_SOURCE%
echo   输出:  gen\Calculator.gen.php
echo          gen\CalculatorLayout_gen.php
echo ----------------------------------------
echo.

"%PHP_CLI%" %SFC_COMPILER% %VUE_SOURCE%
set SFC_RESULT=%errorlevel%

if %SFC_RESULT% neq 0 (
    echo.
    echo [错误] SFC 编译器失败 (errorlevel: %SFC_RESULT%)
    echo   请检查 %VUE_SOURCE% 是否包含完整的 template/script/style 三块
    echo   详情参考: docs\构建编译流程参考.md (第 4 节)
    echo   AOT 验证错误参考: docs\构建编译流程参考.md (第 7-8 节)
    exit /b 10
)

:: 验证生成物 —— 确保编译器确实写入了文件
if not exist "gen\Calculator.gen.php" (
    echo [错误] 生成物缺失: gen\Calculator.gen.php
    exit /b 11
)
if not exist "gen\CalculatorLayout_gen.php" (
    echo [错误] 生成物缺失: gen\CalculatorLayout_gen.php
    exit /b 12
)

echo.
echo [完成] SFC 编译成功，.gen.php 文件已写入 gen\ 目录
echo.

:: --------------------------------------------------------------------------
:: Step 2: AOT 编译器 —— PHP 源码编译为原生 x64 Windows exe
:: --------------------------------------------------------------------------
:: swoole_compiler.exe 读取 project.yml, 执行 4 个子步骤:
::   prepare  → 递归收集 sources 目录下所有 .php 文件
::   convert  → 每个 .php 翻译为 .cc (C++), 生成 arginfo 头文件
::   compile  → cl /c *.cc → *.obj (MSVC 编译为对象文件)
::   link     → 链接所有 .obj + phpx.lib + php8ts.lib + Win32 libs → exe
::
:: 注意: AOT 编译必须在 REPO_ROOT 下运行, 因为 project.yml 的 sources
::       (main.php, ./src, ./gen 等) 都是相对于 project.yml 所在目录.

echo ----------------------------------------
echo  Step 2/3: AOT 编译器 (PHP -^> exe)
echo   配置:  %PROJECT_YML%
echo   产物:  %REPO_ROOT%\%OUTPUT_EXE%
echo ----------------------------------------
echo.

cd /d "%REPO_ROOT%"

"%SWOOLE_COMPILER%" "%PROJECT_DIR%\%PROJECT_YML%" -f
set AOT_RESULT=%errorlevel%

cd /d "%REPO_ROOT%\%PROJECT_DIR%"

if %AOT_RESULT% neq 0 (
    echo.
    echo [错误] AOT 编译失败 (errorlevel: %AOT_RESULT%)
    echo.
    echo   常见原因:
    echo     1. AOT 兼容性错误 ^(禁止 __get/__set, const数组, 未定义变量等^)
    echo        → 参考 docs\构建编译流程参考.md 第 7-8 节
    echo     2. C++ 编译错误 ^(语法, 头文件找不到等^)
    echo        → 检查错误信息中的 C++ 文件和行号
    echo     3. 链接错误 ^(unresolved external symbol 等^)
    echo        → 检查 cpp\*.cc 中函数实现是否与 stub\*.stub.php 声明一致
    exit /b 20
)

:: 验证产物 —— 确保 exe 文件存在
if not exist "%REPO_ROOT%\%OUTPUT_EXE%" (
    echo [警告] 编译过程未报错但未找到产物: %REPO_ROOT%\%OUTPUT_EXE%
    echo   请确认 project.yml 中 name 字段与产物名一致
    exit /b 21
)

echo.
echo [完成] AOT 编译成功
echo.

:: --------------------------------------------------------------------------
:: Step 3: 打包分发 —— 将 exe + 运行时 DLL 复制到 bin/
:: --------------------------------------------------------------------------
:: 参照最佳实践，AOT 编译产物需要搭配运行时库才能运行:
::   php8ts.dll  = PHP 8 Thread-Safe 运行时 (Zend Engine)
::   phpx.dll    = PHPX 桥接库 (PHP ↔ C++ 互操作层)
:: 这三个文件放在同一目录下即可独立分发运行，无需安装 PHP.

echo ----------------------------------------
echo  Step 3/3: 打包 (exe + DLL -^> bin\)
echo   参照: swoole compiler AOT 文档\最佳实践.html ^(第2节^)
echo ----------------------------------------
echo.

:: 创建 bin/ 目录
if not exist "%DIST_DIR%\" (
    mkdir "%DIST_DIR%"
    if %errorlevel% neq 0 (
        echo [错误] 无法创建目录: %DIST_DIR%
        exit /b 30
    )
    echo   创建目录: %DIST_DIR%\
)

:: 复制 exe
echo   复制 %OUTPUT_EXE% ...
copy /y "%REPO_ROOT%\%OUTPUT_EXE%" "%DIST_DIR%\%OUTPUT_EXE%" >nul
if %errorlevel% neq 0 (
    echo [错误] 复制 %OUTPUT_EXE% 失败
    exit /b 31
)

:: 复制 php8ts.dll (PHP 运行时)
echo   复制 php8ts.dll ...
copy /y "%DLL_PHP%" "%DIST_DIR%\php8ts.dll" >nul
if %errorlevel% neq 0 (
    echo [错误] 复制 php8ts.dll 失败
    exit /b 31
)

:: 复制 phpx.dll (PHPX 桥接库)
echo   复制 phpx.dll ...
copy /y "%DLL_PHPX%" "%DIST_DIR%\phpx.dll" >nul
if %errorlevel% neq 0 (
    echo [错误] 复制 phpx.dll 失败
    exit /b 31
)

echo.
echo   打包内容:
for %%f in ("%DIST_DIR%\*") do (
    echo     %%~nxf   %%~zf 字节
)

echo.
echo [完成] 打包完成，bin\ 目录可独立分发
echo.

:: --------------------------------------------------------------------------
:: 构建完成 —— 显示 Summary
:: --------------------------------------------------------------------------

echo ========================================
echo   构建完成！
echo ========================================
echo.
echo   包位置: %CD%\%DIST_DIR%\
echo.
echo   包内容:
for %%f in ("%DIST_DIR%\*.exe" "%DIST_DIR%\*.dll") do (
    echo     %%~nxf   %%~zf 字节   %%~tf
)
echo.
echo   管道:
echo     %VUE_SOURCE%  --^> [SFC] --^> gen\*.gen.php  --^> [AOT] --^> %OUTPUT_EXE%  --^> [Pack] --^> %DIST_DIR%\
echo.
echo   运行:
echo     %DIST_DIR%\%OUTPUT_EXE%
echo.
echo   相关文档:
echo     构建流程详解  docs\构建编译流程参考.md
echo     项目概述      .qoder\repowiki\zh\content\项目概述.md
echo     构建说明      docs\BUILD.md
echo     最佳实践      ..\swoole compiler AOT 文档\最佳实践.html
echo.
echo ========================================

exit /b 0

:: ============================================================================
::  附: MSYS2 / Git Bash 用户手动构建指南
::  ============================================================================
::
::  由于 MSYS2 的路径自动转换会破坏 cmd //c 中的 Windows 路径,
::  build.bat 无法在 MSYS2/Git Bash 中直接使用.
::
::  请使用以下手动命令完成构建 (已在 MSYS2 bash 中验证通过):
::
::  --------------------------------------------------------------------------
::  1. 设置 MSVC 环境变量 (每次新开终端都需要执行一次)
::  --------------------------------------------------------------------------
::
::  export MSVC_HOME="/c/Program Files/Microsoft Visual Studio/18/Community/VC/Tools/MSVC/14.50.35717"
::  export WINKIT_HOME="/c/Program Files (x86)/Windows Kits/10"
::  export WINKIT_VER="10.0.26100.0"
::
::  # cl.exe 可执行路径
::  export PATH="${MSVC_HOME}/bin/Hostx64/x64:$PATH"
::
::  # cl.exe 需要的头文件搜索路径 (INCLUDE 必须用 Windows 反斜杠格式)
::  export INCLUDE="C:\\Program Files\\Microsoft Visual Studio\\18\\Community\\VC\\Tools\\MSVC\\14.50.35717\\include;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\ucrt;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\shared;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\um;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\cppwinrt;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\winrt"
::
::  # cl.exe 需要的库搜索路径 (LIB 必须用 Windows 反斜杠格式)
::  export LIB="C:\\Program Files\\Microsoft Visual Studio\\18\\Community\\VC\\Tools\\MSVC\\14.50.35717\\lib\\x64;C:\\Program Files (x86)\\Windows Kits\\10\\Lib\\10.0.26100.0\\ucrt\\x64;C:\\Program Files (x86)\\Windows Kits\\10\\Lib\\10.0.26100.0\\um\\x64"
::
::  --------------------------------------------------------------------------
::  2. Step 1: SFC 编译器
::  --------------------------------------------------------------------------
::
::  cd "F:/work/swoole_compiler/examples/vue-calc"
::  "F:/work/swoole_compiler/php.exe" tools/sfc-compiler.php src/Calculator.vue
::
::  --------------------------------------------------------------------------
::  3. Step 2: AOT 编译器
::  --------------------------------------------------------------------------
::
::  cd "F:/work/swoole_compiler"
::  ./swoole_compiler.exe examples/vue-calc/project.yml -f
::
::  --------------------------------------------------------------------------
::  4. Step 3: 打包分发 (exe + DLL → bin/)
::  --------------------------------------------------------------------------
::
::  cd "F:/work/swoole_compiler/examples/vue-calc"
::  mkdir -p bin
::  cp "F:/work/swoole_compiler/vue_calc.exe" bin/
::  cp "F:/work/swoole_compiler/php8ts.dll" bin/
::  cp "F:/work/swoole_compiler/phpx.dll" bin/
::
::  --------------------------------------------------------------------------
::  可分发产物: F:/work/swoole_compiler/examples/vue-calc/bin/
::    vue_calc.exe   (~240KB)  — 计算器主程序
::    php8ts.dll     (~11MB)   — PHP 8 运行时
::    phpx.dll       (~2MB)    — PHPX 桥接库
::  ============================================================================
