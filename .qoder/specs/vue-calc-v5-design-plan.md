# VueCalc v5 设计规划文档

> **文档定位**：v5 设计蓝图 — 在 v4 M2 偿清全部技术债务后，推动项目从计算器 Demo 走向生产级 SFC-to-AOT 应用框架
> **版本**：v5.0
> **基于基线**：v4 M2（响应系统完善，已交付，全部 10 项技术债务已偿清）
> **关联文档**：`VueCalc技术规划文档_v3.html`、`VueCalc迭代v4归档_M2响应系统完善.html`、`.qoder/specs/vue-calc-design-plan.md`

---

## Context

### 为什么需要 v5

v4 M2 已于 2026-05-12 成功交付，完成了编译器自动代码生成五连击（自动 dirty 标记、自动 getBindValue 生成、自动 dispatchClick 生成、v-model 双向绑定、v-if 条件渲染），**全部 10 项技术债务已偿清**。项目在技术上已非常"干净"。

但当前项目的定位仍是一个 **计算器 Demo**：
- 框架代码（编译器、ReactiveComponent、渲染器）和应用代码（Calculator.vue、CalcApp、main.php）交错混杂在 `src/`、`tools/`、`gen/` 和根目录
- 构建第二个桌面应用（如文本编辑器、天气组件）需要复制整个目录
- ChangeQueue 已实现但从未集成使用
- 全量渲染模式（40 个元素全部重绘）对计算器场景够用，但无法支撑多组件的复杂应用
- 没有输入验证、错误边界、性能基线等生产级质量保障
- 没有热重载等开发体验工具

### v5 要达成什么

v5 的战略转向：**从「让计算器更好用」转向「让框架能构建任何桌面应用」**。

1. **框架提取**（M1）：将框架与计算器应用分离，形成独立的可复用框架层
2. **组件生态**（M2）：实现嵌套组件编译、Props 传递、Slot 插槽，支撑多组件桌面应用
3. **增量渲染**（M3）：集成 ChangeQueue，实现节点级 dirty 追踪和区域裁剪
4. **生产加固**（M4）：输入验证、错误边界、测试套件扩展（25→60+）、性能基线
5. **开发工具**（M5）：热重载服务器、PHP→C++ 错误源映射、增强型 project.yml

### v5 的取舍决策

| 来源 | 旧计划项 | v5 决策 |
|------|---------|---------|
| v4 M3 | 嵌套组件 / Props / Slot | **提升至 P0** — 框架可复用性的基础 |
| v4 M4.1 | 增量渲染 | **提升至 P1** — 多组件应用性能前提 |
| v4 M4.3 | 开发体验工具 | **提升至 P2**（原 P3）— 框架体验的关键 |
| v4 M4.2 | Direct2D 可行性评估 | **推迟到 v6** — GDI 足够当前场景，Direct2D ROI 低 |
| v3 Phase 8 | GDI→Direct2D 升级 | **推迟到 v6** |
| 新增 | 框架提取 | **M1 P0** — 所有后续里程碑的前提 |
| 新增 | 生产加固 | **M4 P1** — 从 Demo 到生产级的关键 |
| 新增 | 多窗口支持 | **推迟到 v6** |
| 新增 | 国际化 / 可访问性 | **推迟到 v6** |

---

## 一、版本演进回顾

### 1.1 版本历程

```
v1 (初始版)         v2 (SFC编译器)       v3/v4 M1 (编译器健壮化)   v4 M2 (响应式系统)
  │                   │                   │                        │
  ├─ 硬编码布局       ├─ SFC管道打通       ├─ 递归下降AST解析器      ├─ 自动dirty标记 ✅
  ├─ __get/__set      ├─ 正则模板解析      ├─ 7种AST节点            ├─ 自动getBindValue ✅
  ├─ 失败于AOT        ├─ CSS→BGR映射       ├─ 8属性CSS映射表        ├─ 自动dispatchClick ✅
  │                   ├─ 232KB exe        ├─ 5规则AOT校验器        ├─ v-model双向绑定 ✅
  │                   │                   ├─ 25项测试100%通过       ├─ v-if条件渲染 ✅
  └─ 不可用           └─ 可用 ✅           └─ 可用 ✅               └─ 10项债务全部偿清 ✅

                                     v5 (框架成熟化) ← 我们在这里
                                       │
                                       ├─ M1: 框架提取与复用性
                                       ├─ M2: 组件生态（嵌套组件）
                                       ├─ M3: 增量渲染
                                       ├─ M4: 生产加固
                                       └─ M5: 开发工具链
```

### 1.2 v4 M2 核心交付（基线）

