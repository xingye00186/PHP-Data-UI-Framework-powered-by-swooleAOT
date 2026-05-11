# VueCalc v4 设计规划文档

> **文档定位**：v4 设计蓝图 — 评估 v3 M1 成果、诊断当前缺口、规划 v4 路线图
> **版本**：v4.0
> **基于基线**：v3 M1（编译器健壮化，已交付）
> **关联文档**：`VueCalc技术规划文档_v3.html`、`VueCalc迭代v3归档_M1编译器健壮化.html`、`VueCalc技术文档_v2.html`
> **项目基因**：`.vue → sfc-compiler → .gen.php → AOT → vue_calc.exe (~232KB)`

---

## Context

### 为什么需要 v4

v3 M1 已于 2026-05-11 交付，成功将 SFC 编译器从"正则直出"升级为"递归下降 AST 管道"，偿清了编译器层面的 3 项核心技术债务（D1-D3）。但**运行时层面的 3 项高优先级债务（D4-D6）完全未被触及**：

| 债务 | 位置 | 问题 |
|------|------|------|
| D4 | `main.php:38-47` | `getBindValue()` 硬编码 if/else 映射 — 新增 `:bind` 属性需手动改代码 |
| D5 | `main.php:244-258` | `dispatchClick()` 硬编码 if/else 路由 — 新增 `@click` 处理器需手动改代码 |
| D6 | `Calculator.vue` 7个方法 | 手动 `$this->dirty = true` — 每处状态变更都需开发者记得写脏标记 |

**这些债务直接违反了 SFC 的"声明式 UI"核心理念**：模板中已经声明了绑定关系和事件处理，运行时代码不应重复维护相同信息。v4 的核心使命是**让 SFC 编译器真正接管这些运行时映射的自动生成**。

### v4 要达成什么

1. **消除三大运行时硬编码债务（D4-D6）**：编译器自动生成 getBindValue、dispatchClick、dirty 标记
2. **引入 v-model 双向绑定和 v-if 条件渲染**：扩展模板表达能力
3. **为组件生态铺路（M3）**：嵌套组件编译的基础设施

---

## 一、版本演进回顾

### 1.1 版本历程

```
v1 (初始版)         v2 (SFC编译器)       v3 M1 (编译器健壮化)      v4 (响应式系统)
  │                   │                   │                       │
  ├─ 硬编码布局       ├─ SFC管道打通       ├─ 递归下降AST解析器     ├─ 自动dirty标记
  ├─ __get/__set      ├─ 正则模板解析      ├─ CSS映射表正式化       ├─ 自动getBindValue
  ├─ 失败于AOT        ├─ 显式属性+手动dirty ├─ AOT校验器            ├─ 自动dispatchClick
  │                   ├─ 232KB exe        ├─ 25项测试100%通过      ├─ v-model双向绑定
  │                   │                   ├─ 8种编译错误检测        └─ v-if条件渲染
  └─ 不可用           └─ 可用 ✅           └─ 可用 ✅
```

### 1.2 v3 M1 核心交付（基线）

| 交付物 | 文件 | 规模 | 完成度 |
|--------|------|------|--------|
| 递归下降 Parser | `tools/compiler/template-parser.php` | 680 行 | ✅ |
| AST 节点定义 (7种) | `tools/compiler/ast-nodes.php` | 153 行 | ✅ |
| CSS 映射表 (8属性) | `tools/compiler/css-mappings.php` | 210 行 | ✅ |
| AOT 校验器 (5规则) | `tools/compiler/aot-validator.php` | 169 行 | ✅ |
| 编译器主入口重写 | `tools/sfc-compiler.php` | 210 行 | ✅ |
| 单元测试套件 | `tests/sfc-compiler-test.php` | 365 行 | ✅ |
| 布局验证脚本 | `tests/verify-layout.php` | 72 行 | ✅ |

**架构收益**（编译管道 v2→v3）：

```
v2:  .vue → [正则直出] → .gen.php

v3:  .vue → Tokenize → Parse→AST → Lower→Layout → AOT校验 → CodeGen → .gen.php
             5种Token   递归下降    AST降级     5规则检查   代码生成
```

