# VueCalc 构建说明

> 最后更新：2026-05-12

本文档说明如何构建 VueCalc 项目。详细技术原理参见 `../swoole compiler AOT 文档/最佳实践.html` 及 `docs/构建编译流程参考.md`。

---

## 快速开始

### 方式 1：应用编排器（推荐）

双击框架根目录的 **`main_build.bat`**，自动扫描 `apps/` 下的应用，列出后选择构建。编译完成后回到选择菜单，可继续构建其他应用，输入 `q` 退出。

```
vue-calc/
├── main_build.bat    ← [编排器] 扫描 apps/，循环选择并构建
├── build.bat         ← [构建器] 编译整个框架 (含所有 apps/)
└── apps/
    └── calculator/
        └── build.bat ← [自动生成] 由 main_build.bat 按需创建
```

### 方式 2：直接构建

双击 **`build.bat`** 或命令行运行：

```cmd
cd /d vue-calc
build.bat
```

### 方式 3：MSYS2 / Git Bash 手动构建

由于 MSYS2 的路径自动转换与 `cmd //c` 不兼容，需要在 bash 中手动执行：

```bash
# ---- 第一步：设置 MSVC 编译器环境（每次新终端只需执行一次）----
export MSVC_HOME="/c/Program Files/Microsoft Visual Studio/18/Community/VC/Tools/MSVC/14.50.35717"
export WINKIT_HOME="/c/Program Files (x86)/Windows Kits/10"
export WINKIT_VER="10.0.26100.0"

export PATH="${MSVC_HOME}/bin/Hostx64/x64:$PATH"

# 注意：INCLUDE 和 LIB 必须用 Windows 反斜杠格式 (cl.exe 是 Windows 程序)
export INCLUDE="C:\\Program Files\\Microsoft Visual Studio\\18\\Community\\VC\\Tools\\MSVC\\14.50.35717\\include;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\ucrt;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\shared;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\um;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\cppwinrt;C:\\Program Files (x86)\\Windows Kits\\10\\Include\\10.0.26100.0\\winrt"

export LIB="C:\\Program Files\\Microsoft Visual Studio\\18\\Community\\VC\\Tools\\MSVC\\14.50.35717\\lib\\x64;C:\\Program Files (x86)\\Windows Kits\\10\\Lib\\10.0.26100.0\\ucrt\\x64;C:\\Program Files (x86)\\Windows Kits\\10\\Lib\\10.0.26100.0\\um\\x64"

# ---- 第二步：SFC 编译器 (Vue → .gen.php) ----
cd "<框架根目录>"   # 即 build.bat 所在目录
"<仓库根>/php.exe" framework/sfc-compiler.php apps/calculator/Calculator.vue

# ---- 第三步：AOT 编译器 (PHP → exe) ----
cd "<仓库根>"       # 框架根的上两级
./swoole_compiler.exe <框架相对路径>/project.yml -f

# ---- 第四步：打包 (exe + DLL → apps/calculator/bin/) ----
cd "<框架根目录>"
mkdir -p apps/calculator/bin
cp "<仓库根>/vue_calc.exe" apps/calculator/bin/
cp "<仓库根>/php8ts.dll" apps/calculator/bin/
cp "<仓库根>/phpx.dll" apps/calculator/bin/
```

---

## 构建管道

```
Calculator.vue ──→ [SFC 编译器] ──→ gen/*.gen.php ──→ [AOT 编译器] ──→ vue_calc.exe ──→ [打包] ──→ apps/calculator/bin/
 (apps/calculator/)    (含嵌套组件解析)    (apps/calculator/gen/) (PHP→C++→MSVC→exe)              (exe + DLL)
                      ↳ 读取 project.yml
                         组件注册表 (v5 M2)
```

### Step 1: SFC 编译器

| 项目 | 说明 |
|------|------|
| 命令 | `php framework/sfc-compiler.php apps/calculator/Calculator.vue` |
| 输入 | `apps/calculator/Calculator.vue`（template + script + style 三块）+ 嵌套组件 |
| 组件注册 | SFC 编译器自动读取同目录 `apps/calculator/project.yml` 中的 `components` 映射 |
| 输出 | `apps/calculator/gen/Calculator.gen.php`、`apps/calculator/gen/CalculatorLayout_gen.php` |
| 特性 | 标准 PHP CLI 脚本，不经过 AOT；支持嵌套组件解析与布局内联 (v5 M2) |