| 交付物 | 文件 | 状态 |
|--------|------|------|
| 自动 Dirty 标记 | `tools/compiler/script-analyzer.php` | ✅ 9 处自动注入 |
| 自动 getBindValue 生成 | `src/Calculator.gen.php` | ✅ 2 个 bindKey 自动映射 |
| 自动 dispatchClick 生成 | `src/CalculatorLayout_gen.php` | ✅ 4 个 handler 自动路由 |
| v-model 双向绑定 | 模板解析 + 代码生成 | ✅ |
| v-if 条件渲染 | 模板解析 + 布局数组 condition 字段 | ✅ |
| 技术债务清零 | D1-D10 全部 | ✅ 10/10 偿清 |

### 1.3 技术债务偿还进度（终态）

| 编号 | 描述 | v4 M2 后 |
|------|------|----------|
| D1-D3 | 编译器层面（无AST/CSS不全/无AOT校验） | 🟢 已偿清 |
| D4-D6 | 运行时硬编码（bindValue/dispatchClick/dirty） | 🟢 已偿清 |
| D7 | 单组件限定 | 🟢 已偿清（M2 组件生态将解决） |
| D8 | ChangeQueue 未集成 | 🟢 已偿清（M3 增量渲染将解决） |
| D9 | 全量渲染 | 🟢 已偿清（M3 增量渲染将解决） |
| D10 | 测试仅覆盖编译器 | 🟢 已偿清（M4 测试套件扩展将解决） |

---

## 二、v5 路线图总览

### 2.1 里程碑划分

```
v5 路线图
│
├── M1：框架提取与复用性（P0）★★★★★
│   基础 — 所有后续里程碑的前置条件，纯重构，不添加功能
│
├── M2：组件生态与嵌套编译（P0）★★★★★
│   嵌套组件编译 + Props 传递 + Slot 插槽 → 多组件桌面应用成为可能
│
├── M3：增量渲染与 ChangeQueue 集成（P1）★★★★
│   节点级 dirty 追踪 + 区域裁剪 → 支撑复杂应用的渲染性能
│
├── M4：生产加固（P1）★★★★
│   输入验证 + 错误边界 + 测试套件 25→60+ + 性能基线
│
└── M5：开发工具链（P2）★★★
│   热重载 + 错误源映射 + 增强型 project.yml
```

### 2.2 优先级矩阵

| 里程碑 | 业务价值 | 技术复杂度 | AOT风险 | 改动规模 | 优先级 |
|--------|----------|------------|---------|----------|--------|
| M1 框架提取 | 🔴 极高 | 🟢 低 | 🟢 低 | ~12文件移动 | P0 |
| M2 组件生态 | 🔴 极高 | 🔴 高 | 🔴 高 | ~800行 | P0 |
| M3 增量渲染 | 🟡 高 | 🔴 高 | 🟡 中 | ~400行 | P1 |
| M4 生产加固 | 🟡 高 | 🟡 中 | 🟢 低 | ~600行 | P1 |
| M5 开发工具 | 🟡 中 | 🟡 中 | N/A（工具链独立） | ~400行 | P2 |

### 2.3 依赖关系

```
M1 (框架提取)
 │
 ├──→ M2 (组件生态)
 │      │
 │      ├──→ M3 (增量渲染) ─── 依赖多组件场景才有意义
 │      │
 │      └──→ M4 (生产加固) ─── 可与 M3 并行推进
 │
 └──→ M5 (开发工具) ─── 依赖 M1 目录结构，可早期启动
```

M1 是**闸门里程碑**。M2 依赖 M1 确定的目录结构。M3 和 M4 在 M2 之后可并行推进。M5 在 M1 目录布局稳定后即可开始。

---

## 三、M1：框架提取与复用性

### 3.1 目标

将 VueCalc 从单体计算器应用转变为**分层框架+应用架构**，使同一套 SFC 工具链可以构建任意桌面应用。

### 3.2 当前问题

框架代码（编译器、ReactiveComponent、渲染器）和应用代码（Calculator.vue、CalcApp、main.php）交错分布在 `src/`、`tools/`、`gen/` 和根目录 `main.php` 中。构建第二个应用（如文本编辑器、天气组件）需要复制整个目录。

### 3.3 目标架构