核心改进：
- **可扩展性**：新增模板标签只需添加 `parseXxx()` 方法 + AST 节点类
- **错误定位**：所有解析错误精确到行号（8种编译错误类型）
- **安全护栏**：AOT 校验在写盘前拦截不合规代码，消除"编译才发现"的循环

### 1.3 技术债务偿还进度

| 编号 | 描述 | M1前 | M1后 | v4处理 |
|------|------|------|------|--------|
| D1 | 正则解析无AST | 🔴 高 | 🟢 已偿清 | — |
| D2 | CSS仅4属性 | 🔴 高 | 🟢 已偿清 | — |
| D3 | 代码生成无AOT校验 | 🔴 高 | 🟢 已偿清 | — |
| **D4** | **getBindValue硬编码** | 🔴 高 | 🔴 未解决 | **v4 M2.2** |
| **D5** | **dispatchClick硬编码** | 🔴 高 | 🔴 未解决 | **v4 M2.3** |
| **D6** | **手动dirty标记** | 🔴 高 | 🔴 未解决 | **v4 M2.1** |
| D7 | 单组件限定 | 🟡 中 | 🟡 未触及 | v4 M3 |
| D8 | ChangeQueue未集成 | 🟢 低 | 🟢 未触及 | 远期 |
| D9 | 全量渲染 | 🟢 低 | 🟢 未触及 | v4 M4 |
| D10 | 测试仅覆盖编译器 | 🟢 低 | 🟢 未触及 | 远期 |

---

## 二、v4 路线图总览

### 2.1 里程碑划分

```
v4 路线图
│
├── M2：响应式系统完善（v4 核心交付）★★★★★
│   ├── M2.1：自动 Dirty 标记（编译器插入替代码）
│   ├── M2.2：自动 getBindValue 生成
│   ├── M2.3：自动 dispatchClick 生成
│   ├── M2.4：v-model 双向绑定
│   └── M2.5：v-if 条件渲染
│
├── M3：组件生态与嵌套编译（架构升级）
│   ├── M3.1：嵌套组件编译
│   ├── M3.2：Props 传递
│   └── M3.3：Slot 插槽
│
└── M4：渲染层升级与工具链（远期优化）
    ├── M4.1：增量渲染（节点级 dirty + 区域裁剪）
    ├── M4.2：Direct2D 可行性评估
    └── M4.3：开发体验工具
```

### 2.2 优先级矩阵

| 里程碑 | 业务价值 | 技术复杂度 | AOT风险 | 改动规模 | 优先级 |
|--------|----------|------------|---------|----------|--------|
| M2.1 自动Dirty | 🔴 极高 | 🟡 中 | 🟢 低 | ~200行 | P0 |
| M2.2 自动getBindValue | 🔴 极高 | 🟢 低 | 🟢 低 | ~100行 | P0 |
| M2.3 自动dispatchClick | 🔴 极高 | 🟢 低 | 🟡 中 | ~100行 | P0 |
| M2.4 v-model | 🟡 高 | 🟡 中 | 🟡 中 | ~300行 | P1 |
| M2.5 v-if | 🟡 高 | 🟡 中 | 🟢 低 | ~300行 | P1 |
| M3.1 嵌套组件 | 🔴 极高 | 🔴 高 | 🔴 高 | ~800行 | P1 |
| M3.2 Props | 🟡 高 | 🟡 中 | 🔴 高 | ~400行 | P2 |
| M3.3 Slot | 🟡 中 | 🔴 高 | 🔴 高 | ~500行 | P2 |
| M4.1 增量渲染 | 🟡 中 | 🔴 高 | 🟢 低 | ~400行 | P3 |
| M4.2 Direct2D | 🟡 中 | 🔴 高 | 🟡 中 | ~300行 | P3 |
| M4.3 Dev工具 | 🟢 低 | 🟡 中 | N/A | ~300行 | P3 |

### 2.3 依赖关系

```
M2.1 (自动Dirty) ──→ M2.2 (自动getBindValue)
                 ──→ M2.3 (自动dispatchClick)
                              │
M2.2 ──→ M2.4 (v-model)     │
M2.2 ──→ M2.5 (v-if)        │
                 └───────────┘
                       │
                       └──→ M3 (组件生态)
                              │
                              └──→ M4 (渲染升级)
```

