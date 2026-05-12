# VueCalc 构建说明

> 最后更新：2026-05-12

本文档说明如何构建 VueCalc 项目。详细技术原理参见 `../swoole compiler AOT 文档/最佳实践.html` 及 `docs/构建编译流程参考.md`。

---

## 快速开始

### 方式 1：一键构建（推荐）

在 Windows 文件管理器中**双击 `build.bat`**，自动完成 SFC 编译 → AOT 编译 → 打包全部三步。

也可以在 cmd.exe 中运行：

```cmd
cd /d F:\work\swoole_compiler\examples\vue-calc
build.bat
```

### 方式 2：MSYS2 / Git Bash 手动构建

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
cd "F:/work/swoole_compiler/examples/vue-calc"
"F:/work/swoole_compiler/php.exe" tools/sfc-compiler.php src/Calculator.vue

# ---- 第三步：AOT 编译器 (PHP → exe) ----
cd "F:/work/swoole_compiler"
./swoole_compiler.exe examples/vue-calc/project.yml -f

# ---- 第四步：打包 (exe + DLL → bin/) ----
cd "F:/work/swoole_compiler/examples/vue-calc"
mkdir -p bin
cp "F:/work/swoole_compiler/vue_calc.exe" bin/
cp "F:/work/swoole_compiler/php8ts.dll" bin/
cp "F:/work/swoole_compiler/phpx.dll" bin/
```

---

## 构建管道

```
Calculator.vue ──→ [SFC 编译器] ──→ gen/*.gen.php ──→ [AOT 编译器] ──→ vue_calc.exe ──→ [打包] ──→ bin/
 (开发者编写)     (PHP CLI 预处理)    (纯 PHP 代码)    (PHP→C++→MSVC→exe)           (exe + DLL)
```

### Step 1: SFC 编译器

| 项目 | 说明 |
|------|------|
| 命令 | `php tools/sfc-compiler.php src/Calculator.vue` |
| 输入 | `src/Calculator.vue`（template + script + style 三块） |
| 输出 | `gen/Calculator.gen.php`、`gen/CalculatorLayout_gen.php` |
| 特性 | 标准 PHP CLI 脚本，不经过 AOT，替代运行时编译 |

### Step 2: AOT 编译器

| 项目 | 说明 |
|------|------|
| 命令 | `swoole_compiler.exe project.yml -f` |
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

检查 Calculator.vue 是否包含完整的三个块：
- `<template>` ... `</template>`
- `<script lang="php">` ... `</script>`
- `<style>` ... `</style>`

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

### Q: 构建完成后产物在哪？

**`bin/`** 目录（在项目目录下），包含完整的可分发包：

```
bin/
├── vue_calc.exe   ← 主程序
├── php8ts.dll     ← PHP 运行时
└── phpx.dll       ← PHPX 桥接库
```

直接运行 `bin\vue_calc.exe` 即可，无需安装 PHP。

### Q: 为什么需要打包 DLL？

AOT 编译器采用动态链接（而非 Go 那样的纯静态编译），以减小 exe 体积。因此 exe 运行需要 PHP 运行时库。参照最佳实践，将三者打包在一起即可独立分发。

---

## 文件说明

```
examples/vue-calc/
├── build.bat                  ← [本文件] 一键构建脚本 (3步)
├── project.yml                ← AOT 编译配置
├── main.php                   ← 程序入口
├── src/
│   ├── Calculator.vue         ← [源] SFC 单文件组件
│   ├── ReactiveComponent.php  ← 响应式基类
│   └── ChangeQueue.php        ← 变更队列
├── gen/                       ← [自动生成] SFC 编译器输出
│   ├── Calculator.gen.php
│   └── CalculatorLayout_gen.php
├── bin/                       ← [构建产物] 可分发包
│   ├── vue_calc.exe           ← 计算器主程序 (~240KB)
│   ├── php8ts.dll             ← PHP 8 运行时 (~11MB)
│   └── phpx.dll               ← PHPX 桥接库 (~2MB)
├── tools/
│   └── sfc-compiler.php       ← SFC 编译器入口
├── stub/
│   └── vue_calc.stub.php      ← C++ 函数 PHP 声明
├── cpp/
│   └── vue_calc.cc            ← Win32 GDI 原生实现
└── docs/
    ├── 构建编译流程参考.md     ← 详细技术文档
    └── BUILD.md               ← [本文件] 构建说明
```