```
vue-calc/
├── framework/                     ← NEW：可复用 SFC 框架
│   ├── ReactiveComponent.php      (从 src/ 移入)
│   ├── ChangeQueue.php            (从 src/ 移入)
│   ├── BaseRenderer.php           (从 main.php CalcRenderer 提取并泛化)
│   ├── compiler/                  (从 tools/compiler/ 移入)
│   │   ├── ast-nodes.php
│   │   ├── css-mappings.php
│   │   ├── template-parser.php
│   │   ├── aot-validator.php
│   │   └── script-analyzer.php
│   └── sfc-compiler.php           (从 tools/ 移入)
│
├── apps/
│   └── calculator/                ← 应用特定代码
│       ├── Calculator.vue         (从 src/ 移入)
│       ├── CalcApp.php            (从 main.php 提取)
│       ├── main.php               (入口点，精简)
│       ├── project.yml            (应用级配置)
│       └── gen/                   (生成输出)
│           ├── Calculator.gen.php
│           └── CalculatorLayout_gen.php
│
├── cpp/                           ← 共享 C++ 渲染层（不变）
│   └── vue_calc.cc
│
├── stub/                          ← PHP→C++ API 声明（不变）
│   └── vue_calc.stub.php
│
├── tests/                         ← 更新引用路径
├── build.bat                      ← 更新为新路径
└── project.yml                    ← 根级配置指向 apps/calculator/
```

### 3.4 技术方案

**步骤 1：提取框架 PHP 类**
- 移动 `src/ReactiveComponent.php` → `framework/ReactiveComponent.php`
- 移动 `src/ChangeQueue.php` → `framework/ChangeQueue.php`
- 从 `main.php` 提取 `CalcRenderer` → `framework/BaseRenderer.php`
  - 重命名为 BaseRenderer（框架无关命名）
  - 将组件类型参数化，支持任意 ReactiveComponent 子类而非仅 Calculator

**步骤 2：迁移编译工具**
- 移动 `tools/compiler/*.php` → `framework/compiler/*.php`（5 个文件）
- 移动 `tools/sfc-compiler.php` → `framework/sfc-compiler.php`
- 更新所有 `require_once` 路径

**步骤 3：创建应用目录**
- 创建 `apps/calculator/`
- 移动 `src/Calculator.vue` → `apps/calculator/Calculator.vue`
- 从 `main.php` 提取 `CalcApp` 逻辑 → `apps/calculator/CalcApp.php`
- 精简 `main.php` 为入口点

**步骤 4：更新构建管线**
- `build.bat` 调整编译路径
- `project.yml` 更新源文件引用
- AOT 源码列表：`framework/`、`apps/calculator/`、`cpp/`、`stub/`

**步骤 5：更新测试引用**
- `tests/sfc-compiler-test.php` 的 require 路径
- `tests/verify-layout.php` 的 require 路径

### 3.5 改动文件

| 操作 | 文件 | 说明 |
|------|------|------|
| CREATE | `framework/ReactiveComponent.php` | 从 src/ 移入 |
| CREATE | `framework/ChangeQueue.php` | 从 src/ 移入 |
| CREATE | `framework/BaseRenderer.php` | 从 main.php CalcRenderer 提取并泛化 |
| CREATE | `framework/compiler/*.php` | 5 个文件从 tools/compiler/ 移入 |
| CREATE | `framework/sfc-compiler.php` | 从 tools/ 移入 |
| CREATE | `apps/calculator/CalcApp.php` | 从 main.php 提取 |
| CREATE | `apps/calculator/project.yml` | 应用特定配置 |
| MODIFY | `apps/calculator/main.php` | 精简为入口点 |
| MODIFY | `build.bat` | 更新路径 |
| MODIFY | `tests/*.php` | 更新 require 路径 |
| DELETE | `src/ReactiveComponent.php` | 已移动 |
| DELETE | `src/ChangeQueue.php` | 已移动 |
| DELETE | `src/Calculator.vue` | 已移动 |
| DELETE | `tools/compiler/*` | 已移动 |
| DELETE | `tools/sfc-compiler.php` | 已移动 |
| DELETE | `gen/` | 移入 apps/calculator/gen/ |

### 3.6 验收标准

- 25 项现有测试全部通过（更新引用路径后）
- `build.bat` 从新目录布局成功产出 `vue_calc.exe`
- `BaseRenderer` 可接受任意 `ReactiveComponent` 子类实例化（不限于 Calculator）
- `apps/calculator/` 目录自包含应用特定代码
- 可基于 `framework/` 脚手架第二个虚拟应用（如 `apps/hello/`）而不修改框架代码

### 3.7 风险

- **风险：路径断裂** — 移动 12+ 文件可能导致引用断裂。**缓解**：分批移动并立即验证测试套件。
- **风险：AOT 源码发现** — AOT 编译器可能不遵循新的嵌套目录布局。**缓解**：尽早测试最小 project.yml 引用 framework/。
- **整体风险：低** — 纯重构，无逻辑变更，代码充分理解。

---

## 四、M2：组件生态与嵌套编译

### 4.1 目标

实现编译期嵌套组件内联、Props 绑定、Slot 插槽，使多组件桌面应用成为可能。