M2.1 是基础——所有后续里程碑都依赖编译器能够自动分析和插入代码的能力。

---

## 三、M2：响应式系统完善（v4 核心交付）

> **目标**：消除 D4-D6 三大运行时硬编码债务，引入 v-model/v-if 模板指令。
> **改动量**：~1,000 行新增/修改，主要涉及 `tools/sfc-compiler.php`、`main.php`、`src/Calculator.vue`
> **AOT 风险等级**：中等 — 编译器输出需通过 AOT 校验器

### 3.1 M2.1：自动 Dirty 标记（消除 D6）

#### 痛点

`Calculator.vue` 中 7 个业务方法（`reset`、`inputDigit`、`inputDecimal`、`inputOperator`、`calculate`、`backspace`、`handleButton`），每个方法末尾都显式写了 `$this->dirty = true`：

```
Calculator.vue:72,89,103,116,144,161,180 — 共 7 处手动 dirty
```

每新增一个修改状态的方法，开发者都必须记得添加。遗漏导致"点击按钮但界面不刷新"。

#### 设计

**方案A（推荐）**：编译器编译时分析 script 块代码，在包含属性赋值的方法末尾自动插入 `$this->dirty = true`。

实现步骤：

1. **新增 `tools/compiler/script-analyzer.php`**
   - 负责分析 `<script>` 块内容
   - 识别每个 `public function` 方法体
   - 检测方法体是否包含对组件属性的赋值（`$this->display`、`$this->expression` 等）
   - 如果包含赋值且未在 AOT 已注入标记，在方法末尾 `}` 前插入 `$this->dirty = true;`

2. **修改 `tools/sfc-compiler.php` 代码生成阶段**（Step 5，约第 160-182 行）
   - 在写入 script 块到 class body 之前，调用 `ScriptAnalyzer::injectDirty()`
   - 将处理后的代码写入 `Calculator.gen.php`

3. **从 `src/Calculator.vue` 移除手动标记**
   - 删除所有 `$this->dirty = true;` 行

**特殊情况处理**：

| 情况 | 处理方法 |
|------|----------|
| `__construct` | 不插入（构造函数不应标记 dirty） |
| `handleButton()` 调用子方法 | 不重复插入（子方法已标记） |
| `calculate()` 有多个 `return` | 在每个 return 前插入，而非仅末尾 |
| 已有 `$this->dirty = true` | 先移除再统一插入（避免重复） |
| 返回 `$value` 的方法 | 在 `return` 之前插入 |

#### 改动文件

| 文件 | 改动 |
|------|------|
| `tools/compiler/script-analyzer.php` | **新增**：脚本分析模块（~120行） |
| `tools/sfc-compiler.php:160-182` | **修改**：集成 dirty 自动插入（~30行） |
| `src/Calculator.vue:72,89,103,116,144,161,180` | **删除**：手动 `$this->dirty = true`（共8行） |
| `tests/sfc-compiler-test.php` | **新增**：自动 dirty 测试 3-5 项 |

#### 验收标准

- 编译生成的 `Calculator.gen.php` 所有变更方法含自动 dirty 标记
- `Calculator.vue` 源文件中不存在手动 `$this->dirty = true`
- 25 项现有单测 + 新增单测 100% 通过
- AOT 编译零错误
- 功能回归：所有按钮行为与 v3 一致

---

### 3.2 M2.2：自动 getBindValue 生成（消除 D4）

#### 痛点

`main.php:38-47` 中 `CalcRenderer::getBindValue()` 手写了 binding key → 组件属性的映射：

```php
// main.php:38-47 — 手写代码
public function getBindValue(string $bindKey): string {
    if ($bindKey === 'expression') { return $this->component->expression; }
    if ($bindKey === 'display')    { return $this->component->display; }
    return '';
}
```

模板中 `:bind="expression"` 和 `:bind="display"` 已经声明了绑定关系（`Calculator.vue:10,13`），编译器在 `template-parser.php:480-496` 中能收集到这些信息，但从未生成运行时映射代码。

#### 设计

核心思路：**编译器在布局生成阶段收集所有 bindKey → 自动生成 getBindValue 方法到组件类中**。

实现步骤：