### Step 2: AOT 编译器

| 项目 | 说明 |
|------|------|
| 命令 | `swoole_compiler.exe project.yml -f` (从 REPO_ROOT 执行) |
| 入口 | `apps/calculator/main.php` 中的 `main()` 函数 (project.yml 通过 `./apps/calculator` 引入) |
| 输入 | project.yml 指定的所有 sources |
| 子步骤 | prepare → convert → compile (MSVC cl.exe) → link |
| 产物 | `vue_calc.exe`（~240KB，PE32+ x64 原生可执行文件） |

### Step 3: 打包

参照 [Swoole Compiler AOT 最佳实践](../swoole%20compiler%20AOT%20文档/最佳实践.html) 第 2 节「如何打包分发」：

> AOT 编译器采用动态链接方式，生成的 exe 依赖运行时 DLL。
> 部署包应包含：二进制 exe + libphp.so(dll) + libphpx.so(dll)

| 文件 | 大小 | 说明 |
|------|------|------|
| `vue_calc.exe` | ~240KB | 计算器主程序 |
| `php8ts.dll` | ~11MB | PHP 8 Thread-Safe 运行时 |
| `phpx.dll` | ~2MB | PHPX 桥接库 (PHP ↔ C++ 互操作) |

**三个文件放在同一目录下即可独立分发运行**，无需安装 PHP 解释器。

---

## 环境要求