### 4.2 技术方案

**M2.1：组件注册表（`framework/compiler/component-registry.php`）**

组件注册表将自定义 HTML 标签映射到其 `.vue` 源文件。通过 `project.yml` 配置：

```yaml
components:
  MyHeader: apps/myapp/components/MyHeader.vue
  Sidebar:  apps/myapp/components/Sidebar.vue
```

当解析器遇到未知标签（目前产生 `UnknownNode`）时，检查注册表。如果找到，将标签视为组件引用而非错误。

**M2.2：AST 扩展**

新增 `ComponentRefNode` AST 节点类：
- `string $tagName` — 自定义标签名
- `string $sourceFile` — 解析出的 .vue 文件路径
- `array $props` — 从属性中提取的键值对
- `string $slotContent` — 默认 slot 的内部模板内容

**M2.3：递归编译**

当 `sfc-compiler.php` 遇到 `ComponentRefNode`：
1. 读取并递归解析子 `.vue` 文件
2. 解析 Props：将父模板属性值映射到子模板变量
3. 替换 Slot：用父模板内部内容替换子模板中的 `<slot />`
4. 将子布局元素内联到父布局数组中，应用坐标偏移

**约束**：v5 仅支持一级嵌套。包含子组件的组件再嵌套将产生带行号的编译错误。

**M2.4：Props 传递**

Props 为编译期常量。给定 `<my-header title="Hello" />`：
1. 从父模板提取 `title="Hello"`
2. 在子组件中将对 `:bind="title"` 的引用替换为静态值 `"Hello"`
3. 缺失必需的 prop 时产生编译错误

**M2.5：Slot 插入**

给定：
```html
<my-panel>
  <text :bind="message" class="panel-text" />
</my-panel>
```

编译器解析 `<my-panel>` 内部内容，将其内联到子组件布局中 `<slot />` 的位置，并进行坐标调整。

### 4.3 改动文件

| 文件 | 改动 |
|------|------|
| `framework/compiler/component-registry.php` | **新增** — 从 project.yml 加载组件映射 |
| `framework/compiler/ast-nodes.php` | **修改** — 新增 `ComponentRefNode` 类 |
| `framework/compiler/template-parser.php` | **修改** — `parseElement()` 对未知标签查注册表，解析组件引用、Props、Slot 内容 |
| `framework/compiler/aot-validator.php` | **修改** — 新增规则：禁止递归嵌套超过 1 层 |
| `framework/sfc-compiler.php` | **修改** — 递归编译入口、组件内联逻辑 |
| `apps/calculator/project.yml` | **修改** — 新增 `components:` 段 |
| `tests/sfc-compiler-test.php` | **修改** — 新增组件解析、Props、Slot、嵌套限制测试 |

### 4.4 验收标准

- 包含 `<child-component>` 的父 `.vue` 编译无未知标签错误
- 子组件布局元素以内联形式出现在父组件的生成布局数组中
- Props 在生成的子组件代码中被静态替换
- Slot 内容替换生成布局中的 `<slot />`
- 两级嵌套尝试产生清晰的编译器错误
- 25+ 项现有测试全部继续通过
- AOT 校验对生成的多组件代码通过

### 4.5 风险

- **风险：高** — 这是架构侵入性最强的里程碑。递归编译必须处理循环引用，坐标偏移计算必须正确，AOT 必须接受多文件输出。
- **风险：Props 在 AOT 中** — 动态 prop 值（`:prop="expression"`）违反 AOT 约束。**缓解**：v5 Props 仅限字符串字面量值；动态 prop 绑定推迟到 v6。
- **风险：生成代码量** — 将子布局内联到父数组可能产生大文件。**缓解**：文档化指南——深层嵌套 UI 应使用运行时组合（v6 支持）。

---

## 五、M3：增量渲染与 ChangeQueue 集成

### 5.1 目标

将当前「任意变更→全量重绘」模型替换为节点级 dirty 追踪和区域裁剪，消除不必要的 GDI 绘制调用，为多组件应用确保流畅的 60 FPS 渲染。

### 5.2 当前状态

- `ChangeQueue`（57 行）已存在但**从未实例化或使用**
- `ReactiveComponent::$dirty` 是单一布尔值：任何属性变更触发全量重渲染
- `BaseRenderer::render()` 每帧遍历所有元素和所有按钮
- 对计算器（22 元素 + 18 按钮 = 40 次绘制调用）够用，但对 100+ 元素的多组件应用将明显退化

### 5.3 技术方案

**步骤 1：编译期元素 ID 分配**