1. **在 `template-parser.php` lower 阶段收集 bindKey 集合**
   - 已在 `lowerToLayout()` 中处理 `TextNode.bind`（约 480-496 行）
   - 新增返回所有唯一 bindKey 的数组

2. **在 `sfc-compiler.php` 代码生成阶段生成 getBindValue**
   - 根据收集的 bindKey 集合生成显式 if/else 映射（AOT 安全）
   - 写入 `Calculator.gen.php` 的类体中

3. **修改 `main.php:38-47`**
   - 删除手写映射
   - 改为调用 `$this->component->getBindValue($bindKey)`

**生成代码示例**（当前 Calculator.vue 的 2 个 binding）：

```php
// 自动生成到 Calculator.gen.php
public function getBindValue(string $bindKey): string {
    if ($bindKey === 'expression') { return $this->expression; }
    if ($bindKey === 'display')    { return $this->display; }
    return '';
}
```

#### AOT 安全

生成的 if/else 使用显式分支，不涉及 `$this->{$bindKey}` 变量属性访问，完全通过 AOT 校验（`aot-validator.php:72-75`）。

#### 改动文件

| 文件 | 改动 |
|------|------|
| `tools/compiler/template-parser.php:480-496` | **修改**：lower 阶段返回唯一 bindKey 集合 |
| `tools/sfc-compiler.php:160-182` | **修改**：生成 getBindValue 方法（~40行） |
| `main.php:38-47` | **简化**：委托到组件方法（~5行） |
| `tests/sfc-compiler-test.php` | **新增**：getBindValue 生成测试 |

#### 验收标准

- `Calculator.gen.php` 自动包含所有绑定键的 getBindValue 映射
- `main.php` 中不再有手写 if/else
- 新增第三个 `:bind="xxx"` 时只需修改 `.vue` 模板，`main.php` 无需改动
- AOT 编译零错误

---

### 3.3 M2.3：自动 dispatchClick 生成（消除 D5）

#### 痛点

`main.php:244-258` 中 `CalcApp::dispatchClick()` 手写了 handler 路由映射：

```php
// main.php:244-258 — 手写代码
public function dispatchClick(array $btn): void {
    $handler = $btn['handler'];
    $arg = $btn['arg'] ?? null;
    if ($handler === 'reset')           { $this->calc->reset(); }
    elseif ($handler === 'backspace')   { $this->calc->backspace(); }
    elseif ($handler === 'calculate')   { $this->calc->calculate(); }
    elseif ($handler === 'handleButton'){ $this->calc->handleButton($arg); }
}
```

模板中 `@click="reset"` 等被编译器解析为 `handler + arg` 存入布局数组（`template-parser.php:522-524`），但路由映射未自动生成。

#### 设计

核心思路：**编译器从 AST 收集所有 handler 名称，自动生成 `dispatchClick` 方法到生成的布局文件中**。

实现步骤：

1. **收集 handler 集合**
   - 在 `lowerToLayout()` 过程中（`template-parser.php:522-524`）已为每个按钮输出 handler
   - 收集所有唯一 handler 名称及其是否有 arg 参数

2. **在代码生成阶段生成 dispatchTo 函数**
   - 在 `CalculatorLayout_gen.php` 中生成独立的 `dispatchTo()` 函数（避免污染组件类）
   - 使用显式 if/else 分支（AOT 安全）
   - 编译期验证：如果 handler 名称对应的组件方法不存在，编译器报错

3. **简化 `main.php:243-258`**
   - 删除手写的 if/else
   - 改为调用生成的 `dispatchTo()` 函数

**生成代码示例**（当前 Calculator.vue 的 4 个 handler）：

```php
// 自动生成到 CalculatorLayout_gen.php
function dispatchTo(Calculator $calc, string $handler, ?string $arg): void {
    if ($handler === 'reset')           { $calc->reset(); }
    elseif ($handler === 'backspace')   { $calc->backspace(); }
    elseif ($handler === 'calculate')   { $calc->calculate(); }
    elseif ($handler === 'handleButton'){ $calc->handleButton($arg); }
}
```

**错误处理增强**：编译器在解析阶段验证 `@click` handler 是否对应 script 块中的方法名。如果模板中写了 `@click="sqrt"` 但 script 中无 `sqrt()` 方法，编译器应报错（带行号）。