| 组件 | 路径 / 版本 | 用途 |
|------|------------|------|
| PHP CLI | `F:\work\swoole_compiler\php.exe` (v8.4) | 运行 SFC 编译器 |
| Swoole Compiler | `F:\work\swoole_compiler\swoole_compiler.exe` | AOT 编译 |
| Visual Studio 2026 | v18.5.2，C++ 桌面开发工作负载 | 提供 cl.exe 编译器 |
| Windows SDK | `C:\Program Files (x86)\Windows Kits\10\` | user32.lib, gdi32.lib 等 |

> **注意**：`php.exe` 必须使用项目自带的 PHP 8.4，不能使用系统环境中的 PHP 7.4。两者 AOT 兼容性不同。

---

## 常见问题

### Q: 双击 build.bat 闪退？

在 cmd.exe 中运行以查看错误信息：

```cmd
cd /d F:\work\swoole_compiler\examples\vue-calc
build.bat
```

常见原因：
- Visual Studio 2026 未安装或安装路径不是默认位置 → 修改 build.bat 中的 `VCVARSALL` 路径
- PHP CLI 不在预期位置 → 修改 build.bat 中的 `REPO_ROOT` 变量
- vcvarsall.bat 执行失败 → 确认安装了 C++ 桌面开发工作负载

### Q: 提示 `'cl' 不是内部或外部命令`？

MSVC 编译器环境未初始化。双击 build.bat 会自动调用 vcvarsall.bat。

如果在 MSYS2 中遇到此问题，请按上面"方式 2"的步骤先设置环境变量。

### Q: SFC 编译器报错？

检查以下项目：
- Calculator.vue 是否包含完整的三个块：`<template>` / `<script lang="php">` / `<style>`
- 嵌套组件 (`<display-panel>` 等) 对应的 `.vue` 文件是否存在于 `components/` 目录
- `apps/calculator/project.yml` 中 `components` 注册表是否正确

### Q: AOT 编译器报错？

常见错误及解决方案参见 `docs/构建编译流程参考.md` 第 7-8 节。最常见的几类：

| 错误 | 原因 | 解决 |
|------|------|------|
| `All execution code must be within a function` | require_once 写在顶层 | 删除，AOT 通过 sources 自动链接 |
| `Cannot redeclare class X` | 重复声明 | 删除 require 语句 |
| `Undefined variable $xxx` | 未初始化变量 | 显式赋初始值 |
| `error C3927` | 文件名含点号 | 改用下划线 |

### Q: MSYS2 中运行 build.bat 没反应？

这是 MSYS2 的已知限制 (PATH 转换问题)。请使用上面"方式 2"的手动构建命令。

### Q: 根目录 main.php 去哪了？开发时如何运行？

根级 `main.php` 已移除。原因：AOT 兼容性要求所有代码必须在 function 内，`require_once` 在顶层属游离代码。

- **构建**：双击 `main_build.bat`（框架根）选择应用；或直接双击 `build.bat`
- **开发**：`php apps/calculator/main.php`

### Q: main_build.bat 是什么？

框架根的应用构建编排器。扫描 `apps/` 下所有含 `project.yml` 的应用，为缺失 `build.bat` 的应用自动生成构建脚本，列出应用供选择并启动构建。

所有路径基于 `%~dp0` 相对计算，框架整体迁移后仍然有效。

### Q: 构建完成后产物在哪？

**`apps/<应用名>/bin/`** 目录下（每个应用拥有独立的可分发包）：

```
apps/calculator/bin/
├── vue_calc.exe   ← 主程序
├── php8ts.dll     ← PHP 运行时
└── phpx.dll       ← PHPX 桥接库
```

直接运行 `apps\calculator\bin\vue_calc.exe` 即可，无需安装 PHP。

### Q: 为什么需要打包 DLL？

AOT 编译器采用动态链接（而非 Go 那样的纯静态编译），以减小 exe 体积。因此 exe 运行需要 PHP 运行时库。参照最佳实践，将三者打包在一起即可独立分发。

### Q: 中文显示乱码怎么办？

`.bat` 文件使用 UTF-8 编码，脚本在首个 `echo` 输出前自动执行 `chcp 65001` 切换到 UTF-8 代码页。如果仍有乱码，在运行前手动执行：

```cmd
chcp 65001
```

然后运行 target build.bat。Windows Terminal 默认支持 UTF-8，推荐使用。

---

## 文件说明

```
vue-calc/                          ← 框架根 (可整体迁移)
├── main_build.bat              ← [编排器] 扫描 apps/, 选择构建目标应用
├── build.bat                   ← [构建器] 编译整个框架 (相对路径, 可迁移)
├── project.yml                 ← AOT 编译配置 (入口: apps/calculator/main.php)
├── framework/                 ← 可复用框架层 (v5 M2)
│   ├── ReactiveComponent.php  ← 响应式组件基类
│   ├── ChangeQueue.php        ← 变更队列 (环形缓冲)
│   ├── BaseRenderer.php       ← 数据驱动渲染器基类
│   ├── sfc-compiler.php       ← SFC 编译器入口
│   └── compiler/              ← 编译器模块
│       ├── template-parser.php
│       ├── script-analyzer.php
│       ├── css-mappings.php
│       ├── ast-nodes.php
│       ├── aot-validator.php
│       ├── component-registry.php
│       └── component-resolver.php
├── apps/calculator/           ← 计算器应用层
│   ├── main.php               ← [AOT 入口] 应用启动逻辑
│   ├── CalcApp.php            ← 窗口/事件循环控制器
│   ├── Calculator.vue         ← [源] SFC 单文件组件
│   ├── project.yml            ← 应用级配置 (组件注册表, v5 M2)
│   ├── components/
│   │   └── DisplayPanel.vue   ← 可复用子组件 (v5 M2)
│   ├── gen/                   ← [自动生成] SFC 编译器输出
│   │   ├── Calculator.gen.php
│   │   └── CalculatorLayout_gen.php
│   └── bin/                   ← [构建产物] 可分发包 (每个 app 独立)
│       ├── vue_calc.exe       ← 计算器主程序 (~240KB)
│       ├── php8ts.dll         ← PHP 8 运行时 (~11MB)
│       └── phpx.dll           ← PHPX 桥接库 (~2MB)
├── stub/
│   └── vue_calc.stub.php      ← C++ 函数 PHP 声明
├── cpp/
│   └── vue_calc.cc            ← Win32 GDI 原生实现
└── docs/
    ├── 构建编译流程参考.md     ← 详细技术文档
    └── BUILD.md               ← [本文件] 构建说明
```