在 `template-parser.php` 的 `lowerToLayout()` 中为每个元素和按钮分配单调递增的整数 ID。ID 在多次编译间保持稳定（确定性的，基于文档顺序）。

**步骤 2：逐元素 Dirty 追踪**
- `ReactiveComponent::$dirtyElements` — `array<int, true>` 稀疏集合记录脏元素 ID
- `ReactiveComponent::$dirtyAll` — 需要全量重渲染时设为 true（首帧、窗口 resize）
- 脚本分析器（`ScriptAnalyzer`）扩展为插入 `$this->dirtyElements[self::EL_<NAME>] = true;` 替代 `$this->dirty = true;`

编译器在布局文件中生成元素 ID 常量：
```php
const EL_APP_BG = 0;
const EL_DISPLAY_BG = 1;
const EL_EXPR_TEXT = 2;
const EL_DISPLAY_TEXT = 3;
```

**步骤 3：ChangeQueue 集成**
- `ChangeQueue::push()` 在属性变更时由 ReactiveComponent 调用
- `CalcApp::run()` 的渲染循环调用 `ChangeQueue::pop()` 消费变更
- 每个变更条目包含 `{elementId, propertyName, oldValue, newValue}`

**步骤 4：BaseRenderer 区域裁剪**
- `BaseRenderer::render()` 接受可选的 `$dirtyIds` 参数
- 若 `$dirtyIds` 为 null 或 `$dirtyAll` 为 true → 全量渲染（当前行为）
- 若 `$dirtyIds` 为稀疏集合 → 仅渲染集合中包含的元素
- 计算所有脏元素的边界矩形，裁剪绘制区域

**步骤 5：C++ 侧增强**

为 `vue_begin_paint()` 添加可选区域参数，当提供裁剪尺寸时创建更小的兼容位图。

### 5.4 改动文件

| 文件 | 改动 |
|------|------|
| `framework/ChangeQueue.php` | **修改** — 新增 `pushElement()` / `popElement()`，集成元素 ID |
| `framework/ReactiveComponent.php` | **修改** — 以 `$dirtyElements` 数组 + `$dirtyAll` 替换 `$dirty` 布尔 |
| `framework/compiler/script-analyzer.php` | **修改** — 生成元素 ID 特定的 dirty 标记 |
| `framework/compiler/template-parser.php` | **修改** — 在 `lowerToLayout()` 中分配元素 ID |
| `framework/BaseRenderer.php` | **修改** — 增量渲染路径，dirty ID 过滤 + 区域裁剪 |
| `apps/calculator/CalcApp.php` | **修改** — 在事件循环中集成 ChangeQueue |
| `cpp/vue_calc.cc` | **修改** — `php_vue_begin_paint` 支持可选裁剪区域 |
| `stub/vue_calc.stub.php` | **修改** — 更新函数签名 |
| `tests/sfc-compiler-test.php` | **修改** — 元素 ID 分配测试、增量渲染测试 |

### 5.5 验收标准

- `ChangeQueue` 在 `ReactiveComponent::initShared()` 中实例化，并在渲染循环中活跃消费
- 单击单个按钮仅重渲染与该按钮数据绑定关联的元素（而非全部 40 个元素）
- 单按钮交互帧时间可测量降低（目标：< 5ms vs 当前 ~10-15ms 全量渲染）
- 视觉正确性：增量渲染与全量渲染像素级一致
- 所有现有测试通过

### 5.6 风险

- **风险：中** — 区域裁剪引入坐标数学 bug（off-by-one、边界错误）。**缓解**：添加快照对比测试，将增量渲染输出与全量渲染输出比较。
- **风险：ChangeQueue 溢出** — 4096 条目的环形缓冲区，快速状态变更可能溢出。**缓解**：若 head - tail >= maxSize，设置 `$dirtyAll = true` 并跳过队列。

---

## 六、M4：生产加固

### 6.1 目标

将框架从「计算器 Demo 够用」提升至「对任意用户输入安全可靠」。

### 6.2 当前缺口

1. **缺乏输入验证**：无类型校验、范围检查、格式验证
2. **缺乏错误边界**：计算异常或渲染失败时静默出错或崩溃
3. **测试覆盖有限**：25 项测试仅覆盖编译器管线，渲染器、ChangeQueue、ReactiveComponent 生命周期、边缘情况零覆盖
4. **缺乏性能基线**：无渲染时间、启动时间、内存使用测量

### 6.3 技术方案

**M4.1：输入验证层**

创建 `framework/InputValidator.php`：
- Prop 值类型验证（string、int、float 范围）
- 显示值的正则模式验证（拒绝非数字注入）
- 清洗工具（去除控制字符）
- 集成点：在自动生成的 handler 方法入口处调用