#### 改动文件

| 文件 | 改动 |
|------|------|
| `tools/compiler/template-parser.php` | **修改**：lower 阶段返回唯一 handler 集合 + 验证 handler 方法存在 |
| `tools/sfc-compiler.php` | **修改**：生成 dispatchTo 函数（~50行） |
| `main.php:243-258` | **简化**：改为调用 dispatchTo（~5行） |
| `tests/sfc-compiler-test.php` | **新增**：dispatchTo 生成测试 + 未知 handler 报错测试 |

#### 验收标准

- `CalculatorLayout_gen.php` 自动包含覆盖所有 4 个 handler 的 `dispatchTo()` 函数
- `main.php` 中不再有手写 if/else
- 新增 `@click="sqrt"` 时，如果 sqrt 方法已存在则自动加入路由，否则编译器报错
- AOT 编译零错误

---

### 3.4 M2.4：v-model 双向绑定

#### 设计

**当前能力**：`:bind="prop"` 单向绑定（组件属性 → 显示文本）

**v-model 语义**：`v-model="prop"` 双向绑定（用户输入 → 自动更新属性 + 属性变更 → 自动刷新显示）

在计算器场景中，v-model 的实际价值：统一双向绑定的声明语义，编译器保证渲染和事件的一致性。

**实现分为两层**：

**第一层 — 模板语法支持（`template-parser.php`）**：

- 在 `parseText()` 方法中增加对 `v-model` 属性的解析
- 与 `:bind` 互斥检查（不能同时使用）
- AST 节点（`TextNode`）新增 `$vModel` 字段

**第二层 — 代码生成**：

- 编译器检测到 `v-model` 后：
  1. 自动生成 `:bind` 等效代码（单向渲染）
  2. 为此属性生成 setter 包装方法（`set{Prop}()`），setter 中自动 dirty
- 对于计算器场景，`v-model="display"` 等价于现有的 `:bind="display"` + 所有修改 display 的方法自动 dirty

#### 改动文件

| 文件 | 改动 |
|------|------|
| `tools/compiler/template-parser.php` | v-model 属性解析 |
| `tools/compiler/ast-nodes.php` | TextNode 增加 `$vModel` 字段 |
| `tools/sfc-compiler.php` | v-model 代码生成 |
| `tests/sfc-compiler-test.php` | v-model 解析 + 生成测试 |

#### 验收标准

- 模板支持 `v-model="prop"` 语法
- 编译器生成双向绑定代码（属性 setter + 脏标记）
- AOT 编译零错误

---

### 3.5 M2.5：v-if 条件渲染

#### 设计

**当前能力**：所有模板元素无条件渲染

**v-if 语义**：根据组件状态动态控制元素的渲染/隐藏

在计算器场景中可能的使用：
- `v-if="expression"` — 有表达式时显示（已通过空字符串实现）
- `v-if="hasDecimal"` — 控制小数点按钮状态

**实现步骤**：

1. **模板解析**：`template-parser.php` 识别 `v-if="condition"` 属性
   - 条件仅限于简单形式：`v-if="propName"`（布尔判断）或 `v-if="propName == 'value'"`（等值判断）
   - AOT 环境不支持运行时表达式求值，必须编译为静态判断代码

2. **AST 扩展**：元素节点新增 `$vIf` 字段（存条件表达式文本）

3. **布局数据生成**：v-if 转换为布局数组中的 `condition` 字段

4. **运行时判断**：`main.php` 渲染器（`CalcRenderer.render()` 约99-133行）遍历元素时检查 `condition` 字段

**生成代码示例**：

```php
// 布局数组中的条件字段
'condition' => ['prop' => 'expression', 'op' => 'truthy']

// main.php 渲染器中的判断逻辑
if (isset($el['condition'])) {
    if (!$this->evalCondition($el['condition'])) {
        continue; // v-if 不满足，跳过渲染
    }
}
```

`evalCondition()` 使用编译器生成的显式 if/else（类似 getBindValue），不涉及变量属性访问。

#### 改动文件

