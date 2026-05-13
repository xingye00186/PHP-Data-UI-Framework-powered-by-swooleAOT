# Plan: 引入更多组件展示组件生态

## Context

当前项目只有 `DisplayPanel` 一个子组件，虽然框架已支持组件生态系统（ComponentRegistry、ComponentRefNode、compile-time inlining），但示范效果不够直观。用户希望引入更多组件（尤其是弹窗/对话框风格），更直观地展示组件化的强大。

## 设计方案

### 新增组件

**1. NumPad.vue** — 数字键盘组件（提取按钮网格）
- 将 App.vue 中内联的 `<grid>` (18个按钮) 提取为独立组件
- 组件位于 `apps/calculator/components/NumPad.vue`
- 使用 `x`/`y` props 定位

**2. AboutDialog.vue** — 关于弹窗组件
- 模态覆盖层（半透明背景 + 对话框面板）
- 使用 `v-if` 条件渲染控制显隐
- 包含标题、内容、版本号三个文本区域
- 对话框显示/隐藏通过 App 的 `showDialog` 属性控制
- 由 App.vue 中新增的 "?" 按钮切换

### App.vue 变化

改造前结构：
```
App.vue
├── rect (app-bg)
├── display-panel (子组件)
├── text (expression, v-if)
└── grid (18 buttons, 内联)
```

改造后结构：
```
App.vue
├── rect (app-bg)
├── display-panel (子组件)
├── text (expression, v-if)
├── num-pad (子组件) ← 新提取
├── grid (1 btn: "?" 切换按钮) ← 新增
└── about-dialog (子组件, overlay) ← 新增
```

### App.vue 新增属性/方法

```php
// 对话框状态
public bool $showDialog = false;
public string $dialogTitle = 'About VueCalc';
public string $dialogContent = 'SFC Data-Driven Calculator';
public string $dialogVersion = 'Version 5.0 (M2)';

// 切换对话框
public function toggleAboutDialog(): void {
    $this->showDialog = !$this->showDialog;
    $this->dirty = true;
}
```

### 技术可行性验证

- ✅ v-if 支持 rect 和 text 元素，在 BaseRenderer 渲染时检查
- ✅ 子组件中 v-if 引用的属性在 inlining 后归属于父组件 (App)
- ✅ @click 处理函数在 inlining 后路由到 App 的方法
- ✅ 嵌套深度限制 1 级（所有新组件平级于 DisplayPanel，不违反限制）
- ✅ 按钮不支持 v-if（without modifying lowerToLayout），故对话框用 text 展示信息，"?" 按钮做 toggle
- ⚠️ 弹窗覆盖层是纯视觉的，不影响背后按钮的点击响应

### 修改文件清单

| 文件 | 操作 | 说明 |
|------|------|------|
| `apps/calculator/components/NumPad.vue` | **新建** | 18按键网格组件 |
| `apps/calculator/components/AboutDialog.vue` | **新建** | 弹窗组件 (overlay + 面板) |
| `apps/calculator/App.vue` | **修改** | grid → num-pad + 新增 ? 按钮 + about-dialog |
| `apps/calculator/project.yml` | **修改** | 注册 num-pad, about-dialog |
| `tests/sfc-compiler-test.php` | **修改** | 更新子组件数量断言 |

## 验证方案

1. `php framework/sfc-compiler.php apps/calculator/App.vue` — SFC 编译成功
2. `php tests/sfc-compiler-test.php` — 全部测试通过
3. `php tests/verify-layout.php` — 布局验证通过
4. 检查 `gen/App.gen.php` — evalCondition 包含 showDialog，dispatchClick 包含 toggleAboutDialog