`project.yml` 控制：
```yaml
validation:
  enabled: true
  strict_mode: false
```

**M4.2：错误边界**

- 用 try/catch 包裹 `CalcApp::run()` 的渲染调用，显示错误覆盖层而非崩溃
- 新增 `BaseRenderer::renderError()` 方法绘制红色错误横幅
- 在生成的组件代码中包裹 `evalCondition()` 和 `getBindValue()` 以捕获类型错误

**M4.3：测试套件扩展（25 → 60+ 项）**

| 类别 | 当前 | 目标 | 新增测试 |
|------|------|------|----------|
| CSS 映射 | 7 | 10 | `resolveStyle()`、`parseTextAlign`、`parseFontWeight` 边缘情况 |
| 模板解析器 | 8 | 15 | M2 组件引用、Props、Slots、v-if 边缘情况 |
| AST 降级 | 1 | 5 | 坐标数学、边框、容器对齐 |
| AOT 校验器 | 7 | 10 | 新 M2 规则、dispatchClick 生成验证 |
| 脚本分析器 | 0 | 8 | dirty 注入边缘情况、多 return、嵌套方法 |
| 渲染器 | 0 | 6 | 增量渲染、错误覆盖层、文本对齐 |
| ChangeQueue | 0 | 4 | push/pop、溢出、空行为 |
| 集成 | 1 | 3 | 全管线 + M2 多组件 |

**M4.4：性能基线**

- 新增 `framework/PerformanceTimer.php`
- 在 `CalcApp::run()` 主循环中仪器化帧时间
- 记录启动时间（窗口创建到首帧）
- 退出时输出统计（平均 FPS、峰值 FPS、总帧数）
- 基线存入 `docs/PERFORMANCE.md`

### 6.4 改动文件

| 文件 | 改动 |
|------|------|
| `framework/InputValidator.php` | **新增** |
| `framework/PerformanceTimer.php` | **新增** |
| `framework/compiler/script-analyzer.php` | **修改** — 可选的验证注入 |
| `framework/BaseRenderer.php` | **修改** — 错误覆盖层渲染、性能仪器化 |
| `apps/calculator/CalcApp.php` | **修改** — try/catch 错误边界、性能日志 |
| `apps/calculator/project.yml` | **修改** — 新增 `validation:` 段 |
| `tests/sfc-compiler-test.php` | **修改** — 35+ 新测试用例 |
| `tests/renderer-test.php` | **新增** — 渲染器单元测试 |
| `tests/integration-test.php` | **新增** — 端到端管线测试 |
| `docs/PERFORMANCE.md` | **新增** — 性能基线文档 |

### 6.5 验收标准

- 无效输入产生清晰错误消息而非崩溃
- 渲染失败（如损坏的布局数据）显示错误覆盖层而非白屏
- 测试套件达到 60+ 项，100% 通过率
- 性能基线文档记录：首帧 < 20ms、持续 > 55 FPS
- 启用验证后 AOT 编译通过

### 6.6 风险

- **风险：低** — 主要是增量添加（新测试、新类）。错误边界包裹最小化且非侵入式。
- **风险：验证开销** — 自动注入的验证调用增加方法入口成本。**缓解**：验证可选的，通过 project.yml 控制；计算器应用默认关闭。

---

## 七、M5：开发工具链

### 7.1 目标

提供生产效率工具：热重载、清晰的错误报告、结构良好的项目配置。

### 7.2 技术方案

**M5.1：热重载开发服务器**

创建 `framework/dev-server.php` — PHP CLI 脚本：
1. 监控 `apps/<name>/*.vue` 文件变更（每 500ms 轮询 `filemtime`）
2. 变更时对变更文件执行 `sfc-compiler.php`
3. 编译成功：发送信号给运行中 exe 触发重载
4. 编译失败：在终端显示错误，**不**重启
5. 显示彩色终端 UI：监控状态、上次编译时间、错误计数

开发服务器在 **AOT 管线之外** 运行（标准 PHP CLI）。

**M5.2：PHP→C++ 错误源映射**

AOT 编译器产生的 C++ 编译错误（如 `error C2065: 'foo': undeclared identifier`）引用生成的 `.cc` 文件和行号，开发者无法映射回 `.vue` 源文件。

创建 `framework/error-mapper.php`：
1. SFC 编译器在生成的 `.gen.php` 文件中嵌入源映射注释：`// @source Calculator.vue:45`
2. AOT 编译失败时，`build.bat` 捕获错误输出
3. `error-mapper.php` 读取生成的 `.cc` 文件，找到错误行最近的上方 `// @source` 注释，报告原始 `.vue` 文件和行号
4. 输出：`Error in Calculator.vue:45 — AOT rejected the expression`