| 文件 | 改动 |
|------|------|
| `tools/compiler/template-parser.php` | v-if 属性解析（~50行） |
| `tools/compiler/ast-nodes.php` | 节点增加 `$vIf` 字段 |
| `tools/sfc-compiler.php` | v-if → 布局数组条件字段映射（~30行） |
| `main.php:99-133` | 增加条件跳过逻辑（~30行） |
| `tests/sfc-compiler-test.php` | v-if 解析 + 渲染测试 |

#### 验收标准

- 模板支持简单条件 v-if
- 生成的布局含 `condition` 字段
- 渲染器正确跳过不满足条件的元素
- AOT 编译零错误

---

## 四、M3：组件生态与嵌套编译

> **优先级**：P1-P2（低于 M2，架构升级）
> **复杂度**：高 — 需要编译器支持跨文件递归编译 + AOT 环境下组件实例化

### 4.1 设计方向

**核心约束**：AOT 环境不支持运行时动态 `include/require`、动态类实例化、反射。因此嵌套组件的"组件树"必须在编译期完全静态展开。

**编译期展开策略**（简化版）：

```
父 .vue 中使用 <my-child :prop="value" />
    │
    ├─→ 编译器查组件注册表 → 找到 MyChild.vue
    ├─→ 递归编译 MyChild.vue → MyChild.gen.php
    ├─→ 将子组件的布局元素合并到父布局数组中（内联展开）
    └─→ 在父组件生成代码中实例化子组件对象
```

**组件注册表** — 建议通过 `project.yml` 扩展：

```yaml
components:
  MyDisplay: src/components/MyDisplay.vue
  ButtonGrid: src/components/ButtonGrid.vue
```

**简化限制**（v4 M3 不追求完整 Vue 组件模型）：

- 仅支持一层嵌套（子组件不再嵌套孙组件）
- Props 通过编译期常量绑定（无运行时响应式跟踪）
- Slot 编译期内联替换（无运行时 slot 动态切换）
- 子组件事件回调通过编译期生成的显式方法调用

### 4.2 改动文件预估

| 步骤 | 文件 | 改动 |
|------|------|------|
| 组件注册 | `project.yml` | 新增 `components:` 字段 |
| 注册表解析 | `tools/compiler/component-registry.php` | **新增** |
| 递归编译 | `tools/sfc-compiler.php` | 支持 `--components` 参数 |
| 未知标签处理 | `tools/compiler/template-parser.php` | 查注册表判定自定义组件 |
| AOT 校验扩展 | `tools/compiler/aot-validator.php` | 增加组件引用检查 |

---

## 五、M4：渲染层升级与工具链

> **优先级**：P3（远期规划，v4 可能仅做评估）
> **状态**：设计方向定义，不要求完整交付

### 5.1 M4.1：增量渲染（节点级 dirty + 区域裁剪）

**动机**：当前全量渲染（22 个元素全部重绘）对计算器场景够用。但 M3 嵌套组件后如果元素增至 50+，需要优化。

**方案**：
- 将 `ReactiveComponent::$dirty` 从全局布尔改为节点级标识
- 渲染器仅重绘 dirty 元素
- 编译器为每个元素分配唯一 ID，生成 dirty 映射表

### 5.2 M4.2：Direct2D 可行性评估

**目标**：评估 Direct2D 替代 GDI 的收益与代价

**需评估维度**：
- 绘制原语覆盖率（抗锯齿、圆角、透明度、颜色空间）
- 与 CSS 映射表的兼容性
- AOT 编译兼容性
- 性能对比（CPU 绘制 vs GPU 加速）
- 文件体积影响

### 5.3 M4.3：开发体验工具

- Dev Server 热重载：检测 `.vue` 变更 → 自动编译 → 重启 exe
- PHP→C++ 错误映射：将 AOT 编译错误关联回源文件行号
- `project.yml` 增强：`watch`、`d2d` 等新字段

---

## 六、验证计划

### 6.1 每个里程碑的验收基线

每个 M2 子里程碑交付后，必须通过以下基线检查：

