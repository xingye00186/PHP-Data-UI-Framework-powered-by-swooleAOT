# SFC Compiler Implementation Plan

## Context

VueCalc 当前是手工编写 PHP 类 + 硬编码布局常量。目标是将项目推进到技术文档第七章/第八章所描述的架构：
开发者只需编写 `.vue` 单文件组件，PHP SFC 编译器将其编译为 PHP ReactiveComponent + 布局数据，再经 AOT 编译为 exe。

C++ 渲染层使用现有 GDI 原语（不变）。

## Architecture

```
Calculator.vue   ──→  tools/sfc-compiler.php  ──→  src/Calculator.gen.php
                                                    src/CalculatorLayout.gen.php
                                                          │
                                              swoole_compiler.exe (AOT)
                                                          │
                                                     vue_calc.exe
```

SFC 编译器是**构建时**工具，用标准 PHP 运行，不参与 AOT 编译。

## Files Changed

| Action | File | Description |
|--------|------|-------------|
| NEW | `tools/sfc-compiler.php` | SFC 编译器主脚本（标准PHP，无AOT约束） |
| NEW | `src/CalculatorLayout.gen.php` | 生成：布局常量 + 元素数组 |
| NEW | `src/Calculator.gen.php` | 生成：从 .vue 提取的 ReactComponent 子类 |
| MODIFY | `main.php` | CalcRenderer/CalcApp 改用生成的 LAYOUT 数组；删除硬编码常量 |
| MODIFY | `src/Calculator.vue` | 改写为 SFC 格式（template+script+style） |
| MODIFY | `compile_vue_calc.bat` | 增加 SFC 编译步骤；输出文件名修正为 vue-calc |
| DELETE | `src/Calculator.php` | 被 Calculator.gen.php 替代 |

## Template Element Specification

5 种自定义元素，直接映射到 GDI 原语：

| Element | Attributes | Maps To |
|---------|-----------|---------|
| `<app>` | title, width, height | WINDOW_WIDTH/HEIGHT 常量 |
| `<rect>` | x, y, w, h, class | vue_fill_rect |
| `<text>` | :bind, class, align, container-x/w | vue_draw_text（数据绑定） |
| `<grid>` | x, y, cols, rows, cell-w, cell-h, margin | 按钮布局容器 |
| `<btn>` | row, col, label, class, @click | vue_draw_button + 点击路由 |

Directives:
- `:bind="propName"` — 单向绑定，渲染时从组件读取属性
- `@click="method"` 或 `@click="method(arg)"` — 点击事件路由

## SFC Compiler Implementation (4 Steps)

### Step 1: Block Extraction
用 regex 从 `.vue` 提取 `<template>`、`<script lang="php">`、`<style>` 三个块。

### Step 2: Template Parsing
每种元素用专用 regex 解析属性，生成结构化数据。无需 XML 解析器。

### Step 3: Style Resolution
解析 CSS 规则，建立 class → {bg, fg, fontSize, bold} 映射表。
CSS `#RRGGBB` → GDI BGR 整数（例：`#ff9500` → `0x0095FF`）。
边框颜色 = 背景色各通道 +20。

### Step 4: Code Generation
输出两个文件：
- **CalculatorLayout.gen.php**: `LAYOUT` 常量数组，含 `elements`（背景/文本）和 `buttons`（预计算坐标+颜色+handler）
- **Calculator.gen.php**: 从 `<script lang="php">` 提取的类体 + `use native_types` + 类声明 + 构造函数

## main.php 改动

### CalcRenderer.render()
- 遍历 `LAYOUT['elements']`：rect → vue_fill_rect，text → 读 `$this->component->{bind}` → vue_draw_text
- 遍历 `LAYOUT['buttons']`：vue_draw_button + 居中 vue_draw_text
- 删除 `getButtonType()` 方法、硬编码颜色逻辑、手动坐标计算

### CalcApp.handleClick()
- 遍历 `LAYOUT['buttons']` 做 bounding-box 碰撞检测
- 命中后：`$this->calc->{$handler}($arg)` 或 `$this->calc->{$handler}()`
- 删除 `intdiv()` 坐标转换

### 删除的常量
BTN_COLS, BTN_ROWS, BTN_WIDTH, BTN_HEIGHT, BTN_MARGIN, DISPLAY_HEIGHT, WINDOW_WIDTH, WINDOW_HEIGHT, BUTTONS, BTN_TYPE_* 四个类型常量

## Build Script

```bat
echo === Step 1: SFC Compiler ===
php tools\sfc-compiler.php src\Calculator.vue
echo === Step 2: AOT Compilation ===
swoole_compiler.exe project.yml -f
```

## AOT Compatibility

生成的 `.gen.php` 遵循所有 AOT 约束：
- 无 `__get/__set`
- 直接属性声明 + 手动 `$this->dirty = true`
- 只用 PHP 7 级别函数（strpos 等）
- 无反射/eval/include
- `const` 数组可被 AOT 常量折叠

## Verification

1. `php tools/sfc-compiler.php` 成功生成两个 .gen.php
2. `compile_vue_calc.bat` 完整编译通过
3. `vue-calc.exe` 启动显示窗口，布局与原来一致
4. 点击所有按钮功能正常（数字/运算符/等于/清屏/退格）
5. 长数字自动缩小字号
6. 除零显示 "Error"