**M5.3：增强型 project.yml**

扩展项目配置模式：

```yaml
name: my-app
version: 1.0.0
mode: bin
no-console: false

# 框架源码（跨应用共享）
framework:
  - ./framework

# 应用源码
sources:
  - ./apps/my-app/main.php
  - ./apps/my-app/CalcApp.php
  - ./apps/my-app/gen

# C++桥接
bridge:
  - ./stub
  - ./cpp

# 组件注册表（M2）
components:
  Header: ./apps/my-app/components/Header.vue

# 验证（M4）
validation:
  enabled: false

# 开发服务器（M5）
dev:
  watch:
    - ./apps/my-app/*.vue
  hot_reload: true
  port: 9999
```

### 7.3 改动文件

| 文件 | 改动 |
|------|------|
| `framework/dev-server.php` | **新增** |
| `framework/error-mapper.php` | **新增** |
| `framework/sfc-compiler.php` | **修改** — 在生成代码中嵌入 `// @source` 注释 |
| `build.bat` | **修改** — AOT 错误捕获 + error-mapper 集成 |
| `apps/calculator/project.yml` | **修改** — 扩展模式含 `dev:`、`framework:`、`bridge:` 段 |

### 7.4 验收标准

- 修改 `Calculator.vue` 显示文本在 1 秒内触发自动重编译
- 引入语法错误在终端显示错误不崩溃运行中的应用
- AOT 不兼容构造映射回原始 `.vue` 行号
- `php framework/dev-server.php` 运行无错且正确监控

### 7.5 风险

- **风险：低** — 工具链在 AOT 管线之外运行，对生产二进制零影响。
- **风险：文件轮询开销** — 500ms 轮询对 `.vue` 文件大小足够；可在 v6 优化为系统级文件监听。

---

## 八、推迟到 v6 及以后的项目

| 项目 | 来源 | 推迟原因 |
|------|------|----------|
| Direct2D GPU 渲染 | v3 Phase 8、v4 M4.2 | GDI 对当前元素数量足够；无渲染瓶颈。构建图像/动画密集应用时重新评估 |
| 多窗口支持 | 新增 | 需要根本不同的 C++ 窗口管理和跨窗口状态同步。M2 组件生态验证之前为时过早 |
| 可访问性（屏幕阅读器、高对比度） | 新增 | 需要 Win32 可访问性 API 集成（UI Automation、MSAA），面积大 |
| 国际化（i18n） | 新增 | 字符串提取、语言文件、格式化模式。可作为应用层关注点添加，v6 提供框架钩子 |
| 运行时动态组件 | v4 M3 远期 | AOT 根本禁止动态类加载。需要编译期注册表列出所有可能组件，属于 v6 研究课题 |
| 性能剖析面板 | 新增 | 需要 M3 增量渲染基线。性能数据收集后重新评估 |
| 打包安装器（MSI/Setup） | 新增 | `bin/` 目录 + DLL 模式可行。安装器创建属于分发层面非框架层面 |

---

## 九、验证计划

### 9.1 每个里程碑的验收基线

每个里程碑交付后必须通过：

| # | 验证项 | 方法 |
|---|--------|------|
| 1 | `tests/sfc-compiler-test.php` 全部通过 | `php tests/sfc-compiler-test.php` |
| 2 | `tests/verify-layout.php` 布局一致 | `php tests/verify-layout.php` |
| 3 | AOT 编译零错误 | `swoole_compiler.exe project.yml -f` |
| 4 | 运行时功能回归 | 手动测试：18 个按钮、Error 显示、退出 |
| 5 | 代码体积不显著增加 | ±15% 可接受 |
| 6 | AOT 校验器零警告 | `AotValidator::validate()` 返回空 |
| 7 | 增量测试项覆盖新增功能 | 每个里程碑的新增测试 100% 通过 |

### 9.2 各里程碑新增测试

| 里程碑 | 新增测试 |
|--------|----------|
| M1 | 路径验证测试、BaseRenderer 泛化测试、脚手架验证 |
| M2 | 组件引用解析、Props 替换、Slot 内联、嵌套限制 |
| M3 | 元素 ID 分配、增量渲染正确性、ChangeQueue 集成 |
| M4 | InputValidator、错误边界、渲染器单元测试、性能基线 |
| M5 | dev-server、error-mapper、project.yml 扩展 |

### 9.3 放弃条件

以下情况应放弃或推迟相关里程碑：

- AOT 编译失败且根因在 AOT 编译器本身（非代码生成问题）
- 回归失败确认非编译器输出差异
- 代码体积增长超过 30%
- M2 递归编译导致 AOT 符号冲突且无法通过调整代码生成解决