| # | 验证项 | 方法 |
|---|--------|------|
| 1 | `tests/sfc-compiler-test.php` 全部通过 | `php tests/sfc-compiler-test.php` |
| 2 | `tests/verify-layout.php` 布局一致 | `php tests/verify-layout.php` |
| 3 | AOT 编译零错误 | `swoole_compiler.exe project.yml -f` |
| 4 | 运行时功能回归 | 手动测试：18个按钮、Error显示、退出 |
| 5 | 代码体积不显著增加 | ±10% 可接受 |
| 6 | AOT 校验器零警告 | `AotValidator::validate()` 返回空 |

### 6.2 M2 追加测试项

| 里程碑 | 新增测试 |
|--------|----------|
| M2.1 | 自动 dirty 插入（正常/空方法/多return方法/已标记方法） |
| M2.2 | getBindValue 生成（2键/0键/新增键无需手动改） |
| M2.3 | dispatchTo 生成（4handler/有参/无参/未知handler报错） |
| M2.4 | v-model 属性解析 + 与:bind互斥检查 |
| M2.5 | v-if 条件渲染（true跳过/true渲染/false跳过） |

### 6.3 放弃条件

以下情况应放弃或推迟相关里程碑：

- AOT 编译失败且根因在 AOT 编译器本身（非代码生成问题）
- 回归失败确认非编译器输出差异
- 代码体积增长超过 30%

---

## 七、关键文件索引

### 7.1 编译器模块（`tools/compiler/`）

| 文件 | 行数 | 角色 | v4 改动 |
|------|------|------|---------|
| `template-parser.php` | 680 | 递归下降解析器 | M2.2/M2.3/M2.4/M2.5 |
| `ast-nodes.php` | 153 | AST 节点定义 | M2.4/M2.5 扩展 |
| `css-mappings.php` | 210 | CSS→GDI 映射表 | 基本不变 |
| `aot-validator.php` | 169 | AOT 兼容性校验 | M2.3 补充规则 |
| `script-analyzer.php` | 待建 | 脚本分析方法注入 | **M2.1 新建** |
| `component-registry.php` | 待建 | 组件注册表 | M3 新建 |

### 7.2 编译器主入口

| 文件 | 行数 | v4 改动 |
|------|------|---------|
| `tools/sfc-compiler.php` | 210 | M2 所有生成逻辑的核心改动点 |

### 7.3 运行时文件

| 文件 | 行数 | 角色 | v4 改动 |
|------|------|------|---------|
| `main.php` | 291 | 入口 + CalcApp + CalcRenderer | M2.2 简化 getBindValue、M2.3 简化 dispatchClick、M2.5 增加条件判断 |
| `src/Calculator.vue` | 215 | SFC 源文件 | M2.1 删除手动 dirty |
| `src/Calculator.gen.php` | 174 | 生成组件类 | M2 重新生成 |
| `src/CalculatorLayout_gen.php` | 296 | 生成布局数据 | M2.2/M2.3 新增内容 |
| `src/ReactiveComponent.php` | 34 | 响应式基类 | 基本不变 |
| `src/ChangeQueue.php` | 56 | 变更队列 | 不变 |

### 7.4 测试文件

| 文件 | 行数 | v4 改动 |
|------|------|---------|
| `tests/sfc-compiler-test.php` | 365 | 所有里程碑新增测试 |
| `tests/verify-layout.php` | 72 | 回归验证 |

### 7.5 配置文件

| 文件 | v4 改动 |
|------|---------|
| `project.yml` | M3 新增 `components:` 字段 |

---

## 八、总结

| 维度 | 内容 |
|------|------|
| **v4 核心目标** | 消除 D4-D6 三大运行时硬编码债务，使 SFC 编译器真正接管运行时映射的自动生成 |
| **v4 主要产出** | M2.1 自动 Dirty、M2.2 自动 getBindValue、M2.3 自动 dispatchClick、M2.4 v-model、M2.5 v-if |
| **v4 设计原则** | "声明式模板 → 编译期自动生成运行时代码"，不依赖任何 AOT 不安全特性 |
| **与 v3 的关系** | v3 M1 夯实编译器基础（AST + 校验），v4 在此基础上构建响应式系统 |
| **关键风险** | AOT 编译器对生成代码的特殊行为、代码体积膨胀 |
| **放弃条件** | AOT 编译失败且无法通过调整生成代码解决 |

---

*VueCalc v4.0 设计规划 · 基于 v3 M1 编译器健壮化基线 · 2026-05*