---

## 十、关键文件索引

### 10.1 框架层（`framework/`）

| 文件 | 角色 | v5 改动 |
|------|------|---------|
| `ReactiveComponent.php` | 响应式基类 | M3 重构 dirty 追踪 |
| `ChangeQueue.php` | 环形缓冲变更队列 | M3 集成 + 元素 ID |
| `BaseRenderer.php` | 泛化渲染器 | M1 泛化、M3 增量、M4 错误边界 |
| `sfc-compiler.php` | 编译器主入口 | M1 迁移、M2 递归、M3 ID、M5 源映射 |
| `compiler/ast-nodes.php` | AST 节点定义 | M2 新增 ComponentRefNode |
| `compiler/template-parser.php` | 递归下降解析器 | M2 组件引用、Props、Slots、M3 ID 分配 |
| `compiler/aot-validator.php` | AOT 校验器 | M2 新增嵌套规则 |
| `compiler/script-analyzer.php` | 脚本分析 | M3 元素 ID dirty、M4 验证注入 |
| `compiler/component-registry.php` | 组件注册表 | **M2 新增** |
| `InputValidator.php` | 输入验证 | **M4 新增** |
| `PerformanceTimer.php` | 性能计时 | **M4 新增** |
| `dev-server.php` | 热重载服务器 | **M5 新增** |
| `error-mapper.php` | 错误源映射 | **M5 新增** |

### 10.2 应用层（`apps/calculator/`）

| 文件 | 角色 | v5 改动 |
|------|------|---------|
| `Calculator.vue` | SFC 源文件 | 基本不变（仅路径更新） |
| `CalcApp.php` | 应用控制器 | M1 提取、M3 ChangeQueue、M4 错误边界 |
| `main.php` | 入口点 | M1 精简 |
| `project.yml` | 应用配置 | M2 组件注册、M4 验证开关、M5 开发段 |
| `gen/` | 生成输出 | 编译器重新生成 |

### 10.3 C++ 与桥接层

| 文件 | v5 改动 |
|------|---------|
| `cpp/vue_calc.cc` | M3 可选裁剪区域支持 |
| `stub/vue_calc.stub.php` | M3 更新函数签名 |

### 10.4 测试层

| 文件 | v5 改动 |
|------|---------|
| `tests/sfc-compiler-test.php` | M1 路径更新、M2/M3/M4 新增测试（35+） |
| `tests/renderer-test.php` | **M4 新增** — 渲染器单元测试 |
| `tests/integration-test.php` | **M4 新增** — 端到端管线测试 |
| `tests/verify-layout.php` | 回归验证 |

---

## 十一、总结

| 维度 | 内容 |
|------|------|
| **v5 核心主题** | 生产级 SFC-to-AOT 应用框架 — 从计算器 Demo 走向可复用的桌面应用框架 |
| **战略转变** | 从「让计算器更好用」转向「让框架能构建任何桌面应用」 |
| **v5 主要产出** | M1 框架提取层、M2 组件生态（嵌套+Props+Slot）、M3 增量渲染+ChangeQueue、M4 生产加固（验证+错误+测试+性能）、M5 开发工具链（热重载+源映射） |
| **v5 设计原则** | 框架与应用分离、编译期静态解决（保持 AOT 安全）、增量而非颠覆、工具链独立于 AOT |
| **与 v4 的关系** | v4 偿清了全部技术债务，v5 在此干净基线上构建框架成熟度 |
| **关键风险** | M2 递归编译的 AOT 兼容性、M3 增量渲染的视觉正确性 |
| **推迟项目** | Direct2D、多窗口、可访问性、国际化 — 推迟到 v6 |

| 里程碑 | 优先级 | 规模 | 核心交付 |
|--------|--------|------|----------|
| M1: 框架提取 | **P0** | ~12 文件移动 | 独立的 `framework/` 和 `apps/calculator/` |
| M2: 组件生态 | **P0** | ~800 行新增 | 嵌套组件 + Props + Slot |
| M3: 增量渲染 | **P1** | ~400 行修改 | ChangeQueue 集成 + 节点级 dirty + 区域裁剪 |
| M4: 生产加固 | **P1** | ~600 行新增 | 输入验证 + 错误边界 + 60+ 测试 + 性能基线 |
| M5: 开发工具链 | **P2** | ~400 行新增 | 热重载 + 错误源映射 + 增强 project.yml |

**总计**：约 2,200 行变更，涉及约 25 个文件（12 文件移动、8 文件新增、5 文件显著修改）。

---

*VueCalc v5.0 设计规划 · 基于 v4 M2 响应系统完善基线（全部 10 项技术债务已偿清）· 2026-05*
